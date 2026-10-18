<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\FiatRequest;
use App\Services\AdminLogService;
use App\Services\FiatRequestService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class FiatRequestController extends Controller
{
    public function __construct(
        private readonly FiatRequestService $fiat,
        private readonly AdminLogService $adminLog,
    ) {}

    public function index(Request $request): View
    {
        $status = $request->string('status')->toString();
        $direction = $request->string('direction')->toString();

        $query = FiatRequest::query()->with('user')->latest();

        if (in_array($status, ['pending', 'completed', 'rejected'], true)) {
            $query->where('status', $status);
        }
        if (in_array($direction, ['on_ramp', 'off_ramp'], true)) {
            $query->where('direction', $direction);
        }

        return view('admin.fiat.index', [
            'requests' => $query->paginate(20)->withQueryString(),
            'statusFilter' => $status,
            'directionFilter' => $direction,
            'pendingCount' => FiatRequest::query()->where('status', 'pending')->count(),
        ]);
    }

    public function approve(FiatRequest $fiatRequest): RedirectResponse
    {
        if ($fiatRequest->direction === 'on_ramp') {
            $this->fiat->approveOnRamp($fiatRequest, auth()->user());
            $this->adminLog->log(auth()->user(), 'fiat_on_ramp_approved', $fiatRequest, 'Approved fiat on-ramp #'.$fiatRequest->id);
        } else {
            $this->fiat->completeOffRamp($fiatRequest, auth()->user());
            $this->adminLog->log(auth()->user(), 'fiat_off_ramp_completed', $fiatRequest, 'Completed fiat off-ramp #'.$fiatRequest->id);
        }

        return back()->with('success', 'Fiat request processed.');
    }

    public function reject(Request $request, FiatRequest $fiatRequest): RedirectResponse
    {
        $validated = $request->validate([
            'admin_notes' => ['nullable', 'string', 'max:500'],
        ]);
        $notes = $validated['admin_notes'] ?? null;
        $this->fiat->reject($fiatRequest, auth()->user(), $notes);
        $this->adminLog->log(auth()->user(), 'fiat_rejected', $fiatRequest, 'Rejected fiat request #'.$fiatRequest->id);

        return back()->with('success', 'Fiat request rejected.');
    }
}
