<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

class DualWalletSmokeTest extends Command
{
    protected $signature = 'dual-wallet:smoke-test';

    protected $description = 'Run dual-wallet end-to-end Feature tests (invest, reward, reduce 72h, savings, pages)';

    public function handle(): int
    {
        $this->info('Running dual-wallet smoke tests...');

        $process = new Process([
            PHP_BINARY,
            'artisan',
            'test',
            '--filter=DualWalletFlowTest',
        ], base_path());

        $process->setTimeout(120);
        $process->run(function (string $type, string $buffer): void {
            $this->output->write($buffer);
        });

        if (! $process->isSuccessful()) {
            $this->error('Dual-wallet smoke tests failed.');

            return self::FAILURE;
        }

        $this->info('Dual-wallet smoke tests passed.');
        $this->line('Covered: Main Wallet · Club invest/reward/reduce/return · Savings deduct/early/2X · Auth pages');

        return self::SUCCESS;
    }
}
