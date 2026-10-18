<?php

namespace App\Enums;

enum RewardType: string
{
    case DailyReward = 'daily_reward';
    /** @deprecated Prefer GamingReferral / InvestmentSchemeReferral */
    case ReferralCommission = 'referral_commission';
    case ClubReward = 'club_reward';
    case SavingsInterest = 'savings_interest';
    case LendingInterest = 'lending_interest';
    case GamingReferral = 'gaming_referral';
    case InvestmentSchemeReferral = 'investment_scheme_referral';

    /** @return list<self> */
    public static function referralTypes(): array
    {
        return [
            self::ReferralCommission,
            self::GamingReferral,
            self::InvestmentSchemeReferral,
        ];
    }

    /** @return list<string> */
    public static function referralTypeValues(): array
    {
        return array_map(fn (self $t) => $t->value, self::referralTypes());
    }
}
