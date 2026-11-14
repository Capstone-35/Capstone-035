<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PointLedger extends Model
{
    public $timestamps = false;

    protected $table = 'point_ledger';

    protected $fillable = [
        'member_id',
        'order_id',
        'reward_redemption_id',
        'type',
        'points',
        'balance_after',
        'note',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'points' => 'integer',
            'balance_after' => 'integer',
            'created_at' => 'datetime',
        ];
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function rewardRedemption(): BelongsTo
    {
        return $this->belongsTo(RewardRedemption::class);
    }
}
