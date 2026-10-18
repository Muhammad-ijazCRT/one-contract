<?php

namespace App\Console\Commands;

use App\Services\SavingsPlanService;
use Illuminate\Console\Command;

class ProcessSavingsEarlyReturns extends Command
{
    protected $signature = 'savings:process-early-returns';

    protected $description = 'Credit ready Savings Plan early/maturity capital returns to Main Wallet';

    public function handle(SavingsPlanService $savings): int
    {
        $early = $savings->completeReadyEarlyReturns();
        $maturity = $savings->completeReadyMaturityReturns();

        $this->info("Completed {$early} early and {$maturity} maturity savings return(s).");

        return self::SUCCESS;
    }
}
