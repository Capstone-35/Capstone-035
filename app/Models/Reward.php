<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Reward extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'description',
        'points_cost',
        'benefit_type',
        'benefit_value',
        'max_discount',
        'valid_days',
        'is_active',
        'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'points_cost' => 'integer',
            'benefit_value' => 'integer',
            'max_discount' => 'integer',
            'valid_days' => 'integer',
            'is_active' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    public function redemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class);
    }

    public function calculateDiscount(int $subtotal): int
    {
        if ($this->benefit_type === 'percent') {
            $discount = (int) round($subtotal * ($this->benefit_value / 100));
            if ($this->max_discount && $discount > $this->max_discount) {
                $discount = $this->max_discount;
            }

            return min($subtotal, max(0, $discount));
        }

        return min($subtotal, max(0, (int) $this->benefit_value));
    }
}
