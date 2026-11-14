<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class PromotionUsage extends Model
{
    protected $fillable = [
        'promotion_id',
        'order_id',
        'member_id',
        'discount_amount',
        'bonus_points',
        'used_at',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'integer',
            'bonus_points' => 'integer',
            'used_at' => 'datetime',
        ];
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function returnRecord(): HasOne
    {
        return $this->hasOne(PromotionReturn::class, 'promotion_usage_id');
    }
}
