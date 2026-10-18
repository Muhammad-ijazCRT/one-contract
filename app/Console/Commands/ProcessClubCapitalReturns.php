<?php

namespace App\Console\Commands;

use App\Services\ClubInvestmentService;
use Illuminate\Console\Command;

class ProcessClubCapitalReturns extends Command
{
    protected $signature = 'clubs:process-capital-returns';

    protected $description = 'Return completed 72h club capital reductions to Main Wallet';

    public function handle(ClubInvestmentService $clubs): int
    {
        $count = $clubs->completeReadyCapitalReturns();

        $this->info("Completed {$count} club capital return(s).");

        return self::SUCCESS;
    }
}
