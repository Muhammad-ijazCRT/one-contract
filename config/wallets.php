<?php

$cryptoMeta = [
    'BTC' => ['label' => 'Bitcoin', 'coingecko_id' => 'bitcoin', 'decimals' => 8, 'accent' => '#f7931a', 'min_convert' => 0.0001, 'usd_fallback' => 65000, 'nowpayments' => 'btc'],
    'ETH' => ['label' => 'Ethereum', 'coingecko_id' => 'ethereum', 'decimals' => 8, 'accent' => '#627eea', 'min_convert' => 0.001, 'usd_fallback' => 3500, 'nowpayments' => 'eth'],
    'USDT' => ['label' => 'Tether USD', 'coingecko_id' => 'tether', 'decimals' => 2, 'accent' => '#26a17b', 'min_convert' => 1, 'usd_fallback' => 1, 'nowpayments' => 'usdttrc20'],
    'BNB' => ['label' => 'BNB', 'coingecko_id' => 'binancecoin', 'decimals' => 8, 'accent' => '#f3ba2f', 'min_convert' => 0.01, 'usd_fallback' => 600, 'nowpayments' => 'bnbbsc'],
    'XRP' => ['label' => 'XRP', 'coingecko_id' => 'ripple', 'decimals' => 6, 'accent' => '#23292f', 'min_convert' => 1, 'usd_fallback' => 0.55, 'nowpayments' => 'xrp'],
    'SOL' => ['label' => 'Solana', 'coingecko_id' => 'solana', 'decimals' => 8, 'accent' => '#9945ff', 'min_convert' => 0.01, 'usd_fallback' => 150, 'nowpayments' => 'sol'],
    'USDC' => ['label' => 'USD Coin', 'coingecko_id' => 'usd-coin', 'decimals' => 2, 'accent' => '#2775ca', 'min_convert' => 1, 'usd_fallback' => 1, 'nowpayments' => 'usdc'],
    'TRX' => ['label' => 'TRON', 'coingecko_id' => 'tron', 'decimals' => 6, 'accent' => '#ef0027', 'min_convert' => 10, 'usd_fallback' => 0.12, 'nowpayments' => 'trx'],
    'DOGE' => ['label' => 'Dogecoin', 'coingecko_id' => 'dogecoin', 'decimals' => 8, 'accent' => '#c2a633', 'min_convert' => 10, 'usd_fallback' => 0.12, 'nowpayments' => 'doge'],
    'ADA' => ['label' => 'Cardano', 'coingecko_id' => 'cardano', 'decimals' => 6, 'accent' => '#0033ad', 'min_convert' => 5, 'usd_fallback' => 0.45, 'nowpayments' => 'ada'],
    'SHIB' => ['label' => 'Shiba Inu', 'coingecko_id' => 'shiba-inu', 'decimals' => 8, 'accent' => '#ffa409', 'min_convert' => 100000, 'usd_fallback' => 0.00002, 'nowpayments' => 'shib'],
    'AVAX' => ['label' => 'Avalanche', 'coingecko_id' => 'avalanche-2', 'decimals' => 8, 'accent' => '#e84142', 'min_convert' => 0.1, 'usd_fallback' => 35, 'nowpayments' => 'avax'],
    'LINK' => ['label' => 'Chainlink', 'coingecko_id' => 'chainlink', 'decimals' => 8, 'accent' => '#375bd2', 'min_convert' => 0.1, 'usd_fallback' => 15, 'nowpayments' => 'link'],
    'DOT' => ['label' => 'Polkadot', 'coingecko_id' => 'polkadot', 'decimals' => 8, 'accent' => '#e6007a', 'min_convert' => 0.5, 'usd_fallback' => 7, 'nowpayments' => 'dot'],
    'POL' => ['label' => 'Polygon', 'coingecko_id' => 'polygon-ecosystem-token', 'decimals' => 8, 'accent' => '#8247e5', 'min_convert' => 5, 'usd_fallback' => 0.45, 'nowpayments' => 'pol'],
    'LTC' => ['label' => 'Litecoin', 'coingecko_id' => 'litecoin', 'decimals' => 8, 'accent' => '#345d9d', 'min_convert' => 0.01, 'usd_fallback' => 85, 'nowpayments' => 'ltc'],
    'UNI' => ['label' => 'Uniswap', 'coingecko_id' => 'uniswap', 'decimals' => 8, 'accent' => '#ff007a', 'min_convert' => 0.5, 'usd_fallback' => 8, 'nowpayments' => 'uni'],
    'NEAR' => ['label' => 'NEAR Protocol', 'coingecko_id' => 'near', 'decimals' => 8, 'accent' => '#00c08b', 'min_convert' => 0.5, 'usd_fallback' => 5, 'nowpayments' => 'near'],
    'ATOM' => ['label' => 'Cosmos', 'coingecko_id' => 'cosmos', 'decimals' => 8, 'accent' => '#2e3148', 'min_convert' => 0.5, 'usd_fallback' => 8, 'nowpayments' => 'atom'],
    'APT' => ['label' => 'Aptos', 'coingecko_id' => 'aptos', 'decimals' => 8, 'accent' => '#2dd8a7', 'min_convert' => 0.5, 'usd_fallback' => 9, 'nowpayments' => 'apt'],
];

$usdRates = [];
$minConvert = [];
foreach ($cryptoMeta as $code => $meta) {
    $usdRates[$code] = (float) env('WALLET_RATE_'.$code.'_USD', $meta['usd_fallback']);
    $minConvert[$code] = $meta['min_convert'];
}
$usdRates['NGN'] = (float) env('WALLET_RATE_NGN_USD', 0.00065);
$minConvert['NGN'] = 100;

return [
    'conversion_fee_percent' => (float) env('WALLET_CONVERSION_FEE_PERCENT', 1.0),
    'platform_conversion_fee_percent' => (float) env('WALLET_PLATFORM_CONVERSION_FEE_PERCENT', 1.0),
    'live_rate_cache_seconds' => (int) env('WALLET_LIVE_RATE_CACHE_SECONDS', 5),
    'refresh_rate_on_convert' => (bool) env('WALLET_REFRESH_RATE_ON_CONVERT', true),

    'crypto_meta' => $cryptoMeta,

    'usd_rates' => $usdRates,

    'min_convert' => $minConvert,

    /*
    | NOWPayments pay_currency slugs per wallet network (deposit).
    | Prevents unsupported pairs like USDT OMNI on the hosted checkout page.
    */
    'nowpayments_networks' => [
        'USDT' => [
            'trc20' => 'usdttrc20',
            'erc20' => 'usdterc20',
            'bep20' => 'usdtbsc',
        ],
        'USDC' => [
            'erc20' => 'usdc',
            'trc20' => 'usdctrc20',
        ],
    ],
];
