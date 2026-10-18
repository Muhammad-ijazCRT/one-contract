<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\AdminLogService;
use App\Services\MonnifySettings;
use App\Services\NowPaymentsSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class PaymentSettingController extends Controller
{
    public function __construct(
        private readonly AdminLogService $adminLog,
    ) {}

    public function index(): View
    {
        return view('admin.payment-settings.index', [
            'settings' => NowPaymentsSettings::allForAdmin(),
            'monnify' => MonnifySettings::allForAdmin(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in([NowPaymentsSettings::MODE_SANDBOX, NowPaymentsSettings::MODE_LIVE])],
            'sandbox_api_key' => ['nullable', 'string', 'max:255'],
            'live_api_key' => ['nullable', 'string', 'max:255'],
            'sandbox_public_key' => ['nullable', 'string', 'max:255'],
            'live_public_key' => ['nullable', 'string', 'max:255'],
            'sandbox_ipn_secret' => ['nullable', 'string', 'max:255'],
            'live_ipn_secret' => ['nullable', 'string', 'max:255'],
            'sandbox_url' => ['required', 'url', 'max:500'],
            'live_url' => ['required', 'url', 'max:500'],
            'checkout_endpoint' => ['required', 'string', 'max:120'],
            'ipn_callback_url' => ['required', 'url', 'max:500'],
            'success_url' => ['nullable', 'url', 'max:500'],
            'cancel_url' => ['nullable', 'url', 'max:500'],
            'sandbox_custody_email' => ['nullable', 'email', 'max:255'],
            'live_custody_email' => ['nullable', 'email', 'max:255'],
            'sandbox_custody_password' => ['nullable', 'string', 'max:255'],
            'live_custody_password' => ['nullable', 'string', 'max:255'],
        ]);

        NowPaymentsSettings::update($data);

        $this->adminLog->log(
            auth()->user(),
            'payment_settings_updated',
            null,
            'NOWPayments settings updated ('.$data['mode'].')',
            [
                'mode' => $data['mode'],
                'sandbox_url' => $data['sandbox_url'],
                'live_url' => $data['live_url'],
                'checkout_endpoint' => $data['checkout_endpoint'],
                'ipn_callback_url' => $data['ipn_callback_url'],
                'sandbox_api_key_set' => filled($data['sandbox_api_key'] ?? null),
                'live_api_key_set' => filled($data['live_api_key'] ?? null),
                'sandbox_public_key_set' => filled($data['sandbox_public_key'] ?? null),
                'live_public_key_set' => filled($data['live_public_key'] ?? null),
            ],
        );

        return back()->with('success', 'Payment settings saved. Active mode: '.strtoupper($data['mode']).'.');
    }

    public function updateMonnify(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', Rule::in([MonnifySettings::MODE_SANDBOX, MonnifySettings::MODE_LIVE])],
            'enabled' => ['nullable', 'boolean'],
            'deposits_enabled' => ['nullable', 'boolean'],
            'withdrawals_enabled' => ['nullable', 'boolean'],
            'sandbox_api_key' => ['nullable', 'string', 'max:255'],
            'live_api_key' => ['nullable', 'string', 'max:255'],
            'sandbox_secret_key' => ['nullable', 'string', 'max:255'],
            'live_secret_key' => ['nullable', 'string', 'max:255'],
            'sandbox_base_url' => ['required', 'url', 'max:500'],
            'live_base_url' => ['required', 'url', 'max:500'],
            'sandbox_contract_code' => ['nullable', 'string', 'max:120'],
            'live_contract_code' => ['nullable', 'string', 'max:120'],
            'sandbox_wallet_account' => ['nullable', 'string', 'max:120'],
            'live_wallet_account' => ['nullable', 'string', 'max:120'],
            'redirect_url' => ['nullable', 'url', 'max:500'],
            'webhook_url' => ['nullable', 'url', 'max:500'],
        ]);

        $data['enabled'] = $request->boolean('enabled');
        $data['deposits_enabled'] = $request->boolean('deposits_enabled');
        $data['withdrawals_enabled'] = $request->boolean('withdrawals_enabled');

        MonnifySettings::update($data);

        $this->adminLog->log(
            auth()->user(),
            'monnify_settings_updated',
            null,
            'Monnify settings updated ('.$data['mode'].')',
            [
                'mode' => $data['mode'],
                'enabled' => $data['enabled'],
                'sandbox_base_url' => $data['sandbox_base_url'],
                'live_base_url' => $data['live_base_url'],
            ],
        );

        return back()->with('success', 'Monnify settings saved. API key: '
            .(MonnifySettings::apiKey() !== '' ? 'saved' : 'missing')
            .' · Contract: '
            .(MonnifySettings::contractCode() !== '' ? 'saved' : 'still required')
            .' · Mode: '.strtoupper($data['mode']).'.');
    }
}
