<?php

namespace App\Enums;

enum TermsDocument: string
{
    case InvestmentClubs = 'investment_clubs';
    case SavingsPlan = 'savings_plan';

    public function label(): string
    {
        return match ($this) {
            self::InvestmentClubs => 'Investment Schemes Terms & Conditions',
            self::SavingsPlan => 'Savings Plan Terms & Conditions',
        };
    }

    public function legalSlug(): string
    {
        return match ($this) {
            self::InvestmentClubs => 'investment-clubs-terms',
            self::SavingsPlan => 'savings-plan-terms',
        };
    }

    public function redirectRoute(): string
    {
        return match ($this) {
            self::InvestmentClubs => 'user.clubs.index',
            self::SavingsPlan => 'user.savings.index',
        };
    }
}
