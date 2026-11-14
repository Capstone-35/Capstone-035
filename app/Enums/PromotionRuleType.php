<?php

namespace App\Enums;

enum PromotionRuleType: string
{
    case Comeback = 'comeback';
    case RepeatOrder = 'repeat_order';
    case HariBesar = 'hari_besar';
    case WelcomeNextVisit = 'welcome_next_visit';
    case SlowHour = 'slow_hour';
    case ReturnTracking = 'return_tracking';

    public function label(): string
    {
        return match ($this) {
            self::Comeback => 'Comeback Promo',
            self::RepeatOrder => 'Repeat Order Reward',
            self::HariBesar => 'Promo Hari Besar',
            self::WelcomeNextVisit => 'Welcome/Next Visit Promo',
            self::SlowHour => 'Slow Hour Promo',
            self::ReturnTracking => 'Promo Return Tracking',
        };
    }

    public function description(): string
    {
        return match ($this) {
            self::Comeback => 'Diskon bagi Member yang sudah tidak bertransaksi selama N hari terakhir (mis. 30/60 hari) atau berada di segmen Berisiko/Tidak Aktif.',
            self::RepeatOrder => 'Apresiasi bagi Member yang mencapai minimal N transaksi berhasil dalam rentang waktu tertentu (mis. 5 transaksi dalam 30 hari).',
            self::HariBesar => 'Promo khusus pada rentang tanggal hari besar yang ditetapkan oleh Manager.',
            self::WelcomeNextVisit => 'Promo sambutan bagi Member baru (0 transaksi) atau kupon kunjungan berikutnya setelah transaksi selesai.',
            self::SlowHour => 'Promo pada hari dan jam sepi yang dikonfigurasi (mis. Senin–Jumat pukul 14:00–16:00 WIB).',
            self::ReturnTracking => 'Pelacakan pasif untuk mencatat apakah dan kapan Member kembali bertransaksi setelah menerima/memakai promo.',
        };
    }
}
