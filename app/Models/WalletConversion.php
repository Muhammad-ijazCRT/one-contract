<?php

namespace App\Models;

use App\Enums\WalletCurrency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalletConversion extends Model
{
    protected $fillable = [
        'user_id',
        'from_currency',
        'to_currency',
        'from_amount',
        'to_amount',
        'rate',
        'fee_amount',
        'fee_percent',
        'provider_fee_percent',
        'provider_fee_amount',
        'platform_fee_percent',
        'platform_fee_amount',
        'status',
        'nowpayments_conversion_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'from_currency' => WalletCurrency::class,
            'to_currency' => WalletCurrency::class,
            'from_amount' => 'decimal:8',
            'to_amount' => 'decimal:8',
            'rate' => 'decimal:12',
            'fee_amount' => 'decimal:8',
            'fee_percent' => 'decimal:4',
            'provider_fee_percent' => 'decimal:4',
            'provider_fee_amount' => 'decimal:8',
            'platform_fee_percent' => 'decimal:4',
            'platform_fee_amount' => 'decimal:8',
            'metadata' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
