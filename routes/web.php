<?php

use App\Http\Controllers\Admin\AnnouncementController as AdminAnnouncementController;
use App\Http\Controllers\Admin\ClubCapitalRequestController as AdminClubCapitalRequestController;
use App\Http\Controllers\Admin\ContactSettingController as AdminContactSettingController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\DepositController as AdminDepositController;
use App\Http\Controllers\Admin\InvestmentClubController as AdminInvestmentClubController;
use App\Http\Controllers\Admin\LogController as AdminLogController;
use App\Http\Controllers\Admin\NewsletterController as AdminNewsletterController;
use App\Http\Controllers\Admin\DojahSettingController as AdminDojahSettingController;
use App\Http\Controllers\Admin\PaymentSettingController as AdminPaymentSettingController;
use App\Http\Controllers\Admin\PredictionTrendLibraryController;
use App\Http\Controllers\Admin\ReferralController as AdminReferralController;
use App\Http\Controllers\Admin\ReportController as AdminReportController;
use App\Http\Controllers\Admin\RewardController as AdminRewardController;
use App\Http\Controllers\Admin\SavingsPlanController as AdminSavingsPlanController;
use App\Http\Controllers\Admin\SettingController as AdminSettingController;
use App\Http\Controllers\Admin\TransactionController as AdminTransactionController;
use App\Http\Controllers\Admin\TreasuryController as AdminTreasuryController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\SupportController as AdminSupportController;
use App\Http\Controllers\Admin\WithdrawalController as AdminWithdrawalController;
use App\Http\Controllers\Auth\EmailVerificationController;
use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\RegisterController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\IdCardStagingController;
use App\Http\Controllers\SelfieStagingController;
use App\Http\Controllers\FaceVerificationSessionController;
use App\Http\Controllers\EmailLogoController;
use App\Http\Controllers\ContactController;
use App\Http\Controllers\LegalNoticeController;
use App\Http\Controllers\NewsletterController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\DojahWebhookController;
use App\Http\Controllers\MonnifyController;
use App\Http\Controllers\NowPaymentsController;
use App\Http\Controllers\User\ClubInvestmentController;
use App\Http\Controllers\User\DashboardController as UserDashboardController;
use App\Http\Controllers\User\DepositController as UserDepositController;
use App\Http\Controllers\User\EarningsController;
use App\Http\Controllers\User\LendingPlanController;
use App\Http\Controllers\User\NotificationController as UserNotificationController;
use App\Http\Controllers\User\PredictionGameController;
use App\Http\Controllers\User\AzuboguGameController;
use App\Http\Controllers\User\ChatController;
use App\Http\Controllers\User\GameArenaController;
use App\Http\Controllers\User\LeaderboardController;
use App\Http\Controllers\User\RpsvGameController;
use App\Http\Controllers\User\ProfileController;
use App\Http\Controllers\User\ReferralController;
use App\Http\Controllers\User\RewardClaimController;
use App\Http\Controllers\User\SavingsPlanController;
use App\Http\Controllers\User\SecurityController;
use App\Http\Controllers\User\SupportController;
use App\Http\Controllers\User\TransactionController;
use App\Http\Controllers\User\WithdrawalController as UserWithdrawalController;
use App\Http\Controllers\User\WalletController as UserWalletController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('home.index', [
        'minDeposit' => \App\Services\PlatformSettings::gatewayMinDeposit(),
    ]);
})->name('home');

Route::get('/about', function () {
    return view('about.index');
})->name('about');

Route::get('/investment-clubs', function () {
    return view('investment-clubs.index');
})->name('investment-clubs');

Route::get('/savings-plan', function () {
    return redirect()->route('home');
})->name('savings-plan');

Route::get('/how-it-works', function () {
    return view('how-it-works.index');
})->name('how-it-works');

Route::get('/features', function () {
    return view('features.index');
})->name('features');

Route::get('/faq', function () {
    return view('faq.index');
})->name('faq');

Route::get('/email-assets/logo.jpg', EmailLogoController::class)->name('email.assets.logo');

Route::get('/contact', [ContactController::class, 'index'])->name('contact');
Route::post('/contact', [ContactController::class, 'store'])
    ->middleware('throttle:5,1')
    ->name('contact.store');

Route::post('/newsletter', [NewsletterController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('newsletter.subscribe');

Route::get('/legal/{slug}', [LegalNoticeController::class, 'show'])->name('legal.show');
Route::post('/nowpayments/ipn', [NowPaymentsController::class, 'ipn'])->name('nowpayments.ipn');
Route::post('/monnify/webhook', [MonnifyController::class, 'webhook'])->name('monnify.webhook');
Route::get('/monnify/callback', [MonnifyController::class, 'callback'])->name('monnify.callback');
Route::post('/dojah/webhook', DojahWebhookController::class)->name('dojah.webhook');

Route::get('/csrf-token', function () {
    return response()->json(['token' => csrf_token()]);
})->name('csrf.token');

Route::post('/id-verification/stage', [IdCardStagingController::class, 'store'])
    ->middleware('throttle:15,1')
    ->name('id-verification.stage');

Route::post('/selfie-verification/stage', [SelfieStagingController::class, 'store'])
    ->middleware('throttle:15,1')
    ->name('selfie-verification.stage');

Route::post('/face-verification/start', [FaceVerificationSessionController::class, 'start'])
    ->middleware('throttle:15,1')
    ->name('face-verification.start');
Route::post('/face-verification/challenge', [FaceVerificationSessionController::class, 'challenge'])
    ->middleware('throttle:60,1')
    ->name('face-verification.challenge');
Route::post('/face-verification/complete', [FaceVerificationSessionController::class, 'complete'])
    ->middleware('throttle:15,1')
    ->name('face-verification.complete');

Route::middleware('guest')->group(function () {
    Route::get('/login', [LoginController::class, 'showLoginForm'])->name('login');
    Route::post('/login', [LoginController::class, 'login']);
    Route::get('/admin/login', [LoginController::class, 'showAdminLoginForm'])->name('admin.login');
    Route::post('/admin/login', [LoginController::class, 'adminLogin']);
    Route::get('/register', [RegisterController::class, 'showRegistrationForm'])->name('register');
    Route::post('/register', [RegisterController::class, 'register']);

    Route::get('/forgot-password', [ForgotPasswordController::class, 'showLinkRequestForm'])->name('password.request');
    Route::post('/forgot-password', [ForgotPasswordController::class, 'sendResetLinkEmail'])
        ->middleware('throttle:5,1')
        ->name('password.email');
    Route::get('/reset-password/{token}', [ForgotPasswordController::class, 'showResetForm'])->name('password.reset');
    Route::post('/reset-password', [ForgotPasswordController::class, 'reset'])->name('password.update');

    Route::get('/auth/google', [GoogleAuthController::class, 'loginRedirect'])->name('auth.google');
    Route::get('/auth/google/register', [GoogleAuthController::class, 'registerRedirect'])->name('auth.google.register');

    Route::get('/two-factor-challenge', [TwoFactorChallengeController::class, 'show'])->name('two-factor.challenge');
    Route::post('/two-factor-challenge', [TwoFactorChallengeController::class, 'verify'])->name('two-factor.verify');
});

Route::get('/email/verify', [EmailVerificationController::class, 'notice'])->name('verification.notice');
Route::post('/email/verification-notification', [EmailVerificationController::class, 'resend'])
    ->middleware('throttle:6,1')
    ->name('verification.send');

Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');

Route::get('/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
    ->middleware('signed')
    ->name('verification.verify');

Route::post('/logout', [LoginController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'verified', 'role:user'])->prefix('dashboard')->name('user.')->group(function () {
    Route::get('/', [UserDashboardController::class, 'index'])->name('dashboard');
    Route::get('/wallets', [UserWalletController::class, 'index'])->name('wallets.index');
    Route::get('/wallets/select-currency', [UserWalletController::class, 'selectCurrency'])->name('wallets.select-currency');
    Route::post('/wallets/select-currency', [UserWalletController::class, 'setPreferredCurrency'])->middleware('throttle:30,1')->name('wallets.select-currency.store');
    Route::get('/wallets/convert', [UserWalletController::class, 'convertForm'])->name('wallets.convert');
    Route::post('/wallets/convert', [UserWalletController::class, 'convert'])->middleware('throttle:30,1')->name('wallets.convert.store');
    Route::get('/wallets/quote', [UserWalletController::class, 'quote'])->name('wallets.quote');
    Route::get('/wallets/rates', [UserWalletController::class, 'rates'])->name('wallets.rates');
    Route::get('/deposit', [UserDepositController::class, 'index'])->name('deposit.index');
    Route::post('/deposit', [UserDepositController::class, 'store'])->name('deposit.store');
    Route::get('/deposit/return', [UserDepositController::class, 'paymentReturn'])->name('deposit.return');
    Route::get('/deposits/pending', [UserDepositController::class, 'pending'])->name('deposits.pending');
    Route::get('/deposit/{deposit}', [UserDepositController::class, 'show'])->name('deposit.show');
    Route::get('/withdraw', [UserWithdrawalController::class, 'index'])->name('withdraw.index');
    Route::post('/withdraw/send-otp', [UserWithdrawalController::class, 'sendOtp'])->middleware('throttle:5,1')->name('withdraw.send-otp');
    Route::post('/withdraw/reward', [UserWithdrawalController::class, 'storeReward'])->name('withdraw.reward');
    Route::post('/withdraw/capital', [UserWithdrawalController::class, 'storeCapital'])->name('withdraw.capital');
    Route::get('/withdraw/{withdrawal}', [UserWithdrawalController::class, 'show'])->name('withdraw.show');
    Route::post('/withdraw/{withdrawal}/cancel', [UserWithdrawalController::class, 'cancel'])->name('withdraw.cancel');
    Route::get('/transactions', [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{transaction}', [TransactionController::class, 'show'])->name('transactions.show');
    Route::get('/referrals', [ReferralController::class, 'index'])->name('referrals.index');
    Route::get('/referral-network', [ReferralController::class, 'network'])->name('referrals.network');
    Route::get('/referrals/{referral}', [ReferralController::class, 'show'])->name('referrals.show');
    Route::get('/earnings', [EarningsController::class, 'index'])->name('earnings.index');
    Route::get('/clubs', [ClubInvestmentController::class, 'index'])->name('clubs.index');
    Route::post('/clubs/terms', [ClubInvestmentController::class, 'acceptTerms'])->name('clubs.terms');
    Route::post('/clubs/{club}/invest', [ClubInvestmentController::class, 'invest'])
        ->middleware('terms:investment_clubs')
        ->name('clubs.invest');
    Route::post('/clubs/investments/{investment}/withdraw', [ClubInvestmentController::class, 'withdraw'])
        ->middleware('terms:investment_clubs')
        ->name('clubs.withdraw');
    // Savings retired — named routes kept for old links (redirect only).
    $retireToDashboard = fn () => redirect()
        ->route('user.dashboard')
        ->with('info', 'Savings Plan is retired. Contact support if you have an active plan or pending reward.');
    Route::get('/savings', $retireToDashboard)->name('savings.index');
    Route::post('/savings/terms', $retireToDashboard)->name('savings.terms');
    Route::post('/savings/enroll', $retireToDashboard)->name('savings.enroll');
    Route::post('/savings/{plan}/early-withdraw', $retireToDashboard)->name('savings.early');
    Route::post('/savings/{plan}/return-principal', $retireToDashboard)->name('savings.return-principal');
    Route::get('/lending', [LendingPlanController::class, 'index'])->name('lending.index');
    Route::post('/lending', [LendingPlanController::class, 'store'])->name('lending.store');
    Route::post('/lending/{plan}/early-withdraw', [LendingPlanController::class, 'earlyWithdraw'])->name('lending.early');
    Route::post('/lending/{plan}/return-principal', [LendingPlanController::class, 'returnPrincipal'])->name('lending.return-principal');
    Route::get('/games', [GameArenaController::class, 'index'])->name('games.index');
    Route::get('/prediction', [PredictionGameController::class, 'index'])->name('prediction.index');
    Route::get('/prediction/state', [PredictionGameController::class, 'state'])->name('prediction.state');
    Route::post('/prediction/bet', [PredictionGameController::class, 'bet'])->name('prediction.bet');
    Route::post('/prediction/cancel', [PredictionGameController::class, 'cancel'])->name('prediction.cancel');
    Route::get('/azubogu', [AzuboguGameController::class, 'index'])->name('azubogu.index');
    Route::get('/azubogu/history', [AzuboguGameController::class, 'history'])->name('azubogu.history');
    Route::get('/azubogu/my-bets', [AzuboguGameController::class, 'myBets'])->name('azubogu.my-bets');
    Route::get('/azubogu/state', [AzuboguGameController::class, 'state'])->name('azubogu.state');
    Route::post('/azubogu/bet', [AzuboguGameController::class, 'bet'])->name('azubogu.bet');
    Route::post('/azubogu/cancel', [AzuboguGameController::class, 'cancel'])->name('azubogu.cancel');
    Route::get('/rpsv', [RpsvGameController::class, 'index'])->name('rpsv.index');
    Route::post('/rpsv/queue', [RpsvGameController::class, 'queue'])->middleware('throttle:20,1')->name('rpsv.queue');
    Route::post('/rpsv/queue/cancel', [RpsvGameController::class, 'cancelQueue'])->name('rpsv.queue.cancel');
    Route::get('/rpsv/queue/status', [RpsvGameController::class, 'queueStatus'])->name('rpsv.queue.status');
    Route::post('/rpsv/invite', [RpsvGameController::class, 'invite'])->middleware('throttle:20,1')->name('rpsv.invite');
    Route::post('/rpsv/invite/{invite}/accept', [RpsvGameController::class, 'acceptInvite'])->name('rpsv.invite.accept');
    Route::post('/rpsv/invite/{invite}/decline', [RpsvGameController::class, 'declineInvite'])->name('rpsv.invite.decline');
    Route::post('/rpsv/invite/{invite}/cancel', [RpsvGameController::class, 'cancelInvite'])->name('rpsv.invite.cancel');
    Route::get('/rpsv/match/{match}', [RpsvGameController::class, 'match'])->name('rpsv.match');
    Route::get('/rpsv/match/{match}/state', [RpsvGameController::class, 'state'])->name('rpsv.state');
    Route::post('/rpsv/match/{match}/ready', [RpsvGameController::class, 'ready'])->middleware('throttle:60,1')->name('rpsv.ready');
    Route::post('/rpsv/match/{match}/move', [RpsvGameController::class, 'move'])->middleware('throttle:60,1')->name('rpsv.move');
    Route::post('/rpsv/match/{match}/forfeit', [RpsvGameController::class, 'forfeit'])->name('rpsv.forfeit');
    Route::get('/leaderboard', [LeaderboardController::class, 'index'])->name('leaderboard.index');
    Route::get('/chat', [ChatController::class, 'index'])->name('chat.index');
    Route::get('/chat/messages', [ChatController::class, 'messages'])->name('chat.messages');
    Route::post('/chat/messages', [ChatController::class, 'store'])->middleware('throttle:20,1')->name('chat.store');
    Route::post('/chat/messages/{message}/react', [ChatController::class, 'react'])->middleware('throttle:30,1')->name('chat.react');
    Route::get('/rewards/claim', [RewardClaimController::class, 'index'])->name('rewards.claim');
    Route::post('/rewards/claim', [RewardClaimController::class, 'claim'])->name('rewards.claim.store');
    Route::post('/rewards/claim/{category}', [RewardClaimController::class, 'claimCategory'])->name('rewards.claim.category');
    Route::get('/support', [SupportController::class, 'index'])->name('support.index');
    Route::post('/support', [SupportController::class, 'store'])->name('support.store');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/profile/kyc-image/{type}', [ProfileController::class, 'kycImage'])
        ->whereIn('type', ['face', 'selfie', 'id', 'id_back', 'passport', 'license', 'license_back'])
        ->name('profile.kyc-image');
    Route::get('/security', [SecurityController::class, 'index'])->name('security.index');
    Route::put('/security/password', [SecurityController::class, 'updatePassword'])->name('security.password');
    Route::get('/security/google/connect', [GoogleAuthController::class, 'connectRedirect'])->name('security.google.connect');
    Route::delete('/security/google/disconnect', [GoogleAuthController::class, 'disconnect'])->name('security.google.disconnect');
    Route::post('/security/two-factor/setup', [SecurityController::class, 'setupTwoFactor'])->name('security.two-factor.setup');
    Route::post('/security/two-factor/confirm', [SecurityController::class, 'confirmTwoFactor'])->name('security.two-factor.confirm');
    Route::put('/security/two-factor/toggle', [SecurityController::class, 'toggleTwoFactor'])->name('security.two-factor.toggle');
    Route::delete('/security/two-factor/disconnect', [SecurityController::class, 'disconnectTwoFactor'])->name('security.two-factor.disconnect');
    Route::get('/notifications', [UserNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications/mark-all-read', [UserNotificationController::class, 'markAllRead'])->name('notifications.mark-all');
    Route::get('/notifications/{notification}/read', [NotificationController::class, 'readUser'])->name('notifications.read');
});

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');
    Route::get('/users', [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}', [AdminUserController::class, 'show'])->name('users.show');
    Route::get('/users/{user}/edit', [AdminUserController::class, 'edit'])->name('users.edit');
    Route::put('/users/{user}', [AdminUserController::class, 'update'])->name('users.update');
    Route::delete('/users/{user}', [AdminUserController::class, 'destroy'])->name('users.destroy');
    Route::post('/users/{user}/toggle-status', [AdminUserController::class, 'toggleStatus'])->name('users.toggle-status');
    Route::post('/users/{user}/kyc/approve', [AdminUserController::class, 'approveKyc'])->name('users.kyc.approve');
    Route::post('/users/{user}/kyc/reject', [AdminUserController::class, 'rejectKyc'])->name('users.kyc.reject');
    Route::get('/users/{user}/kyc-image/{type}', [AdminUserController::class, 'kycImage'])
        ->whereIn('type', ['face', 'selfie', 'id', 'id_back', 'passport', 'license', 'license_back'])
        ->name('users.kyc.image');
    Route::get('/users/{user}/face-sessions/{session}/image', [AdminUserController::class, 'faceSessionImage'])
        ->name('users.face-session.image');
    Route::get('/support', [AdminSupportController::class, 'index'])->name('support.index');
    Route::get('/support/{ticket}', [AdminSupportController::class, 'show'])->name('support.show');
    Route::put('/support/{ticket}', [AdminSupportController::class, 'update'])->name('support.update');
    Route::get('/deposits', [AdminDepositController::class, 'index'])->name('deposits.index');
    Route::get('/deposits/{deposit}', [AdminDepositController::class, 'show'])->name('deposits.show');
    Route::post('/deposits/{deposit}/approve', [AdminDepositController::class, 'approve'])->name('deposits.approve');
    Route::post('/deposits/{deposit}/reject', [AdminDepositController::class, 'reject'])->name('deposits.reject');
    Route::get('/withdrawals', [AdminWithdrawalController::class, 'index'])->name('withdrawals.index');
    Route::get('/withdrawals/reward', [AdminWithdrawalController::class, 'reward'])->name('withdrawals.reward');
    Route::get('/withdrawals/capital', [AdminWithdrawalController::class, 'capital'])->name('withdrawals.capital');
    Route::get('/withdrawals/{withdrawal}', [AdminWithdrawalController::class, 'show'])->name('withdrawals.show');
    Route::post('/withdrawals/{withdrawal}/approve', [AdminWithdrawalController::class, 'approve'])->name('withdrawals.approve');
    Route::post('/withdrawals/{withdrawal}/complete', [AdminWithdrawalController::class, 'complete'])->name('withdrawals.complete');
    Route::post('/withdrawals/{withdrawal}/reject', [AdminWithdrawalController::class, 'reject'])->name('withdrawals.reject');
    Route::get('/rewards', [AdminRewardController::class, 'index'])->name('rewards.index');
    Route::get('/rewards/{reward}', [AdminRewardController::class, 'show'])->name('rewards.show');
    Route::get('/clubs', [AdminInvestmentClubController::class, 'index'])->name('clubs.index');
    Route::put('/clubs/{club}', [AdminInvestmentClubController::class, 'update'])->name('clubs.update');
    Route::get('/capital-requests', [AdminClubCapitalRequestController::class, 'index'])->name('capital-requests.index');
    Route::post('/capital-requests/{capitalRequest}/complete', [AdminClubCapitalRequestController::class, 'complete'])->name('capital-requests.complete');
    Route::get('/savings', [AdminSavingsPlanController::class, 'index'])->name('savings.index');
    Route::post('/savings/{plan}/complete-early', [AdminSavingsPlanController::class, 'completeEarlyReturn'])->name('savings.complete-early');
    Route::get('/referrals', [AdminReferralController::class, 'index'])->name('referrals.index');
    Route::get('/referrals/{referral}', [AdminReferralController::class, 'show'])->name('referrals.show');
    Route::get('/transactions', [AdminTransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/{transaction}', [AdminTransactionController::class, 'show'])->name('transactions.show');
    Route::get('/treasury', [AdminTreasuryController::class, 'index'])->name('treasury.index');
    Route::put('/treasury', [AdminTreasuryController::class, 'update'])->name('treasury.update');
    Route::get('/reports/earnings', [AdminReportController::class, 'earnings'])->name('reports.earnings');
    Route::get('/reports/withdrawals', [AdminReportController::class, 'withdrawals'])->name('reports.withdrawals');
    Route::get('/announcements', [AdminAnnouncementController::class, 'index'])->name('announcements.index');
    Route::post('/announcements', [AdminAnnouncementController::class, 'store'])->name('announcements.store');
    Route::get('/settings', [AdminSettingController::class, 'index'])->name('settings.index');
    Route::put('/settings', [AdminSettingController::class, 'update'])->name('settings.update');
    Route::get('/contact-settings', [AdminContactSettingController::class, 'index'])->name('contact-settings.index');
    Route::put('/contact-settings', [AdminContactSettingController::class, 'update'])->name('contact-settings.update');
    Route::get('/payment-settings', [AdminPaymentSettingController::class, 'index'])->name('payment-settings.index');
    Route::put('/payment-settings', [AdminPaymentSettingController::class, 'update'])->name('payment-settings.update');
    Route::put('/payment-settings/monnify', [AdminPaymentSettingController::class, 'updateMonnify'])->name('payment-settings.monnify');
    Route::get('/kyc-settings', [AdminDojahSettingController::class, 'index'])->name('kyc-settings.index');
    Route::put('/kyc-settings', [AdminDojahSettingController::class, 'update'])->name('kyc-settings.update');
    Route::post('/kyc-settings/test', [AdminDojahSettingController::class, 'test'])->name('kyc-settings.test');
    Route::get('/newsletter', [AdminNewsletterController::class, 'index'])->name('newsletter.index');
    Route::delete('/newsletter/{subscriber}', [AdminNewsletterController::class, 'destroy'])->name('newsletter.destroy');
    Route::get('/prediction-trends', [PredictionTrendLibraryController::class, 'index'])->name('prediction-trends.index');
    Route::post('/prediction-trends', [PredictionTrendLibraryController::class, 'store'])->name('prediction-trends.store');
    Route::post('/prediction-trends/{library}/activate', [PredictionTrendLibraryController::class, 'activate'])->name('prediction-trends.activate');
    Route::get('/logs', [AdminLogController::class, 'index'])->name('logs.index');
    Route::post('/notifications/mark-all-read', [NotificationController::class, 'markAllAdminRead'])->name('notifications.mark-all');
    Route::get('/notifications/{type}/{id}/read', [NotificationController::class, 'readAdmin'])->name('notifications.read');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::put('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::get('/security', [SecurityController::class, 'index'])->name('security.index');
    Route::put('/security/password', [SecurityController::class, 'updatePassword'])->name('security.password');
    Route::get('/security/google/connect', [GoogleAuthController::class, 'connectRedirect'])->name('security.google.connect');
    Route::delete('/security/google/disconnect', [GoogleAuthController::class, 'disconnect'])->name('security.google.disconnect');
    Route::post('/security/two-factor/setup', [SecurityController::class, 'setupTwoFactor'])->name('security.two-factor.setup');
    Route::post('/security/two-factor/confirm', [SecurityController::class, 'confirmTwoFactor'])->name('security.two-factor.confirm');
    Route::put('/security/two-factor/toggle', [SecurityController::class, 'toggleTwoFactor'])->name('security.two-factor.toggle');
    Route::delete('/security/two-factor/disconnect', [SecurityController::class, 'disconnectTwoFactor'])->name('security.two-factor.disconnect');
});


