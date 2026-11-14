<?php

namespace App\Models;

use App\Enums\PromotionRuleType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Promotion extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'code',
        'name',
        'rule_type',
        'description',
        'benefit_type',
        'benefit_value',
        'max_discount',
        'starts_at',
        'ends_at',
        'target_segments',
        'min_transaction',
        'usage_limit',
        'per_member_limit',
        'conditions',
        'is_active',
        'is_demo',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'rule_type' => PromotionRuleType::class,
            'benefit_value' => 'integer',
            'max_discount' => 'integer',
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'target_segments' => 'array',
            'min_transaction' => 'integer',
            'usage_limit' => 'integer',
            'per_member_limit' => 'integer',
            'conditions' => 'array',
            'is_active' => 'boolean',
            'is_demo' => 'boolean',
        ];
    }

    public function usages(): HasMany
    {
        return $this->hasMany(PromotionUsage::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function calculateDiscount(int $subtotal): int
    {
        if ($this->rule_type === PromotionRuleType::ReturnTracking || $this->benefit_type === 'bonus_points') {
            return 0;
        }

        if ($this->benefit_type === 'percent') {
            $discount = (int) round($subtotal * ($this->benefit_value / 100));
            if ($this->max_discount && $discount > $this->max_discount) {
                $discount = (int) $this->max_discount;
            }

            return min($subtotal, max(0, $discount));
        }

        return min($subtotal, max(0, (int) $this->benefit_value));
    }

    public function benefitLabel(): string
    {
        if ($this->rule_type === PromotionRuleType::ReturnTracking) {
            return 'Pelacakan Kunjungan Ulang';
        }
        if ($this->benefit_type === 'percent') {
            $cap = $this->max_discount ? ' (maks Rp'.number_format($this->max_discount, 0, ',', '.').')' : '';

            return 'Diskon '.$this->benefit_value.'%'.$cap;
        }
        if ($this->benefit_type === 'bonus_points') {
            return '+'.$this->benefit_value.' Poin Bonus';
        }

        return 'Potongan Rp'.number_format($this->benefit_value, 0, ',', '.');
    }

    public function benefitSummary(): string
    {
        return $this->benefitLabel();
    }
}
