<?php

namespace App\Enums;

enum InvoiceDeliveryStatus: string
{
    case Pending = 'pending';
    case Sent = 'sent';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Pengiriman',
            self::Sent => 'Terkirim',
            self::Failed => 'Gagal Kirim',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Sent => 'success',
            self::Failed => 'danger',
        };
    }
}
