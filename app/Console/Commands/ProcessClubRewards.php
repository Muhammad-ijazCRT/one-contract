<?php

namespace App\Console\Commands;

use App\Services\ClubRewardProcessor;
use Illuminate\Console\Command;

class ProcessClubRewards extends Command
{
    protected $signature = 'clubs:process-rewards {--date=}';

    protected $description = 'Credit due investment club rewards to Main Wallet';

    public function handle(ClubRewardProcessor $processor): int
    {
        $date = $this->option('date')
            ? \Illuminate\Support\Carbon::parse($this->option('date'))
            : now();

        $count = $processor->process($date);

        $this->info("Processed {$count} club reward(s) as of ".$date->toDateTimeString().'.');

        return self::SUCCESS;
    }
}
