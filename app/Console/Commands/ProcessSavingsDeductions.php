<?php

namespace App\Console\Commands;

use App\Services\SavingsPlanService;
use Illuminate\Console\Command;

class ProcessSavingsDeductions extends Command
{
    protected $signature = 'savings:process-daily {--date=}';

    protected $description = 'Process daily Savings Plan deductions from Main Wallet (catch-up when no --date)';

    public function handle(SavingsPlanService $savings): int
    {
        $date = $this->option('date')
            ? \Illuminate\Support\Carbon::parse($this->option('date'))
            : null;

        $count = $savings->processDailyDeductions($date);

        $this->info("Processed {$count} savings deduction event(s)"
            .($date ? ' for '.$date->toDateString() : ' (catch-up through today)')
            .'.');

        return self::SUCCESS;
    }
}
