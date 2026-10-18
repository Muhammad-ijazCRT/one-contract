<?php

namespace App\Models;

use App\Enums\WalletCurrency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FiatRequest extends Model
{
    protected $fillable = [
        'user_id',
        'direction',
        'amount',
        'currency',
        'bank_name',
        'account_name',
        'account_number',
        'reference',
        'notes',
        'status',
        'gateway',
        'gateway_reference',
        'checkout_url',
        'gateway_status',
        'bank_code',
        'gateway_payload',
        'processed_by',
        'processed_at',
        'admin_notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:8',
            'processed_at' => 'datetime',
            'gateway_payload' => 'array',
        ];
    }

    public function creditWallet(): WalletCurrency
    {
        return WalletCurrency::tryNormalize($this->currency)
            ?? WalletCurrency::Ngn;
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function processor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'processed_by');
    }
}
