<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RewardRedemption extends Model
{
    protected $fillable = [
        'member_id',
        'reward_id',
        'voucher_code',
        'points_spent',
        'status',
        'redeemed_at',
        'used_order_id',
        'expires_at',
    ];

    protected function casts(): array
    {
        return [
            'points_spent' => 'integer',
            'redeemed_at' => 'datetime',
            'expires_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function reward(): BelongsTo
    {
        return $this->belongsTo(Reward::class);
    }

    public function usedOrder(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'used_order_id');
    }

    public function isUsable(): bool
    {
        if ($this->status !== 'active') {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        return true;
    }
}
