<?php

namespace App\Enums;

enum LendingPlanType: string
{
    case Regular = 'regular';
    case Premium = 'premium';

    public function label(): string
    {
        return match ($this) {
            self::Regular => 'Regular Lending',
            self::Premium => 'Premium Lending',
        };
    }
}
