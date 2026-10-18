<?php

namespace App\Enums;

enum WithdrawalType: string
{
    case Reward = 'reward';
    case Capital = 'capital';

    public function label(): string
    {
        return match ($this) {
            self::Reward => 'Main Wallet',
            self::Capital => 'Capital',
        };
    }
}
