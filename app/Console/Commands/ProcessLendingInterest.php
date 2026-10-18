<?php

namespace App\Console\Commands;

use App\Services\LendingPlanService;
use Illuminate\Console\Command;

class ProcessLendingInterest extends Command
{
    protected $signature = 'lending:process-interest';

    protected $description = 'Accrue daily interest on active lending plans and mark matured plans';

    public function handle(LendingPlanService $lending): int
    {
        $processed = $lending->processDailyInterest();
        $this->info("Processed {$processed} lending interest accrual(s).");

        return self::SUCCESS;
    }
}
