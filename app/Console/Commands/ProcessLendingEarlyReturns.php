<?php

namespace App\Console\Commands;

use App\Services\LendingPlanService;
use Illuminate\Console\Command;

class ProcessLendingEarlyReturns extends Command
{
    protected $signature = 'lending:process-early-returns';

    protected $description = 'Complete ready early and maturity lending principal returns';

    public function handle(LendingPlanService $lending): int
    {
        $early = $lending->completeReadyEarlyReturns();
        $maturity = $lending->completeReadyMaturityReturns();
        $this->info("Completed {$early} early and {$maturity} maturity lending return(s).");

        return self::SUCCESS;
    }
}
