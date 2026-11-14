<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';
    case Cancelled = 'cancelled';
    case AmountMismatch = 'amount_mismatch';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu',
            self::Paid => 'Lunas',
            self::Failed => 'Gagal',
            self::Expired => 'Kedaluwarsa',
            self::Cancelled => 'Dibatalkan',
            self::AmountMismatch => 'Nominal Tidak Sesuai',
        };
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::Pending => 'warning',
            self::Paid => 'success',
            self::Failed, self::AmountMismatch => 'danger',
            self::Expired, self::Cancelled => 'neutral',
        };
    }
}
