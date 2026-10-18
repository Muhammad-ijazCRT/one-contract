<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class DualWalletScheduleStatus extends Command
{
    protected $signature = 'dual-wallet:schedule-status';

    protected $description = 'Verify dual-wallet scheduled commands are registered';

    public function handle(): int
    {
        $required = [
            'clubs:process-rewards',
            'clubs:process-capital-returns',
            'savings:process-daily',
            'savings:process-early-returns',
        ];

        Artisan::call('schedule:list');
        $listed = Artisan::output();

        $this->line($listed);

        $missing = [];

        foreach ($required as $command) {
            if (! str_contains($listed, $command)) {
                $missing[] = $command;
            }
        }

        if ($missing !== []) {
            $this->error('Missing scheduled commands: '.implode(', ', $missing));

            return self::FAILURE;
        }

        $this->info('All dual-wallet scheduled commands are registered.');

        return self::SUCCESS;
    }
}
