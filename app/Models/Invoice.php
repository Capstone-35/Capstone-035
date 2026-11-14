<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Invoice extends Model
{
    protected $fillable = [
        'order_id',
        'invoice_number',
        'issued_at',
        'pdf_path',
        'snapshot',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'snapshot' => 'array',
        ];
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function deliveries(): HasMany
    {
        return $this->hasMany(InvoiceDelivery::class)->orderByDesc('id');
    }

    public function latestDelivery(): HasOne
    {
        return $this->hasOne(InvoiceDelivery::class)->latestOfMany();
    }
}
