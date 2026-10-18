<?php

namespace App\Models;

use App\Enums\AzuboguRoundStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AzuboguRound extends Model
{
    protected $fillable = [
        'round_number',
        'status',
        'server_seed',
        'server_seed_hash',
        'client_seed',
        'nonce',
        'multipliers',
        'total_bets',
        'total_payout',
        'total_players',
        'betting_ends_at',
        'round_ends_at',
        'started_at',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'status' => AzuboguRoundStatus::class,
            'multipliers' => 'array',
            'total_bets' => 'decimal:8',
            'total_payout' => 'decimal:8',
            'nonce' => 'integer',
            'total_players' => 'integer',
            'betting_ends_at' => 'datetime',
            'round_ends_at' => 'datetime',
            'started_at' => 'datetime',
            'settled_at' => 'datetime',
        ];
    }

    public function bets(): HasMany
    {
        return $this->hasMany(AzuboguBet::class);
    }

    /** @return array{0x: list<int>, 0.45x: list<int>, 1.40x: list<int>, 1.90x: list<int>} */
    public function groupedMultipliers(): array
    {
        $groups = [
            '0x' => [],
            '0.45x' => [],
            '1.40x' => [],
            '1.90x' => [],
        ];

        foreach (($this->multipliers ?? []) as $number => $mult) {
            $m = (float) $mult;
            $key = match (true) {
                abs($m - 1.9) < 0.001 => '1.90x',
                abs($m - 1.4) < 0.001 => '1.40x',
                abs($m - 0.45) < 0.001 => '0.45x',
                default => '0x',
            };
            $groups[$key][] = (int) $number;
        }

        foreach ($groups as &$nums) {
            sort($nums);
        }

        return $groups;
    }
}
