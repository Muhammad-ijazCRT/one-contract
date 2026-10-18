<?php

namespace App\Http\Controllers\Auth;

use App\Enums\KycStatus;
use App\Enums\UserRole;
use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\CountryList;
use App\Services\IdCardStagingService;
use App\Services\SelfieStagingService;
use App\Services\DojahKycService;
use App\Models\DojahCheck;
use App\Services\UserCodeGenerator;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\View\View;

class RegisterController extends Controller
{
    public function showRegistrationForm(Request $request): View
    {
        return view('auth.register', [
            'referral' => $request->query('ref'),
            'countries' => CountryList::names(),
        ]);
    }

    public function register(
        Request $request,
        IdCardStagingService $idCardStaging,
        SelfieStagingService $selfieStaging,
        DojahKycService $dojah,
    ): RedirectResponse {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'country_id' => ['required', 'string', 'max:10'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'referral_code' => ['nullable', 'string', 'exists:users,referral_code'],
            'terms' => ['required', 'accepted'],
            'id_staging_token' => ['required', 'string', 'min:32', 'max:64'],
            'selfie_staging_token' => ['required', 'string', 'min:32', 'max:64'],
        ], [
            'terms.required' => 'You must read and accept the User Agreement before creating an account.',
            'terms.accepted' => 'You must read and accept the User Agreement before creating an account.',
            'country_id.required' => 'Please select your country ID.',
            'id_staging_token.required' => 'Please capture your ID document before registering.',
            'selfie_staging_token.required' => 'Please complete selfie capture before registering.',
        ]);

        $idPair = $idCardStaging->consumePair($validated['id_staging_token']);
        $idCardPath = $idPair['national_id_front'] ?? $idPair['front'];
        $idCardBackPath = $idPair['national_id_back'] ?? ($idPair['id_document_type'] === 'national_id' ? $idPair['back'] : null);
        $passportPath = $idPair['passport_front'] ?? ($idPair['id_document_type'] === 'passport' ? $idPair['front'] : null);
        $licensePath = $idPair['license_front'] ?? ($idPair['id_document_type'] === 'license' ? $idPair['front'] : null);
        $licenseBackPath = $idPair['license_back'] ?? ($idPair['id_document_type'] === 'license' ? $idPair['back'] : null);
        $selfiePath = $selfieStaging->consume($validated['selfie_staging_token']);

        $user = User::query()->create([
            'name' => $validated['name'],
            'country_id' => $validated['country_id'],
            'email' => $validated['email'],
            'password' => Hash::make($validated['password']),
            'role' => UserRole::User,
            'status' => UserStatus::Active,
            'member_id' => UserCodeGenerator::memberId(),
            'referral_code' => UserCodeGenerator::referralCode(),
            'face_image_path' => $selfiePath,
            'selfie_image_path' => $selfiePath,
            'face_verified_at' => null,
            'id_card_image_path' => $idCardPath,
            'id_card_back_image_path' => $idCardBackPath,
            'id_document_type' => $idPair['id_document_type'],
            'passport_image_path' => $passportPath,
            'license_image_path' => $licensePath,
            'license_back_image_path' => $licenseBackPath,
            'id_card_verified_at' => null,
            'kyc_status' => KycStatus::Pending,
        ]);

        DojahCheck::query()
            ->where('staging_token', $validated['selfie_staging_token'])
            ->whereNull('user_id')
            ->update(['user_id' => $user->id]);

        $livenessPassed = DojahCheck::query()
            ->where('user_id', $user->id)
            ->where('type', DojahCheck::TYPE_LIVENESS)
            ->where('passed', true)
            ->exists();

        if ($livenessPassed) {
            $user->forceFill(['face_verified_at' => now()])->save();
        }

        UserCodeGenerator::attachReferralChain($user, $validated['referral_code'] ?? null);

        $dojah->monitorUserEvent($user, 'onboarding', 'registration');

        app(\App\Services\ChatService::class)->markSeen($user);

        $emailSent = true;

        try {
            event(new Registered($user));
        } catch (\Throwable $e) {
            report($e);
            $emailSent = false;
        }

        if ($emailSent) {
            return redirect()
                ->route('login')
                ->with('status', 'Your account is created. We sent a confirmation email to '.$user->email.'. Please check your inbox and spam folder, then log in.');
        }

        return redirect()
            ->route('verification.notice')
            ->with('email', $user->email)
            ->withErrors([
                'email' => 'Your account is created, but we could not send the confirmation email. Use Resend below or try again in a few minutes.',
            ]);
    }
}
