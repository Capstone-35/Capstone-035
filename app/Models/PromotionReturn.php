<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PromotionReturn extends Model
{
    protected $fillable = [
        'promotion_usage_id',
        'member_id',
        'returned_order_id',
        'days_to_return',
        'return_order_value',
        'evaluated_at',
    ];

    protected function casts(): array
    {
        return [
            'days_to_return' => 'integer',
            'return_order_value' => 'integer',
            'evaluated_at' => 'datetime',
        ];
    }

    public function usage(): BelongsTo
    {
        return $this->belongsTo(PromotionUsage::class, 'promotion_usage_id');
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function returnedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'returned_order_id');
    }
}
