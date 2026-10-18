<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminLogService;
use App\Services\DojahKycService;
use App\Services\DojahSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class DojahSettingController extends Controller
{
    public function __construct(
        private readonly AdminLogService $adminLog,
        private readonly DojahKycService $dojah,
    ) {}

    public function index(): View
    {
        return view('admin.kyc-settings.index', [
            'dojah' => DojahSettings::allForAdmin(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in([DojahSettings::MODE_SANDBOX, DojahSettings::MODE_LIVE])],
            'enabled' => ['nullable', 'boolean'],
            'liveness_enabled' => ['nullable', 'boolean'],
            'transaction_monitoring_enabled' => ['nullable', 'boolean'],
            'block_risky_withdrawals' => ['nullable', 'boolean'],
            'min_liveness_probability' => ['required', 'numeric', 'min:0', 'max:100'],
            'sandbox_public_key' => ['nullable', 'string', 'max:255'],
            'live_public_key' => ['nullable', 'string', 'max:255'],
            'sandbox_secret_key' => ['nullable', 'string', 'max:255'],
            'live_secret_key' => ['nullable', 'string', 'max:255'],
            'sandbox_app_id' => ['nullable', 'string', 'max:255'],
            'live_app_id' => ['nullable', 'string', 'max:255'],
            'sandbox_ingest_key' => ['nullable', 'string', 'max:255'],
            'live_ingest_key' => ['nullable', 'string', 'max:255'],
            'sandbox_base_url' => ['required', 'url', 'max:500'],
            'live_base_url' => ['required', 'url', 'max:500'],
            'webhook_url' => ['nullable', 'url', 'max:500'],
        ]);

        $data['enabled'] = $request->boolean('enabled');
        $data['liveness_enabled'] = $request->boolean('liveness_enabled');
        $data['transaction_monitoring_enabled'] = $request->boolean('transaction_monitoring_enabled');
        $data['block_risky_withdrawals'] = $request->boolean('block_risky_withdrawals');

        DojahSettings::update($data);

        $this->adminLog->log(
            auth()->user(),
            'dojah_settings_updated',
            null,
            'Dojah KYC settings updated ('.$data['mode'].')',
            [
                'mode' => $data['mode'],
                'enabled' => $data['enabled'],
                'liveness_enabled' => $data['liveness_enabled'],
                'transaction_monitoring_enabled' => $data['transaction_monitoring_enabled'],
            ],
        );

        return back()->with('success', 'Dojah settings saved. Mode: '.strtoupper($data['mode'])
            .' · Liveness: '.(DojahSettings::isLivenessReady() ? 'ready' : 'needs App ID / keys')
            .' · Monitoring: '.(DojahSettings::isMonitoringReady() ? 'ready' : 'needs ingest key'));
    }

    public function test(): RedirectResponse
    {
        $result = $this->dojah->ping();

        return back()->with($result['ok'] ? 'success' : 'error', $result['message']);
    }
}
