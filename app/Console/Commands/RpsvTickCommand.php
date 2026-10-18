<?php

namespace App\Console\Commands;

use App\Services\RpsvGameService;
use Illuminate\Console\Command;

class RpsvTickCommand extends Command
{
    protected $signature = 'rpsv:tick';

    protected $description = 'Advance RPSV matchmaking, rounds, and invite expiry';

    public function handle(RpsvGameService $game): int
    {
        $game->tick();

        return self::SUCCESS;
    }
}
