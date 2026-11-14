<?php

namespace App\Models;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Order extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_number',
        'order_code',
        'public_token',
        'client_request_id',
        'source',
        'table_id',
        'member_id',
        'customer_name',
        'customer_email',
        'customer_phone',
        'status',
        'payment_method',
        'subtotal',
        'discount_amount',
        'tax_rate',
        'tax_amount',
        'total_amount',
        'promotion_id',
        'reward_redemption_id',
        'is_repeat_order',
        'paid_at',
        'processing_at',
        'ready_at',
        'completed_at',
        'cancelled_at',
        'cancel_reason',
        'created_by',
        'handed_over_by',
        'is_demo',
    ];

    protected function casts(): array
    {
        return [
            'status' => OrderStatus::class,
            'payment_method' => PaymentMethod::class,
            'subtotal' => 'integer',
            'discount_amount' => 'integer',
            'tax_rate' => 'float',
            'tax_amount' => 'integer',
            'total_amount' => 'integer',
            'is_repeat_order' => 'boolean',
            'paid_at' => 'datetime',
            'processing_at' => 'datetime',
            'ready_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'is_demo' => 'boolean',
        ];
    }

    /**
     * Reusable scope for "Transaksi Berhasil" (Section 0.3 / D4).
     * When setting loyalty.earn_on_status = 'Dibayar', includes Dibayar, Diproses, Siap, Selesai.
     * By default ('Selesai'), only includes Selesai.
     */
    public function scopeSuccessful(Builder $query): Builder
    {
        $earnOn = Setting::getValue('loyalty.earn_on_status', OrderStatus::Selesai->value);

        if ($earnOn === OrderStatus::Dibayar->value) {
            return $query->whereIn('status', [
                OrderStatus::Dibayar->value,
                OrderStatus::Diproses->value,
                OrderStatus::Siap->value,
                OrderStatus::Selesai->value,
            ]);
        }

        return $query->where('status', OrderStatus::Selesai->value);
    }

    /**
     * Operational Revenue scope (Section 0.3): orders with confirmed payment
     * (Dibayar, Diproses, Siap, Selesai). Cancelled orders excluded.
     */
    public function scopePaidRevenue(Builder $query): Builder
    {
        return $query->whereIn('status', [
            OrderStatus::Dibayar->value,
            OrderStatus::Diproses->value,
            OrderStatus::Siap->value,
            OrderStatus::Selesai->value,
        ]);
    }

    public function table(): BelongsTo
    {
        return $this->belongsTo(DiningTable::class, 'table_id');
    }

    public function diningTable(): BelongsTo
    {
        return $this->table();
    }

    public function pointLedgers(): HasMany
    {
        return $this->hasMany(PointLedger::class);
    }

    public function member(): BelongsTo
    {
        return $this->belongsTo(Member::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('id');
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->orderByDesc('id');
    }

    public function paidPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->where('status', 'paid');
    }

    public function latestPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }

    public function invoice(): HasOne
    {
        return $this->hasOne(Invoice::class);
    }

    public function promotion(): BelongsTo
    {
        return $this->belongsTo(Promotion::class);
    }

    public function promotionUsage(): HasOne
    {
        return $this->hasOne(PromotionUsage::class);
    }

    public function rewardRedemption(): BelongsTo
    {
        return $this->belongsTo(RewardRedemption::class);
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function handedOverBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'handed_over_by');
    }

    public function isEditable(): bool
    {
        return $this->status === OrderStatus::MenungguPembayaran;
    }

    public function formattedTotal(): string
    {
        return 'Rp'.number_format($this->total_amount, 0, ',', '.');
    }

    public function shortNumber(): string
    {
        $parts = explode('-', $this->order_number);

        return end($parts) ?: $this->order_number;
    }
}
