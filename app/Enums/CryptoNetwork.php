<?php

namespace App\Enums;

enum CryptoNetwork: string
{
    case Trc20 = 'TRC20';
    case Erc20 = 'ERC20';
    case Bep20 = 'BEP20';
    case Polygon = 'POLYGON';
    case Solana = 'SOLANA';
    case Arbitrum = 'ARBITRUM';
    case Optimism = 'OPTIMISM';
    case Avalanche = 'AVALANCHE';
    case Ton = 'TON';

    public function label(): string
    {
        return match ($this) {
            self::Trc20 => 'TRC20 (Tron)',
            self::Erc20 => 'ERC20 (Ethereum)',
            self::Bep20 => 'BEP20 (BNB Smart Chain)',
            self::Polygon => 'Polygon (MATIC)',
            self::Solana => 'Solana (SPL)',
            self::Arbitrum => 'Arbitrum One',
            self::Optimism => 'Optimism',
            self::Avalanche => 'Avalanche C-Chain',
            self::Ton => 'TON',
        };
    }

    /**
     * @return array<int, string>
     */
    public static function values(): array
    {
        return array_map(fn (self $case) => $case->value, self::cases());
    }
}
