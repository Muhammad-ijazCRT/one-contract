<?php

namespace App\Console\Commands;

use App\Services\PredictionGameService;
use Illuminate\Console\Command;

class ProcessPredictionRounds extends Command
{
    protected $signature = 'prediction:tick';

    protected $description = 'Advance prediction game rounds (betting lock, settle, next round)';

    public function handle(PredictionGameService $game): int
    {
        $round = $game->tick();

        if (! $round) {
            $this->info('Idle — waiting for a bet to start the next round.');

            return self::SUCCESS;
        }

        $this->info('Round #'.$round->round_number.' status='.$round->status->value);

        return self::SUCCESS;
    }
}
