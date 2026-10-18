<?php

namespace App\Enums;

enum TransactionType: string
{
    case Deposit = 'deposit';
    case DailyReward = 'daily_reward';
    case ReferralCommission = 'referral_commission';
    case RewardWithdrawal = 'reward_withdrawal';
    case CapitalWithdrawal = 'capital_withdrawal';
    case AdminAction = 'admin_action';

    // Dual-wallet + clubs + savings
    case MainWalletCredit = 'main_wallet_credit';
    case MainWalletDebit = 'main_wallet_debit';
    case ClubInvestment = 'club_investment';
    case ClubInvestmentIncrease = 'club_investment_increase';
    case ClubReward = 'club_reward';
    case ClubCapitalReduce = 'club_capital_reduce';
    case ClubCapitalReturn = 'club_capital_return';
    case SavingsDeduction = 'savings_deduction';
    case SavingsCompletion = 'savings_completion';
    case SavingsEarlyWithdrawal = 'savings_early_withdrawal';
    case SavingsPrincipalLock = 'savings_principal_lock';
    case SavingsInterest = 'savings_interest';
    case SavingsPrincipalReturn = 'savings_principal_return';
    case PlatformFee = 'platform_fee';
    case RewardClaim = 'reward_claim';
    case LendingPrincipalLock = 'lending_principal_lock';
    case LendingInterest = 'lending_interest';
    case LendingPrincipalReturn = 'lending_principal_return';
    case PredictionBet = 'prediction_bet';
    case PredictionBetRefund = 'prediction_bet_refund';
    case PredictionWin = 'prediction_win';
    case AzuboguBet = 'azubogu_bet';
    case AzuboguBetRefund = 'azubogu_bet_refund';
    case AzuboguWin = 'azubogu_win';
    case LuckyDiceBet = 'lucky_dice_bet';
    case LuckyDiceWin = 'lucky_dice_win';
    case FiatOnRamp = 'fiat_on_ramp';
    case FiatOffRampHold = 'fiat_off_ramp_hold';
    case FiatOffRampRefund = 'fiat_off_ramp_refund';
    case RpsvStake = 'rpsv_stake';
    case RpsvStakeRefund = 'rpsv_stake_refund';
    case RpsvWin = 'rpsv_win';
    case WalletConvertOut = 'wallet_convert_out';
    case WalletConvertIn = 'wallet_convert_in';
    case CryptoDeposit = 'crypto_deposit';
    case CryptoWithdrawal = 'crypto_withdrawal';
}
