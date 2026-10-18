<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Services\MonnifyClient;
use App\Services\MonnifyService;
use App\Services\MonnifySettings;
use Illuminate\Console\Command;

class MonnifyTestCommand extends Command
{
    protected $signature = 'monnify:test
        {--email= : User email for a test deposit}
        {--amount=1000 : NGN amount}
        {--contract= : Sandbox contract code (if not saved in admin)}';

    protected $description = 'Test Monnify auth and optionally start a sandbox NGN deposit';

    public function handle(MonnifyClient $client, MonnifyService $monnify): int
    {
        if ($contract = $this->option('contract')) {
            MonnifySettings::update([
                'mode' => MonnifySettings::MODE_SANDBOX,
                'enabled' => true,
                'deposits_enabled' => true,
                'withdrawals_enabled' => true,
                'sandbox_api_key' => MonnifySettings::apiKey(),
                'sandbox_base_url' => MonnifySettings::baseUrl(),
                'live_base_url' => 'https://api.monnify.com',
                'sandbox_contract_code' => $contract,
            ]);
        }

        $this->info('Mode: '.MonnifySettings::mode());
        $this->info('Base URL: '.MonnifySettings::baseUrl());

        try {
            $client->authenticate();
            $this->info('Authentication: OK');
        } catch (\Throwable $e) {
            $this->error('Authentication failed: '.$e->getMessage());

            return self::FAILURE;
        }

        if (! MonnifySettings::contractCode()) {
            $this->warn('Contract code missing. Add it in Admin → Payment Settings → Monnify, or pass --contract=YOUR_CODE');

            return self::FAILURE;
        }

        $email = (string) $this->option('email');
        if ($email === '') {
            $this->line('Skipping deposit test (pass --email=user@example.com to test checkout).');

            return self::SUCCESS;
        }

        $user = User::query()->where('email', $email)->first();
        if (! $user) {
            $this->error('User not found: '.$email);

            return self::FAILURE;
        }

        try {
            $request = $monnify->initiateDeposit($user, (float) $this->option('amount'));
            $this->info('Deposit reference: '.$request->reference);
            $this->info('Checkout URL: '.($request->checkout_url ?: 'n/a'));

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error('Deposit test failed: '.$e->getMessage());

            return self::FAILURE;
        }
    }
}
