<?php

namespace App\Enums;

enum WalletCurrency: string
{
    case Btc = 'BTC';
    case Eth = 'ETH';
    case Usdt = 'USDT';
    case Bnb = 'BNB';
    case Xrp = 'XRP';
    case Sol = 'SOL';
    case Usdc = 'USDC';
    case Trx = 'TRX';
    case Doge = 'DOGE';
    case Ada = 'ADA';
    case Shib = 'SHIB';
    case Avax = 'AVAX';
    case Link = 'LINK';
    case Dot = 'DOT';
    case Pol = 'POL';
    case Ltc = 'LTC';
    case Uni = 'UNI';
    case Near = 'NEAR';
    case Atom = 'ATOM';
    case Apt = 'APT';
    case Ngn = 'NGN';

    /**
     * @return list<self>
     */
    public static function crypto(): array
    {
        return array_values(array_filter(self::cases(), fn (self $c) => $c->isCrypto()));
    }

    /**
     * @return list<self>
     */
    public static function all(): array
    {
        return self::cases();
    }

    /**
     * Display order for pickers (crypto only, client list order).
     *
     * @return list<self>
     */
    public static function selectOrder(): array
    {
        return self::crypto();
    }

    /**
     * Header currency picker: fiat + all cryptos.
     *
     * @return list<self>
     */
    public static function pickerOrder(): array
    {
        return [self::Ngn, ...self::crypto()];
    }

    public static function cryptoCodes(): string
    {
        return implode(',', array_map(fn (self $c) => $c->value, self::crypto()));
    }

    public static function allCodes(): string
    {
        return implode(',', array_map(fn (self $c) => $c->value, self::all()));
    }

    public function meta(): array
    {
        if ($this === self::Ngn) {
            return [
                'label' => 'Nigerian Naira',
                'decimals' => 2,
                'accent' => '#4caf50',
                'coingecko_id' => null,
                'nowpayments' => null,
            ];
        }

        return config('wallets.crypto_meta.'.$this->value, [
            'label' => $this->value,
            'decimals' => 8,
            'accent' => '#efff00',
            'coingecko_id' => null,
            'nowpayments' => strtolower($this->value),
        ]);
    }

    public function label(): string
    {
        return (string) ($this->meta()['label'] ?? $this->value);
    }

    public function shortLabel(): string
    {
        return $this === self::Ngn ? 'Naira' : $this->value;
    }

    public static function tryFromPayCurrency(?string $value): ?self
    {
        $pay = strtolower(preg_replace('/[^a-z0-9]/', '', (string) $value) ?? '');
        if ($pay === '') {
            return null;
        }

        $codes = ['usdt', 'usdc', 'btc', 'eth', 'bnb', 'xrp', 'sol', 'doge', 'ada', 'shib', 'avax', 'link', 'dot', 'pol', 'ltc', 'uni', 'near', 'atom', 'apt', 'trx', 'ngn'];
        foreach ($codes as $code) {
            if (str_starts_with($pay, $code) || str_contains($pay, $code)) {
                return self::tryNormalize($code);
            }
        }

        return null;
    }

    public function accentColor(): string
    {
        return (string) ($this->meta()['accent'] ?? '#efff00');
    }

    public function coingeckoId(): ?string
    {
        $id = $this->meta()['coingecko_id'] ?? null;

        return is_string($id) && $id !== '' ? $id : null;
    }

    public function nowPaymentsCurrency(): ?string
    {
        $slug = $this->meta()['nowpayments'] ?? null;

        return is_string($slug) && $slug !== '' ? $slug : null;
    }

    public function nowPaymentsSlugForNetwork(?string $network = null): string
    {
        $network = strtolower(trim((string) $network));
        $map = config('wallets.nowpayments_networks.'.$this->value, []);

        if ($network !== '' && is_array($map) && isset($map[$network])) {
            return (string) $map[$network];
        }

        return $this->nowPaymentsCurrency() ?? strtolower($this->value);
    }

    public function networkLabel(?string $network = null): string
    {
        $network = strtolower(trim((string) $network));

        return match ($network) {
            'trc20' => 'TRC20',
            'erc20' => 'ERC20',
            'bep20' => 'BEP20',
            'btc' => 'Bitcoin',
            'eth' => 'Ethereum',
            'trx' => 'TRON',
            default => $network !== '' ? strtoupper($network) : 'Default',
        };
    }

    public function symbol(): string
    {
        return match ($this) {
            self::Btc => '₿',
            self::Eth => 'Ξ',
            self::Usdt, self::Usdc => '$',
            self::Ngn => '₦',
            default => $this->value,
        };
    }

    public function decimals(): int
    {
        return (int) ($this->meta()['decimals'] ?? 8);
    }

    public function isCrypto(): bool
    {
        return $this !== self::Ngn;
    }

    public function iconUrl(): string
    {
        $file = strtolower($this->value).'.svg';
        $paths = [
            public_path('images/wallets/'.$file),
            public_path('images/deposit/'.$file),
        ];

        foreach ($paths as $path) {
            if (is_file($path)) {
                $url = str_contains($path, DIRECTORY_SEPARATOR.'deposit'.DIRECTORY_SEPARATOR)
                    ? asset('images/deposit/'.$file)
                    : asset('images/wallets/'.$file);

                return $url.'?v='.filemtime($path);
            }
        }

        return asset('images/wallets/crypto-generic.svg');
    }

    public function format(float $amount): string
    {
        $decimals = $this->decimals();
        $formatted = number_format($amount, $decimals);

        return match ($this) {
            self::Usdt, self::Usdc => '$'.$formatted,
            self::Ngn => '₦'.$formatted,
            default => $formatted.' '.$this->value,
        };
    }

    public function formatCompact(float $amount): string
    {
        $decimals = min($this->decimals(), $amount >= 1 ? 2 : 6);
        $formatted = rtrim(rtrim(number_format($amount, $decimals), '0'), '.');

        return match ($this) {
            self::Usdt, self::Usdc => $formatted.' '.$this->value,
            self::Ngn => '₦'.$formatted,
            default => $formatted.' '.$this->value,
        };
    }

    /** Map legacy USD / main wallet naming → USDT */
    public static function normalize(string $value): self
    {
        $value = strtoupper(trim($value));

        return match ($value) {
            'USD', 'USDT', 'MAIN' => self::Usdt,
            'BTC', 'BITCOIN' => self::Btc,
            'ETH', 'ETHEREUM' => self::Eth,
            'BNB' => self::Bnb,
            'XRP' => self::Xrp,
            'SOL', 'SOLANA' => self::Sol,
            'USDC' => self::Usdc,
            'TRX', 'TRON' => self::Trx,
            'DOGE', 'DOGECOIN' => self::Doge,
            'ADA', 'CARDANO' => self::Ada,
            'SHIB', 'SHIBA' => self::Shib,
            'AVAX', 'AVALANCHE' => self::Avax,
            'LINK', 'CHAINLINK' => self::Link,
            'DOT', 'POLKADOT' => self::Dot,
            'POL', 'MATIC', 'POLYGON' => self::Pol,
            'LTC', 'LITECOIN' => self::Ltc,
            'UNI', 'UNISWAP' => self::Uni,
            'NEAR' => self::Near,
            'ATOM', 'COSMOS' => self::Atom,
            'APT', 'APTOS' => self::Apt,
            'NGN', 'NAIRA' => self::Ngn,
            default => throw new \InvalidArgumentException('Unsupported wallet currency: '.$value),
        };
    }

    public static function tryNormalize(?string $value): ?self
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        try {
            return self::normalize($value);
        } catch (\InvalidArgumentException) {
            return null;
        }
    }
}
