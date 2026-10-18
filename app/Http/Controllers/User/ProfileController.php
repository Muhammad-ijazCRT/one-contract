<?php

namespace App\Http\Controllers\User;

use App\Enums\UserStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\UpdateProfileRequest;
use App\Services\MultiWalletService;
use App\Services\UserWalletService;
use App\Services\WalletLiveRateService;
use App\Support\CountryList;
use App\Support\GoogleAuth;
use App\Support\TwoFactorAuth;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ProfileController extends Controller
{
    public function __construct(
        private readonly UserWalletService $wallet,
        private readonly MultiWalletService $multiWallets,
        private readonly WalletLiveRateService $liveRates,
    ) {}

    public function edit(Request $request): View
    {
        $user = auth()->user();
        $setupSecret = session('two_factor.setup_secret');
        $twoFactorQrSvg = null;

        if (filled($setupSecret) && ! $user->hasTwoFactorConnected()) {
            $twoFactorQrSvg = app(TwoFactorAuth::class)->qrCodeSvg($user->email, $setupSecret);
        }

        $referralOverview = null;
        $referralLink = null;
        $stats = null;
        $vip = null;

        if (! $user->isAdmin()) {
            $walletStats = $this->wallet->stats($user);
            $referralStats = $this->wallet->referralStats($user);
            $referralEarned = array_sum(array_column($referralStats, 'earnings'));
            $totalEarnings = (float) ($walletStats['total_rewards'] ?? 0)
                + (float) ($walletStats['referral_earnings'] ?? 0)
                + (float) ($walletStats['gaming_wins'] ?? 0);

            $portfolioUsd = $this->liveRates->portfolioUsd($this->multiWallets->balances($user));

            $stats = [
                'main_wallet' => $portfolioUsd,
                'total_balance_usd' => $portfolioUsd,
                'total_invested' => (float) ($walletStats['total_invested'] ?? 0),
                'total_earnings' => $totalEarnings,
                'referral_earnings' => (float) ($walletStats['referral_earnings'] ?? 0),
            ];

            $vip = $this->vipProgress(
                (float) ($walletStats['total_deposited'] ?? 0),
                (float) ($walletStats['total_invested'] ?? 0),
                $totalEarnings,
            );

            $referralOverview = [
                'total' => $user->referrals()->count(),
                'active' => $user->referrals()
                    ->whereHas('referred', fn ($q) => $q->where('status', UserStatus::Active))
                    ->count(),
                'earned' => $referralEarned,
            ];

            $referralLink = url('/register?ref='.$user->referral_code);
        }

        $countries = CountryList::names();
        $countryName = $countries[$user->country_id] ?? ($user->country_id ?: '—');

        $verification = [
            [
                'key' => 'email',
                'label' => 'Email Verification',
                'done' => $user->hasVerifiedEmail(),
                'url' => route($this->securityRoute()),
            ],
            [
                'key' => 'phone',
                'label' => 'Phone Verification',
                'done' => filled($user->phone),
                'url' => '#profile-edit-panel',
            ],
            [
                'key' => 'kyc',
                'label' => 'KYC Verification',
                'done' => $user->isKycApproved(),
                'url' => '#ocpf-kyc-modal',
                'action' => 'kyc-docs',
            ],
            [
                'key' => 'address',
                'label' => 'Address Verification',
                'done' => filled($user->country_id),
                'url' => $user->isAdmin() ? '#profile-edit-panel' : route('user.support.index'),
            ],
        ];

        $kycDocuments = [
            'has_face' => filled($user->face_image_path),
            'has_id' => filled($user->id_card_image_path),
            'face_url' => (! $user->isAdmin() && filled($user->face_image_path))
                ? route('user.profile.kyc-image', ['type' => 'face'])
                : null,
            'id_url' => (! $user->isAdmin() && filled($user->id_card_image_path))
                ? route('user.profile.kyc-image', ['type' => 'id'])
                : null,
            'status' => $user->kyc_status?->label() ?? 'Under Verification',
            'approved' => $user->isKycApproved(),
        ];
        $kycDocuments['has_docs'] = (bool) ($kycDocuments['face_url'] || $kycDocuments['id_url']);

        return view('user.profile.edit', [
            'user' => $user,
            'layout' => $this->layout(),
            'profileUpdateRoute' => $this->profileUpdateRoute(),
            'dashboardRoute' => $this->dashboardRoute(),
            'securityRoute' => $this->securityRoute(),
            'securityPasswordRoute' => $this->securityPasswordRoute(),
            'googleConnectRoute' => $this->googleConnectRoute(),
            'googleDisconnectRoute' => $this->googleDisconnectRoute(),
            'twoFactorSetupRoute' => $this->twoFactorSetupRoute(),
            'twoFactorConfirmRoute' => $this->twoFactorConfirmRoute(),
            'twoFactorToggleRoute' => $this->twoFactorToggleRoute(),
            'twoFactorDisconnectRoute' => $this->twoFactorDisconnectRoute(),
            'twoFactorSetupSecret' => $setupSecret,
            'twoFactorQrSvg' => $twoFactorQrSvg,
            'googleAuthConfigured' => GoogleAuth::isConfigured(),
            'googleAuthSetupHint' => GoogleAuth::showSetupHint(),
            'activeTab' => (
                $request->query('tab') === 'security'
                || (
                    ($errorBag = $request->session()->get('errors'))
                    && $errorBag instanceof \Illuminate\Support\ViewErrorBag
                    && $errorBag->hasAny([
                        'current_password',
                        'password',
                        'two_factor',
                        'two_factor_code',
                        'two_factor_password',
                        'google',
                    ])
                )
            ) ? 'security' : 'profile',
            'referralOverview' => $referralOverview,
            'referralLink' => $referralLink,
            'stats' => $stats,
            'vip' => $vip,
            'countryName' => $countryName,
            'verification' => $verification,
            'kycLabel' => ($user->kyc_status?->label() ?? 'Under Verification'),
            'kycDocuments' => $kycDocuments,
            'openEdit' => $request->boolean('edit') || $request->session()->has('errors'),
            'openKycDocs' => $request->query('view') === 'kyc',
        ]);
    }

    public function kycImage(string $type): Response
    {
        $user = auth()->user();

        if ($user->isAdmin()) {
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

    public function update(UpdateProfileRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $data = $request->validated();

        if ($request->hasFile('avatar')) {
            if ($user->avatar) {
                Storage::disk('public')->delete($user->avatar);
            }

            $data['avatar'] = $request->file('avatar')->store('avatars', 'public');
        }

        $user->update([
            'name' => $data['name'],
            'phone' => $data['phone'] ?? null,
            'avatar' => $data['avatar'] ?? $user->avatar,
        ]);

        return redirect()
            ->route($this->profileEditRoute())
            ->with('success', 'Profile updated successfully.');
    }

    /**
     * @return array{vip: int, next_vip: int, xp: int, xp_next: int, xp_pct: float, label: string}
     */
    private function vipProgress(float $deposited, float $invested, float $totalEarnings): array
    {
        $thresholds = [
            1 => 0,
            2 => 500,
            3 => 2000,
            4 => 5000,
            5 => 10000,
        ];

        $score = max($deposited, $invested * 2);
        $vip = 1;
        foreach ([5, 4, 3, 2] as $level) {
            if ($score >= $thresholds[$level]) {
                $vip = $level;
                break;
            }
        }

        $nextVip = min(5, $vip + 1);
        $xp = (int) max(0, floor($score));
        $xpNext = $vip >= 5
            ? max($xp, $thresholds[5])
            : (int) $thresholds[$nextVip];
        $xpPct = $xpNext > 0
            ? min(100, round(($xp / $xpNext) * 100, 1))
            : 100.0;

        return [
            'vip' => $vip,
            'next_vip' => $nextVip,
            'xp' => $xp,
            'xp_next' => $xpNext,
            'xp_pct' => $xpPct,
            'label' => 'VIP Level '.$vip,
        ];
    }

    private function layout(): string
    {
        return auth()->user()->isAdmin() ? 'layouts.admin' : 'layouts.dashboard';
    }

    private function profileEditRoute(): string
    {
        return auth()->user()->isAdmin() ? 'admin.profile.edit' : 'user.profile.edit';
    }

    private function profileUpdateRoute(): string
    {
        return auth()->user()->isAdmin() ? 'admin.profile.update' : 'user.profile.update';
    }

    private function dashboardRoute(): string
    {
        return auth()->user()->isAdmin() ? 'admin.dashboard' : 'user.dashboard';
    }

    private function securityRoute(): string
    {
        return auth()->user()->isAdmin() ? 'admin.security.index' : 'user.security.index';
    }

    private function securityPasswordRoute(): string
    {
        return auth()->user()->isAdmin() ? 'admin.security.password' : 'user.security.password';
    }

    private function googleConnectRoute(): string
    {
        return auth()->user()->isAdmin() ? 'admin.security.google.connect' : 'user.security.google.connect';
    }

    private function googleDisconnectRoute(): string
    {
        return auth()->user()->isAdmin() ? 'admin.security.google.disconnect' : 'user.security.google.disconnect';
    }

    private function twoFactorSetupRoute(): string
    {
        return auth()->user()->isAdmin() ? 'admin.security.two-factor.setup' : 'user.security.two-factor.setup';
    }

    private function twoFactorConfirmRoute(): string
    {
        return auth()->user()->isAdmin() ? 'admin.security.two-factor.confirm' : 'user.security.two-factor.confirm';
    }

    private function twoFactorToggleRoute(): string
    {
        return auth()->user()->isAdmin() ? 'admin.security.two-factor.toggle' : 'user.security.two-factor.toggle';
    }

    private function twoFactorDisconnectRoute(): string
    {
        return auth()->user()->isAdmin() ? 'admin.security.two-factor.disconnect' : 'user.security.two-factor.disconnect';
    }
}
