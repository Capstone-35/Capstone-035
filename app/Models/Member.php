<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Member extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'member_code',
        'points_balance',
        'consent_at',
        'joined_at',
        'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'points_balance' => 'integer',
            'consent_at' => 'datetime',
            'joined_at' => 'datetime',
            'is_demo' => 'boolean',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getNameAttribute(): string
    {
        return (string) ($this->user?->name ?? 'Member');
    }

    public function getEmailAttribute(): ?string
    {
        return $this->user?->email;
    }

    public function getPhoneAttribute(): ?string
    {
        return $this->user?->phone;
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function pointLedger(): HasMany
    {
        return $this->hasMany(PointLedger::class)->orderByDesc('id');
    }

    public function pointLedgers(): HasMany
    {
        return $this->pointLedger();
    }

    public function rewardRedemptions(): HasMany
    {
        return $this->hasMany(RewardRedemption::class)->orderByDesc('id');
    }

    public function promotionUsages(): HasMany
    {
        return $this->hasMany(PromotionUsage::class);
    }

    public function rfmResults(): HasMany
    {
        return $this->hasMany(RfmResult::class);
    }

    public function latestRfmResult(): HasOne
    {
        return $this->hasOne(RfmResult::class)->latestOfMany();
    }
}
