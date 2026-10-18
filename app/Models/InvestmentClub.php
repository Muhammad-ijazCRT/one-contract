<?php

namespace App\Models;

use App\Enums\InvestmentClubSlug;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class InvestmentClub extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'reward_percent',
        'cycle_days',
        'min_investment',
        'max_investment',
        'is_active',
        'sort_order',
    ];

    protected function casts(): array
    {
        return [
            'slug' => InvestmentClubSlug::class,
            'reward_percent' => 'decimal:4',
            'cycle_days' => 'integer',
            'min_investment' => 'decimal:8',
            'max_investment' => 'decimal:8',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    public function investments(): HasMany
    {
        return $this->hasMany(ClubInvestment::class);
    }
}
