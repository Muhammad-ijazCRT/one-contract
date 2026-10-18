<?php

$src = 'C:/Users/SHAFI UL HASSAN/.cursor/projects/e-one-contract-one-contract-updated-code/assets';
$dst = dirname(__DIR__).'/public/images/dashboard';

if (! is_dir($dst)) {
    mkdir($dst, 0777, true);
}

$map = [
    'dash-promo-hero.png' => 'promo-hero.png',
    'dash-stat-wallet.png' => 'stat-wallet.png',
    'dash-stat-earnings.png' => 'stat-earnings.png',
    'dash-stat-invest.png' => 'stat-invest.png',
    'dash-stat-rewards.png' => 'stat-rewards.png',
    'dash-feat-saving.png' => 'feature-saving.png',
    'dash-feat-lending.png' => 'feature-lending.png',
    'dash-feat-invest.png' => 'feature-invest.png',
    'dash-feat-betting.png' => 'feature-betting.png',
    'dash-feat-tx.png' => 'feature-transactions.png',
    'dash-feat-earnings.png' => 'feature-earnings.png',
    'dash-cta-banner.png' => 'cta-champion.png',
];

foreach ($map as $from => $to) {
    $a = $src.'/'.$from;
    $b = $dst.'/'.$to;
    if (! is_file($a)) {
        echo "MISSING $from\n";
        continue;
    }
    if (! copy($a, $b)) {
        echo "FAIL $from\n";
        continue;
    }
    echo "OK $to\n";
}
