<?php

namespace App\Services;

use App\Enums\TransactionType;
use App\Enums\WalletCurrency;
use App\Models\FiatRequest;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class MonnifyService
{
    public function __construct(
        private readonly MonnifyClient $client,
        private readonly MultiWalletService $wallets,
    ) {}

    public function initiateDeposit(User $user, float $amount): FiatRequest
    {
        if (! MonnifySettings::depositsEnabled()) {
            throw ValidationException::withMessages([
                'amount' => 'Monnify Naira deposits are currently disabled.',
            ]);
        }

        if (! MonnifySettings::hasCredentials()) {
            throw ValidationException::withMessages([
                'amount' => 'Monnify API credentials are missing. Please contact support.',
            ]);
        }

        if (MonnifySettings::contractCode() === '') {
            throw ValidationException::withMessages([
                'amount' => 'Monnify contract code is not configured yet. Admin must add it in Payment Settings.',
            ]);
        }

        if ($amount < 100) {
            throw ValidationException::withMessages([
                'amount' => 'Minimum Monnify deposit is ₦100.',
            ]);
        }

        $reference = $this->makeReference('MNFD', $user->id);

        $request = FiatRequest::query()->create([
            'user_id' => $user->id,
            'direction' => 'on_ramp',
            'amount' => $amount,
            'currency' => WalletCurrency::Ngn->value,
            'reference' => $reference,
            'status' => 'pending',
            'gateway' => 'monnify',
            'gateway_reference' => $reference,
            'gateway_status' => 'PENDING',
        ]);

        try {
            $response = $this->client->initTransaction([
                'amount' => round($amount, 2),
                'customerName' => $user->name ?: 'ONE CONTRACT User',
                'customerEmail' => $user->email,
                'paymentReference' => $reference,
                'paymentDescription' => 'ONE CONTRACT NGN wallet deposit',
                'currencyCode' => 'NGN',
                'contractCode' => MonnifySettings::contractCode(),
                'redirectUrl' => MonnifySettings::redirectUrl(),
                'paymentMethods' => ['CARD', 'ACCOUNT_TRANSFER'],
            ]);
        } catch (\Throwable $e) {
            $request->update([
                'status' => 'rejected',
                'gateway_status' => 'FAILED',
                'gateway_payload' => ['error' => $e->getMessage()],
            ]);

            throw ValidationException::withMessages([
                'amount' => 'Could not start Monnify payment: '.$e->getMessage(),
            ]);
        }

        $request->update([
            'gateway_reference' => (string) ($response['transactionReference'] ?? $reference),
            'checkout_url' => (string) ($response['checkoutUrl'] ?? ''),
            'gateway_status' => (string) ($response['paymentStatus'] ?? 'PENDING'),
            'gateway_payload' => $response,
        ]);

        return $request->fresh();
    }

    public function initiateWithdrawal(User $user, array $data): FiatRequest
    {
        if (! MonnifySettings::withdrawalsEnabled()) {
            throw ValidationException::withMessages([
                'amount' => 'Monnify Naira withdrawals are currently disabled.',
            ]);
        }

        if (! MonnifySettings::isConfigured() || MonnifySettings::walletAccountNumber() === '') {
            throw ValidationException::withMessages([
                'amount' => 'Monnify payout wallet is not configured. Please contact support.',
            ]);
        }

        $amount = (float) ($data['amount'] ?? 0);
        $bankCode = (string) ($data['bank_code'] ?? '');
        $accountNumber = (string) ($data['account_number'] ?? '');
        $accountName = (string) ($data['account_name'] ?? '');
        $bankName = (string) ($data['bank_name'] ?? '');

        if ($amount < 100) {
            throw ValidationException::withMessages([
                'amount' => 'Minimum Monnify withdrawal is ₦100.',
            ]);
        }

        if ($bankCode === '' || $accountNumber === '') {
            throw ValidationException::withMessages([
                'bank_code' => 'Please select a valid bank.',
            ]);
        }

        if ($accountName === '') {
            try {
                $validated = $this->client->validateAccount($accountNumber, $bankCode);
                $accountName = (string) ($validated['accountName'] ?? '');
            } catch (\Throwable) {
                throw ValidationException::withMessages([
                    'account_number' => 'Could not verify bank account. Check details and try again.',
                ]);
            }
        }

        return DB::transaction(function () use ($user, $amount, $bankCode, $accountNumber, $accountName, $bankName, $data) {
            $user = User::query()->whereKey($user->id)->lockForUpdate()->firstOrFail();

            if ($this->wallets->balance($user, WalletCurrency::Ngn) < $amount) {
                throw ValidationException::withMessages([
                    'amount' => 'Insufficient NGN balance.',
                ]);
            }

            $reference = $this->makeReference('MNFW', $user->id);

            $request = FiatRequest::query()->create([
                'user_id' => $user->id,
                'direction' => 'off_ramp',
                'amount' => $amount,
                'currency' => WalletCurrency::Ngn->value,
                'bank_name' => $bankName,
                'bank_code' => $bankCode,
                'account_name' => $accountName,
                'account_number' => $accountNumber,
                'reference' => $reference,
                'notes' => $data['notes'] ?? null,
                'status' => 'pending',
                'gateway' => 'monnify',
                'gateway_reference' => $reference,
                'gateway_status' => 'PENDING',
            ]);

            $this->wallets->debit(
                $user,
                WalletCurrency::Ngn,
                $amount,
                TransactionType::FiatOffRampHold,
                'Monnify withdrawal hold #'.$request->id,
                $request,
            );

            try {
                $response = $this->client->initiateSingleTransfer([
                    'amount' => round($amount, 2),
                    'reference' => $reference,
                    'narration' => 'ONE CONTRACT NGN withdrawal #'.$request->id,
                    'destinationBankCode' => $bankCode,
                    'destinationAccountNumber' => $accountNumber,
                    'destinationAccountName' => $accountName,
                    'currency' => 'NGN',
                    'sourceAccountNumber' => MonnifySettings::walletAccountNumber(),
                    'async' => true,
                ]);
            } catch (\Throwable $e) {
                $this->wallets->credit(
                    $user,
                    WalletCurrency::Ngn,
                    $amount,
                    TransactionType::FiatOffRampRefund,
                    'Monnify withdrawal refund #'.$request->id,
                    $request,
                );

                $request->update([
                    'status' => 'rejected',
                    'gateway_status' => 'FAILED',
                    'gateway_payload' => ['error' => $e->getMessage()],
                    'processed_at' => now(),
                ]);

                throw ValidationException::withMessages([
                    'amount' => 'Monnify payout failed: '.$e->getMessage(),
                ]);
            }

            $status = strtoupper((string) ($response['status'] ?? 'PENDING'));

            $request->update([
                'gateway_reference' => (string) ($response['reference'] ?? $reference),
                'gateway_status' => $status,
                'gateway_payload' => $response,
            ]);

            if (in_array($status, ['SUCCESS', 'COMPLETED'], true)) {
                $request->update([
                    'status' => 'completed',
                    'processed_at' => now(),
                ]);
            }

            return $request->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    public function handleWebhook(array $payload): void
    {
        $eventType = (string) ($payload['eventType'] ?? $payload['eventData']['eventType'] ?? '');
        $eventData = is_array($payload['eventData'] ?? null) ? $payload['eventData'] : $payload;

        if (in_array($eventType, ['SUCCESSFUL_TRANSACTION', 'SUCCESSFUL_DEPOSIT'], true)
            || strtoupper((string) ($eventData['paymentStatus'] ?? '')) === 'PAID') {
            $this->completeDepositFromGateway($eventData);

            return;
        }

        if (in_array($eventType, ['SUCCESSFUL_DISBURSEMENT', 'SUCCESSFUL_TRANSFER'], true)) {
            $this->completeWithdrawalFromGateway($eventData);

            return;
        }

        if (in_array($eventType, ['FAILED_DISBURSEMENT', 'FAILED_TRANSFER'], true)) {
            $this->failWithdrawalFromGateway($eventData);
        }
    }

    public function syncDepositFromCallback(string $paymentReference): ?FiatRequest
    {
        $request = FiatRequest::query()
            ->where('gateway', 'monnify')
            ->where('direction', 'on_ramp')
            ->where(function ($query) use ($paymentReference) {
                $query->where('reference', $paymentReference)
                    ->orWhere('gateway_reference', $paymentReference);
            })
            ->first();

        if (! $request || $request->status !== 'pending') {
            return $request;
        }

        try {
            $status = $this->client->getTransactionStatus($request->gateway_reference ?: $paymentReference);
            if (strtoupper((string) ($status['paymentStatus'] ?? '')) === 'PAID') {
                return $this->completeDepositFromGateway($status);
            }
        } catch (\Throwable $e) {
            report($e);
        }

        return $request;
    }

    /**
     * @return array<int, array{code: string, name: string}>
     */
    public function bankOptions(): array
    {
        try {
            $banks = $this->client->banks();

            return collect($banks)
                ->map(fn (array $bank) => [
                    'code' => (string) ($bank['code'] ?? ''),
                    'name' => (string) ($bank['name'] ?? ''),
                ])
                ->filter(fn (array $bank) => $bank['code'] !== '' && $bank['name'] !== '')
                ->sortBy('name')
                ->values()
                ->all();
        } catch (\Throwable) {
            return $this->fallbackBanks();
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function completeDepositFromGateway(array $data): ?FiatRequest
    {
        $reference = (string) ($data['paymentReference'] ?? $data['reference'] ?? '');

        if ($reference === '') {
            return null;
        }

        return DB::transaction(function () use ($reference, $data) {
            $request = FiatRequest::query()
                ->where('gateway', 'monnify')
                ->where('direction', 'on_ramp')
                ->where(function ($query) use ($reference) {
                    $query->where('reference', $reference)
                        ->orWhere('gateway_reference', $reference);
                })
                ->lockForUpdate()
                ->first();

            if (! $request || $request->status !== 'pending') {
                return $request;
            }

            $user = User::query()->whereKey($request->user_id)->lockForUpdate()->firstOrFail();
            $paidAmount = (float) ($data['amountPaid'] ?? $data['paidAmount'] ?? $request->amount);

            $this->wallets->credit(
                $user,
                WalletCurrency::Ngn,
                $paidAmount,
                TransactionType::FiatOnRamp,
                'Monnify deposit #'.$request->id,
                $request,
            );

            $request->update([
                'status' => 'completed',
                'gateway_status' => 'PAID',
                'processed_at' => now(),
                'gateway_payload' => array_merge((array) $request->gateway_payload, $data),
            ]);

            return $request->fresh();
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function completeWithdrawalFromGateway(array $data): ?FiatRequest
    {
        $reference = (string) ($data['reference'] ?? $data['paymentReference'] ?? '');

        if ($reference === '') {
            return null;
        }

        $request = FiatRequest::query()
            ->where('gateway', 'monnify')
            ->where('direction', 'off_ramp')
            ->where(function ($query) use ($reference) {
                $query->where('reference', $reference)
                    ->orWhere('gateway_reference', $reference);
            })
            ->first();

        if (! $request || $request->status !== 'pending') {
            return $request;
        }

        $request->update([
            'status' => 'completed',
            'gateway_status' => 'SUCCESS',
            'processed_at' => now(),
            'gateway_payload' => array_merge((array) $request->gateway_payload, $data),
        ]);

        return $request->fresh();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function failWithdrawalFromGateway(array $data): ?FiatRequest
    {
        $reference = (string) ($data['reference'] ?? $data['paymentReference'] ?? '');

        if ($reference === '') {
            return null;
        }

        return DB::transaction(function () use ($reference, $data) {
            $request = FiatRequest::query()
                ->where('gateway', 'monnify')
                ->where('direction', 'off_ramp')
                ->where(function ($query) use ($reference) {
                    $query->where('reference', $reference)
                        ->orWhere('gateway_reference', $reference);
                })
                ->lockForUpdate()
                ->first();

            if (! $request || $request->status !== 'pending') {
                return $request;
            }

            $user = User::query()->whereKey($request->user_id)->lockForUpdate()->firstOrFail();

            $this->wallets->credit(
                $user,
                WalletCurrency::Ngn,
                (float) $request->amount,
                TransactionType::FiatOffRampRefund,
                'Monnify withdrawal refund #'.$request->id,
                $request,
            );

            $request->update([
                'status' => 'rejected',
                'gateway_status' => 'FAILED',
                'processed_at' => now(),
                'gateway_payload' => array_merge((array) $request->gateway_payload, $data),
            ]);

            return $request->fresh();
        });
    }

    private function makeReference(string $prefix, int $userId): string
    {
        return strtoupper($prefix.'-'.$userId.'-'.now()->format('YmdHis').'-'.Str::upper(Str::random(6)));
    }

    /**
     * @return array<int, array{code: string, name: string}>
     */
    private function fallbackBanks(): array
    {
        return [
            ['code' => '044', 'name' => 'Access Bank'],
            ['code' => '058', 'name' => 'GTBank'],
            ['code' => '033', 'name' => 'UBA'],
            ['code' => '057', 'name' => 'Zenith Bank'],
            ['code' => '011', 'name' => 'First Bank'],
        ];
    }
}
