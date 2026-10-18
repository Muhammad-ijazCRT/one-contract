<?php

namespace App\Http\Controllers\Admin;

use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\FaceVerificationSession;
use App\Models\User;
use App\Services\AdminLogService;
use App\Services\NotificationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\Response;
use Illuminate\View\View;

class UserController extends Controller
{
    public function __construct(
        private readonly AdminLogService $adminLog,
        private readonly NotificationService $notifications,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $kyc = $request->string('kyc')->toString();
        $search = $request->string('q')->trim()->toString();

        $query = User::query()
            ->where('role', UserRole::User)
            ->withCount(['deposits', 'referrals', 'withdrawalRequests'])
            ->latest();

        if ($status === 'active') {
            $query->where('status', UserStatus::Active);
        } elseif ($status === 'suspended') {
            $query->where('status', UserStatus::Suspended);
        }

        if (in_array($kyc, ['pending', 'approved', 'rejected'], true)) {
            $query->where('kyc_status', $kyc);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('member_id', 'like', "%{$search}%");
            });
        }

        $base = User::query()->where('role', UserRole::User);

        return view('admin.users.index', [
            'users' => $query->paginate(20)->withQueryString(),
            'statusFilter' => $status,
            'kycFilter' => $kyc,
            'search' => $search,
            'counts' => [
                'all' => (clone $base)->count(),
                'active' => (clone $base)->where('status', UserStatus::Active)->count(),
                'suspended' => (clone $base)->where('status', UserStatus::Suspended)->count(),
                'kyc_pending' => (clone $base)->where('kyc_status', \App\Enums\KycStatus::Pending)->count(),
                'kyc_approved' => (clone $base)->where('kyc_status', \App\Enums\KycStatus::Approved)->count(),
                'kyc_rejected' => (clone $base)->where('kyc_status', \App\Enums\KycStatus::Rejected)->count(),
            ],
        ]);
    }

    public function show(User $user): View
    {
        if ($user->role !== UserRole::User) {
            abort(403);
        }

        $multiWallet = app(\App\Services\MultiWalletService::class);
        $multiWallet->ensureWallets($user);

        return view('admin.users.show', [
            'user' => $user->loadCount(['deposits', 'referrals', 'withdrawalRequests']),
            'walletCards' => $multiWallet->walletCards($user),
            'faceVerificationSessions' => $user->faceVerificationSessions()->limit(15)->get(),
            'dojahChecks' => $user->dojahChecks()->limit(20)->get(),
        ]);
    }

    public function edit(User $user): View
    {
        if ($user->role !== UserRole::User) {
            abort(403);
        }

        return view('admin.users.edit', [
            'user' => $user,
        ]);
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        if ($user->role !== UserRole::User) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email,'.$user->id],
            'status' => ['required', 'in:active,suspended'],
        ]);

        $user->update([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'status' => $validated['status'] === 'active' ? UserStatus::Active : UserStatus::Suspended,
        ]);

        $this->adminLog->log(
            auth()->user(),
            'user_updated',
            $user,
            "User {$user->name} ({$user->member_id}) updated by admin.",
        );

        return redirect()->route('admin.users.index')->with('success', 'User updated successfully.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->role !== UserRole::User) {
            abort(403);
        }

        if (auth()->id() === $user->id) {
            return back()->with('success', 'You cannot delete your own account.');
        }

        $name = $user->name;
        $memberId = $user->member_id;
        $user->delete();

        $this->adminLog->log(
            auth()->user(),
            'user_deleted',
            null,
            "User {$name} ({$memberId}) deleted by admin.",
        );

        return redirect()->route('admin.users.index')->with('success', 'User deleted successfully.');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        if ($user->role !== UserRole::User) {
            abort(403);
        }

        $newStatus = $user->status === UserStatus::Active
            ? UserStatus::Suspended
            : UserStatus::Active;

        $user->update(['status' => $newStatus]);

        $this->adminLog->log(
            auth()->user(),
            $newStatus === UserStatus::Suspended ? 'user_suspended' : 'user_activated',
            $user,
            "User {$user->name} ({$user->member_id}) ".($newStatus === UserStatus::Suspended ? 'suspended' : 'activated'),
        );

        $this->notifications->notifyUser(
            $user,
            'account',
            $newStatus === UserStatus::Suspended ? 'Account Suspended' : 'Account Activated',
            $newStatus === UserStatus::Suspended
                ? 'Your account has been suspended. Please contact support for assistance.'
                : 'Your account has been reactivated. You can log in and use the platform again.',
        );

        return back()->with('success', 'User account '.($newStatus === UserStatus::Suspended ? 'suspended' : 'activated').'.');
    }

    public function approveKyc(User $user): RedirectResponse
    {
        if ($user->role !== UserRole::User) {
            abort(403);
        }

        if (! $user->hasCompleteKycDocuments()) {
            return back()->with('error', 'Cannot approve KYC: both face and ID card images are required. Ask the user to complete verification first.');
        }

        $user->forceFill([
            'kyc_status' => \App\Enums\KycStatus::Approved,
            'face_verified_at' => now(),
            'id_card_verified_at' => now(),
            'kyc_reviewed_at' => now(),
            'kyc_review_note' => null,
        ])->save();

        $this->adminLog->log(
            auth()->user(),
            'kyc_approved',
            $user,
            "KYC approved for {$user->name} ({$user->member_id}).",
        );

        $this->notifications->notifyUser(
            $user,
            'kyc',
            'KYC Confirmed',
            'Your identity has been confirmed. You can now submit withdrawal requests for admin approval.',
        );

        return back()->with('success', 'KYC approved. User has been notified.');
    }

    public function rejectKyc(Request $request, User $user): RedirectResponse
    {
        if ($user->role !== UserRole::User) {
            abort(403);
        }

        $validated = $request->validate([
            'note' => ['required', 'string', 'max:500'],
        ]);

        $user->forceFill([
            'kyc_status' => \App\Enums\KycStatus::Rejected,
            'face_verified_at' => null,
            'id_card_verified_at' => null,
            'kyc_reviewed_at' => now(),
            'kyc_review_note' => $validated['note'],
        ])->save();

        $this->adminLog->log(
            auth()->user(),
            'kyc_rejected',
            $user,
            "KYC rejected for {$user->name} ({$user->member_id}).",
        );

        $this->notifications->notifyUser(
            $user,
            'kyc',
            'KYC Not Confirmed',
            'Your KYC was not confirmed. Reason: '.$validated['note'].' Please contact support for help.',
        );

        return back()->with('success', 'KYC rejected. User has been notified.');
    }

    public function faceSessionImage(User $user, FaceVerificationSession $session): Response
    {
        if ($user->role !== UserRole::User) {
            abort(403);
        }

        if ((int) $session->user_id !== (int) $user->id) {
            abort(404);
        }

        $path = $session->capture_image_path;
        if (! $path) {
            abort(404);
        }

        $path = str_replace('\\', '/', ltrim($path, '/'));
        if (! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $mime = Storage::disk('public')->mimeType($path) ?: 'image/jpeg';
        $content = Storage::disk('public')->get($path);

        return response($content, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=300',
        ]);
    }

    public function kycImage(User $user, string $type): Response
    {
        if ($user->role !== UserRole::User) {
            abort(403);
        }

        $path = $user->kycImagePath($type);

        if (! $path) {
            abort(404);
        }

        $path = str_replace('\\', '/', ltrim($path, '/'));
        if (! Storage::disk('public')->exists($path)) {
            abort(404);
        }

        $mime = Storage::disk('public')->mimeType($path) ?: 'image/jpeg';
        $content = Storage::disk('public')->get($path);

        return response($content, 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'private, max-age=300',
        ]);
    }
}
