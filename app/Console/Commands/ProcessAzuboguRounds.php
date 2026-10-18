<?php

namespace App\Console\Commands;

use App\Services\AzuboguGameService;
use Illuminate\Console\Command;

class ProcessAzuboguRounds extends Command
{
    protected $signature = 'azubogu:tick';

    protected $description = 'Advance Azubogu Prediction Game rounds (betting lock, settle, next round)';

    public function handle(AzuboguGameService $game): int
    {
        $round = $game->tick();

        if (! $round) {
            $this->info('No live Azubogu round.');

            return self::SUCCESS;
        }

        $this->info('Azubogu round #'.$round->round_number.' status='.$round->status->value);

        return self::SUCCESS;
    }
}
