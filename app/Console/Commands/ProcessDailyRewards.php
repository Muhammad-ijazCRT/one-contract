<?php

namespace App\Console\Commands;

use App\Services\DailyRewardProcessor;
use Illuminate\Console\Command;

class ProcessDailyRewards extends Command
{
    protected $signature = 'rewards:process-daily {--date=}';

    protected $description = 'Credit daily rewards (rate configured in platform settings) for all active deposits';

    public function handle(DailyRewardProcessor $processor): int
    {
        $date = $this->option('date')
            ? \Illuminate\Support\Carbon::parse($this->option('date'))
            : now();

        $count = $processor->process($date);

        $this->info("Processed {$count} daily reward(s) for ".$date->toDateString().'.');

        return self::SUCCESS;
    }
}
