<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Treasury extends Model
{
    protected $table = 'treasury';

    protected $fillable = [
        'balance',
        'reserved_balance',
    ];

    protected function casts(): array
    {
        return [
            'balance' => 'decimal:8',
            'reserved_balance' => 'decimal:8',
        ];
    }
}
