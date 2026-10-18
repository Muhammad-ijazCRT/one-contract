<?php

namespace App\Enums;

enum InvestmentClubSlug: string
{
    case Binary = 'binary';
    case Crypto = 'crypto';
    case Forex = 'forex';
    case Casino = 'casino';
    case Polymarket = 'polymarket';

    public function label(): string
    {
        return match ($this) {
            self::Binary => 'Lion Investment Scheme',
            self::Crypto => 'Eagle Investment Scheme',
            self::Forex => 'Man Investment Scheme',
            self::Casino => 'Ox Investment Scheme',
            self::Polymarket => 'Gorilla Investment Scheme',
        };
    }
}
