<?php

namespace App\Enums;

enum OrderStatus: string
{
    case MenungguPembayaran = 'Menunggu Pembayaran';
    case Dibayar = 'Dibayar';
    case Diproses = 'Diproses';
    case Siap = 'Siap';
    case Selesai = 'Selesai';
    case Dibatalkan = 'Dibatalkan';

    public function label(): string
    {
        return $this->value;
    }

    public function badgeVariant(): string
    {
        return match ($this) {
            self::MenungguPembayaran => 'warning',
            self::Dibayar => 'paid',
            self::Diproses => 'info',
            self::Siap => 'success',
            self::Selesai => 'neutral',
            self::Dibatalkan => 'danger',
        };
    }

    public function iconName(): string
    {
        return match ($this) {
            self::MenungguPembayaran => 'clock',
            self::Dibayar => 'check-circle',
            self::Diproses => 'flame',
            self::Siap => 'bell',
            self::Selesai => 'check-check',
            self::Dibatalkan => 'x-circle',
        };
    }

    public function isPaidOrLater(): bool
    {
        return in_array($this, [
            self::Dibayar,
            self::Diproses,
            self::Siap,
            self::Selesai,
        ], true);
    }

    public function isTerminal(): bool
    {
        return in_array($this, [
            self::Selesai,
            self::Dibatalkan,
        ], true);
    }
}
