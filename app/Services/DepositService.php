<?php

namespace App\Services;

use App\Enums\DepositStatus;
use App\Enums\TransactionType;
use App\Enums\WalletCurrency;
use App\Models\Deposit;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class DepositService
{
    public function __construct(
        private readonly NotificationService $notifications,
        private readonly MainWalletService $mainWallet,
        private readonly MultiWalletService $wallets,
        private readonly NowPaymentsClient $nowPayments,
        private readonly DojahKycService $dojah,
    ) {}

    public function submit(User $user, float $amount, string $creditCurrency = 'USDT', ?string $network = null): Deposit
    {
        $minDeposit = PlatformSettings::gatewayMinDeposit();
        $currency = WalletCurrency::normalize($creditCurrency);

        if (! $currency->isCrypto()) {
            throw ValidationException::withMessages([
                'currency' => 'Select a supported cryptocurrency for crypto deposit.',
            ]);
        }

        if ($amount < $minDeposit) {
            throw ValidationException::withMessages([
                'amount' => 'Minimum deposit is '.number_format($minDeposit, 2).' USD.',
            ]);
        }

        $orderId = 'deposit-'.$user->id.'-'.now()->timestamp;
        $gateway = $this->createGatewayPayment($user, $amount, $orderId, $currency, $network);
        $paymentId = (string) ($gateway['payment_id'] ?? '');
        $invoiceId = (string) ($gateway['id'] ?? $gateway['invoice_id'] ?? '');
        $payCurrency = $currency->nowPaymentsSlugForNetwork($network);

        $deposit = DB::transaction(function () use ($user, $amount, $gateway, $paymentId, $invoiceId, $orderId, $currency, $network, $payCurrency) {
            $deposit = Deposit::query()->create([
                'user_id' => $user->id,
                'amount' => $amount,
                'credit_currency' => $currency->value,
                'order_id' => $orderId,
                'payment_id' => $paymentId !== '' ? $paymentId : ($invoiceId !== '' ? $invoiceId : null),
                'purchase_id' => $invoiceId !== '' ? $invoiceId : ($paymentId !== '' ? $paymentId : null),
                'payment_url' => $gateway['payment_url'] ?? $gateway['invoice_url'] ?? null,
                'payment_status' => strtolower((string) ($gateway['payment_status'] ?? 'waiting')),
                'pay_currency' => strtolower((string) ($gateway['pay_currency'] ?? $payCurrency)),
                'pay_address' => $gateway['pay_address'] ?? null,
                'network' => $currency->networkLabel($network),
                'deposited_at' => now(),
                'status' => DepositStatus::Pending,
            ]);

            Transaction::query()->create([
                'user_id' => $user->id,
                'type' => TransactionType::Deposit,
                'amount' => $amount,
                'reference_type' => Deposit::class,
                'reference_id' => $deposit->id,
                'description' => 'Deposit '.$currency->value.' (Pending)',
                'status' => 'pending',
                'metadata' => [
                    'currency' => $currency->value,
                    'network' => $network,
                    'pay_currency' => $gateway['pay_currency'] ?? null,
                    'pay_amount' => $gateway['pay_amount'] ?? null,
                ],
            ]);

            $this->notifications->notifyUser(
                $user,
                'deposit',
                'Deposit Submitted',
                'Your '.$currency->value.' deposit (~'.number_format($amount, 2).' USD) is pending. Complete payment on NOWPayments.',
            );

            return $deposit->fresh();
        });

        $this->dojah->monitorUserEvent($user, 'payments', 'deposit', [
            'id' => (string) $deposit->id,
            'time' => now()->toIso8601String(),
            'amount' => $amount,
            'currency' => $currency->value,
            'ref' => $orderId,
            'type' => 'deposit',
            'channel' => 'online',
            'purpose' => 'wallet deposit',
        ]);

        return $deposit;
    }

    public function syncPendingDepositsForUser(User $user): void
    {
        $user->pendingDeposits()
            ->whereNotNull('purchase_id')
            ->latest()
            ->limit(10)
            ->get()
            ->each(function (Deposit $deposit): void {
                try {
                    $this->syncDeposit($deposit);
                } catch (\Throwable $e) {
                    Log::info('Pending deposit sync skipped.', [
                        'deposit_id' => $deposit->id,
                        'message' => $e->getMessage(),
                    ]);
                }
            });
    }

    public function syncDeposit(Deposit $deposit): void
    {
        $paymentData = $this->fetchPaymentDataForDeposit($deposit);

        if ($paymentData === null) {
            return;
        }

        $deposit = $this->applyPaymentData($deposit, $paymentData);
        $paymentStatus = strtolower((string) ($paymentData['payment_status'] ?? $paymentData['status'] ?? ''));

        if (! in_array($paymentStatus, ['finished', 'confirmed'], true)) {
            return;
        }

        if ($deposit->status !== DepositStatus::Pending) {
            return;
        }

        try {
            $this->approve($deposit->fresh(), null);
        } catch (\Throwable $e) {
            Log::info('Deposit auto-approval skipped.', [
                'deposit_id' => $deposit->id,
                'message' => $e->getMessage(),
            ]);
        }
    }

    public function attachGatewayPaymentId(Deposit $deposit, string $paymentId): Deposit
    {
        $paymentId = trim($paymentId);

        if ($paymentId === '') {
            return $deposit;
        }

        $deposit->update(['payment_id' => $paymentId]);

        return $deposit->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function handlePaymentNotification(array $data): ?Deposit
    {
        $paymentId = (string) ($data['payment_id'] ?? $data['id'] ?? '');
        $invoiceId = (string) ($data['invoice_id'] ?? $data['iid'] ?? '');
        $orderId = (string) ($data['order_id'] ?? '');

        if ($paymentId === '' && $invoiceId === '' && $orderId === '') {
            return null;
        }

        $deposit = Deposit::query()
            ->where(function ($query) use ($paymentId, $invoiceId, $orderId) {
                if ($orderId !== '') {
                    $query->where('order_id', $orderId);
                }

                if ($paymentId !== '') {
                    $query->orWhere('payment_id', $paymentId)
                        ->orWhere('purchase_id', $paymentId);
                }

                if ($invoiceId !== '') {
                    $query->orWhere('purchase_id', $invoiceId)
                        ->orWhere('payment_id', $invoiceId);
                }
            })
            ->latest()
            ->first();

        if (! $deposit) {
            Log::warning('NowPayments IPN received for unknown payment.', [
                'payment_id' => $paymentId,
                'invoice_id' => $invoiceId,
                'order_id' => $orderId,
            ]);

            return null;
        }

        $deposit = $this->applyPaymentData($deposit, $data);
        $paymentStatus = strtolower((string) ($data['payment_status'] ?? $data['status'] ?? ''));

        if (in_array($paymentStatus, ['finished', 'confirmed'], true)) {
            try {
                $this->approve($deposit->fresh(), null);
            } catch (\Throwable $e) {
                Log::info('NowPayments IPN deposit approval skipped.', [
                    'deposit_id' => $deposit->id,
                    'message' => $e->getMessage(),
                ]);
            }
        }

        return $deposit->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function applyPaymentData(Deposit $deposit, array $data): Deposit
    {
        $paymentId = (string) ($data['payment_id'] ?? $data['id'] ?? '');
        $paymentStatus = strtolower((string) ($data['payment_status'] ?? $data['status'] ?? ''));
        $payCurrency = strtolower((string) ($data['pay_currency'] ?? ''));

        $deposit->update([
            'payment_id' => $paymentId !== '' ? $paymentId : $deposit->payment_id,
            'payment_status' => $paymentStatus !== '' ? $paymentStatus : $deposit->payment_status,
            'pay_currency' => $payCurrency !== '' ? $payCurrency : $deposit->pay_currency,
            'network' => $data['network'] ?? $deposit->network,
            'pay_address' => $data['pay_address'] ?? $deposit->pay_address,
        ]);

        return $deposit->fresh();
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchPaymentDataForDeposit(Deposit $deposit): ?array
    {
        $paymentId = (string) $deposit->payment_id;
        $invoiceId = (string) $deposit->purchase_id;

        if ($paymentId !== '' && $paymentId !== $invoiceId) {
            $data = $this->fetchPaymentData($paymentId);

            if ($data !== null) {
                return $data;
            }
        }

        if ($invoiceId !== '') {
            return $this->fetchPaymentByInvoiceId($invoiceId);
        }

        if ($paymentId !== '') {
            return $this->fetchPaymentData($paymentId);
        }

        return null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchPaymentByInvoiceId(string $invoiceId): ?array
    {
        $apiKey = (string) NowPaymentsSettings::apiKey();
        $baseUrl = NowPaymentsSettings::baseUrl();

        if ($apiKey === '') {
            return null;
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'Accept' => 'application/json',
        ])->get($baseUrl.'/v1/payment/', [
            'invoiceid' => $invoiceId,
            'limit' => 10,
            'page' => 0,
            'sortBy' => 'created_at',
            'orderBy' => 'desc',
        ]);

        if (! $response->successful()) {
            return null;
        }

        $payments = $response->json('data');

        if (! is_array($payments) || $payments === []) {
            return null;
        }

        return $this->bestPaymentFromList($payments);
    }

    /**
     * @param  list<array<string, mixed>>  $payments
     * @return array<string, mixed>|null
     */
    private function bestPaymentFromList(array $payments): ?array
    {
        $priority = ['finished', 'confirmed', 'confirming', 'partially_paid', 'waiting'];

        usort($payments, function (array $left, array $right) use ($priority): int {
            $leftStatus = strtolower((string) ($left['payment_status'] ?? ''));
            $rightStatus = strtolower((string) ($right['payment_status'] ?? ''));
            $leftRank = array_search($leftStatus, $priority, true);
            $rightRank = array_search($rightStatus, $priority, true);
            $leftRank = $leftRank === false ? count($priority) : $leftRank;
            $rightRank = $rightRank === false ? count($priority) : $rightRank;

            return $leftRank <=> $rightRank;
        });

        return $payments[0] ?? null;
    }

    /**
     * @return array<string, mixed>|null
     */
    private function fetchPaymentData(string $paymentId): ?array
    {
        if ($paymentId === '') {
            return null;
        }

        $apiKey = NowPaymentsSettings::apiKey();
        $baseUrl = NowPaymentsSettings::baseUrl();

        if (! $apiKey) {
            return null;
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'Accept' => 'application/json',
        ])->get($baseUrl.'/v1/payment/'.$paymentId);

        if (! $response->successful()) {
            return null;
        }

        $data = $response->json();

        return is_array($data) ? $data : null;
    }

    /**
     * @return array<string, mixed>
     */
    private function createGatewayPayment(User $user, float $amount, string $orderId, WalletCurrency $currency, ?string $network): array
    {
        if (! $this->nowPayments->isConfigured()) {
            throw ValidationException::withMessages([
                'amount' => 'NowPayments API key is not configured.',
            ]);
        }

        $payCurrency = $currency->nowPaymentsSlugForNetwork($network);
        $usdAmount = max($amount, 5.0);

        try {
            $estimate = $this->nowPayments->estimate($usdAmount, 'usd', $payCurrency);
            $estimated = (float) ($estimate['estimated_amount'] ?? 0);

            if ($estimated <= 0) {
                throw ValidationException::withMessages([
                    'currency' => 'This currency/network is not available for deposit right now. Try TRC20 USDT.',
                ]);
            }
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::warning('NOWPayments deposit estimate failed', [
                'currency' => $currency->value,
                'network' => $network,
                'pay_currency' => $payCurrency,
                'amount' => $usdAmount,
                'message' => $e->getMessage(),
            ]);

            throw ValidationException::withMessages([
                'currency' => 'Cannot get payment estimate for '.$currency->value.' ('.$currency->networkLabel($network).'). Choose another network or coin.',
            ]);
        }

        $payload = [
            'price_amount' => $usdAmount,
            'price_currency' => 'usd',
            'pay_currency' => $payCurrency,
            'order_id' => $orderId,
            'order_description' => 'One-Contract deposit for '.$user->name.' ('.$currency->value.' '.$currency->networkLabel($network).')',
            'ipn_callback_url' => NowPaymentsSettings::ipnCallbackUrl(),
            'success_url' => NowPaymentsSettings::successUrl()
                ?: route('user.deposit.return', ['order_id' => $orderId], absolute: true),
            'cancel_url' => NowPaymentsSettings::cancelUrl()
                ?: route('user.deposit.index', ['cancelled' => 1], absolute: true),
            'is_fixed_rate' => true,
        ];

        try {
            $data = $this->nowPayments->createInvoice($payload);
        } catch (ValidationException $e) {
            $messages = $e->errors();
            $first = $messages['conversion'][0] ?? $messages['amount'][0] ?? null;

            throw ValidationException::withMessages([
                'amount' => $first ?: 'Unable to create payment session. Check amount and network, then try again.',
            ]);
        }

        if (! is_array($data)) {
            $data = [];
        }

        $data['pay_currency'] = $data['pay_currency'] ?? $payCurrency;
        $data['payment_url'] = $data['invoice_url'] ?? $data['payment_url'] ?? null;
        $data['pay_amount'] = $data['pay_amount'] ?? $estimated;

        if (empty($data['payment_url']) && empty($data['pay_address'])) {
            Log::warning('NOWPayments invoice invalid response', ['body' => $data]);

            throw ValidationException::withMessages([
                'amount' => 'Payment gateway returned an invalid response.',
            ]);
        }

        return $data;
    }

    /**
     * @return array<string, mixed>
     * @deprecated Use createGatewayPayment()
     */
    private function createInvoice(User $user, float $amount, string $orderId, ?WalletCurrency $preferPay = null): array
    {
        $apiKey = NowPaymentsSettings::apiKey();
        $baseUrl = NowPaymentsSettings::baseUrl();

        if (! $apiKey) {
            throw ValidationException::withMessages([
                'amount' => 'NowPayments API key is not configured.',
            ]);
        }

        $payload = [
            'price_amount' => max($amount, 5.0),
            'price_currency' => 'usd',
            'order_id' => $orderId,
            'order_description' => 'One-Contract deposit for '.$user->name.($preferPay ? ' ('.$preferPay->value.')' : ''),
            'ipn_callback_url' => NowPaymentsSettings::ipnCallbackUrl(),
            'success_url' => NowPaymentsSettings::successUrl()
                ?: route('user.deposit.return', ['order_id' => $orderId], absolute: true),
            'cancel_url' => NowPaymentsSettings::cancelUrl()
                ?: route('user.deposit.index', ['cancelled' => 1], absolute: true),
        ];

        if ($preferPay && $preferPay->isCrypto()) {
            $payload['pay_currency'] = $preferPay->nowPaymentsCurrency() ?? strtolower($preferPay->value);
        }

        $response = Http::withHeaders([
            'x-api-key' => $apiKey,
            'Accept' => 'application/json',
            'Content-Type' => 'application/json',
        ])->post($baseUrl.NowPaymentsSettings::checkoutEndpoint(), $payload);

        if (! $response->successful()) {
            Log::warning('NowPayments invoice failed', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            throw ValidationException::withMessages([
                'amount' => 'Unable to create payment session right now. Please try again.',
            ]);
        }

        $data = $response->json();

        if (! is_array($data) || empty($data['invoice_url'])) {
            throw ValidationException::withMessages([
                'amount' => 'Payment gateway returned an invalid response.',
            ]);
        }

        return $data;
    }

    public function approve(Deposit $deposit, ?User $admin): Deposit
    {
        if ($deposit->status !== DepositStatus::Pending) {
            throw ValidationException::withMessages([
                'deposit' => 'This deposit cannot be approved.',
            ]);
        }

        return DB::transaction(function () use ($deposit, $admin) {
            $deposit->update([
                'status' => DepositStatus::Completed,
                'approved_at' => now(),
                'approved_by' => $admin?->id,
            ]);

            $deposit->user->transactions()
                ->where('reference_type', Deposit::class)
                ->where('reference_id', $deposit->id)
                ->update([
                    'status' => 'completed',
                    'description' => 'Deposit credited',
                ]);

            $currency = WalletCurrency::tryNormalize($deposit->credit_currency)
                ?? WalletCurrency::tryNormalize($deposit->pay_currency)
                ?? WalletCurrency::Usdt;

            if (! $currency->isCrypto()) {
                $currency = WalletCurrency::Usdt;
            }

            $creditAmount = $this->creditAmountForDeposit($deposit, $currency);

            $this->wallets->credit(
                $deposit->user,
                $currency,
                $creditAmount,
                TransactionType::CryptoDeposit,
                'Deposit credited to '.$currency->value.' wallet',
                $deposit,
                ['usd_amount' => (float) $deposit->amount],
            );

            $treasury = \App\Models\Treasury::query()->first();

            if ($treasury) {
                $treasury->increment('balance', $deposit->amount);
            }

            $this->notifications->notifyUser(
                $deposit->user,
                'deposit',
                'Deposit Confirmed',
                'Your deposit has been credited: '.$currency->format($creditAmount).' (~'.number_format((float) $deposit->amount, 2).' USD).',
            );

            return $deposit->fresh();
        });
    }

    private function creditAmountForDeposit(Deposit $deposit, WalletCurrency $currency): float
    {
        $usdAmount = (float) $deposit->amount;

        if ($currency === WalletCurrency::Usdt) {
            return round($usdAmount, $currency->decimals());
        }

        $creditAmount = app(WalletLiveRateService::class)->cryptoAmountForUsd($currency, $usdAmount);

        if ($creditAmount <= 0) {
            return round($usdAmount, $currency->decimals());
        }

        return $creditAmount;
    }

    public function reject(Deposit $deposit, ?User $admin, ?string $notes = null): Deposit
    {
        if ($deposit->status !== DepositStatus::Pending) {
            throw ValidationException::withMessages([
                'deposit' => 'This deposit cannot be rejected.',
            ]);
        }

        return DB::transaction(function () use ($deposit, $admin, $notes) {
            $processedAt = now();

            $deposit->update([
                'status' => DepositStatus::Rejected,
                'approved_at' => $processedAt,
                'approved_by' => $admin?->id,
            ]);

            $deposit->user->transactions()
                ->where('reference_type', Deposit::class)
                ->where('reference_id', $deposit->id)
                ->update([
                    'status' => 'rejected',
                    'description' => $notes ? 'Deposit (Rejected: '.$notes.')' : 'Deposit (Rejected)',
                ]);

            $this->notifications->notifyUser(
                $deposit->user,
                'deposit',
                'Deposit Rejected',
                $notes ?: 'Your deposit could not be confirmed. Please contact support.',
            );

            return $deposit->fresh();
        });
    }
}
