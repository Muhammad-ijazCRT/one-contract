<?php

namespace App\Models;

use App\Enums\WalletCurrency;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserWallet extends Model
{
    protected $fillable = [
        'user_id',
        'currency',
        'balance',
    ];

    protected function casts(): array
    {
        return [
            'currency' => WalletCurrency::class,
            'balance' => 'decimal:8',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
