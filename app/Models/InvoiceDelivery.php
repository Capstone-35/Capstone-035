<?php

namespace App\Models;

use App\Enums\InvoiceDeliveryStatus;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InvoiceDelivery extends Model
{
    protected $fillable = [
        'invoice_id',
        'channel',
        'recipient',
        'status',
        'attempt_no',
        'provider_message_id',
        'error_message',
        'sent_at',
        'triggered_by',
    ];

    protected function casts(): array
    {
        return [
            'status' => InvoiceDeliveryStatus::class,
            'attempt_no' => 'integer',
            'sent_at' => 'datetime',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }
}
