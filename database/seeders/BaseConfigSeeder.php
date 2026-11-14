<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Models\RfmParameter;
use App\Models\RfmSegment;
use App\Models\Setting;
use Illuminate\Database\Seeder;

class BaseConfigSeeder extends Seeder
{
    public function run(): void
    {
        $isDemo = (bool) config('app.demo_mode', true);

        // ASSUMPTION A1, A2, A7, D2, D4: All parameters stored in settings and editable by Manager/Owner
        $settings = [
            ['key' => 'outlet.name', 'value' => '774 HUB', 'type' => 'string', 'description' => 'Nama outlet coffee shop'],
            ['key' => 'outlet.address', 'value' => 'Jl. Bung Tarjo No. 12, Baciro, Kec. Gondokusuman, Kota Yogyakarta, DIY', 'type' => 'string', 'description' => 'Alamat outlet 774 HUB'],
            ['key' => 'outlet.phone', 'value' => '0274-555774', 'type' => 'string', 'description' => 'Kontak outlet'],
            ['key' => 'outlet.instagram', 'value' => '@774.hub', 'type' => 'string', 'description' => 'Akun Instagram resmi'],
            ['key' => 'outlet.tax_id', 'value' => '', 'type' => 'string', 'description' => 'NPWP / Nomor Pajak Daerah (opsional, Q12)'],
            ['key' => 'tax_rate', 'value' => $isDemo ? '10' : '0', 'type' => 'float', 'description' => 'Tarif pajak dalam persen (ASSUMPTION A7 / D2: 10% pada mode demo, 0% default produksi)'],
            ['key' => 'qris_expiry_minutes', 'value' => '15', 'type' => 'int', 'description' => 'Batas waktu pembayaran QRIS dalam menit (ASSUMPTION A1)'],
            ['key' => 'loyalty.points_per_amount', 'value' => '10000', 'type' => 'int', 'description' => 'Nominal belanja (Rp) setelah diskon untuk memperoleh 1 poin (ASSUMPTION A2)'],
            ['key' => 'loyalty.earn_on_status', 'value' => OrderStatus::Selesai->value, 'type' => 'string', 'description' => 'Status transaksi pemicu perolehan poin & transaksi berhasil (D4: Selesai atau Dibayar)'],
            ['key' => 'kds.warning_minutes', 'value' => '5', 'type' => 'int', 'description' => 'Ambang timer kuning KDS (menit)'],
            ['key' => 'kds.urgent_minutes', 'value' => '10', 'type' => 'int', 'description' => 'Ambang timer merah KDS (menit)'],
        ];

        foreach ($settings as $item) {
            Setting::setValue($item['key'], $item['value'], $item['type'], $item['description']);
        }

        // ASSUMPTION A3: RFM parameters (period_days = 90; R, F, M thresholds)
        RfmParameter::query()->updateOrCreate(
            ['key' => 'scoring_config'],
            [
                'value' => [
                    'period_days' => 90,
                    // Recency (days since last successful order; lower is better)
                    'r_thresholds' => [
                        5 => 7,   // <= 7 days -> 5
                        4 => 14,  // <= 14 days -> 4
                        3 => 30,  // <= 30 days -> 3
                        2 => 60,  // <= 60 days -> 2
                        1 => 999999, // > 60 days -> 1
                    ],
                    // Frequency (count of successful orders in period; higher is better)
                    'f_thresholds' => [
                        5 => 8,   // >= 8 -> 5
                        4 => 5,   // 5..7 -> 4
                        3 => 3,   // 3..4 -> 3
                        2 => 2,   // 2 -> 2
                        1 => 1,   // 1 -> 1
                    ],
                    // Monetary (total Rupiah of successful orders in period; higher is better)
                    'm_thresholds' => [
                        5 => 1000000, // >= Rp1.000.000 -> 5
                        4 => 600000,  // >= Rp600.000 -> 4
                        3 => 300000,  // >= Rp300.000 -> 3
                        2 => 100000,  // >= Rp100.000 -> 2
                        1 => 0,       // < Rp100.000 -> 1
                    ],
                ],
            ]
        );

        // ASSUMPTION Section 6 Module I: Ordered RFM segment rules (first match wins)
        $segments = [
            [
                'slug' => 'pelanggan-setia',
                'name' => 'Pelanggan Setia',
                'description' => 'Member dengan frekuensi, nilai belanja, dan kunjungan terbaru sangat tinggi (R≥4, F≥4, M≥4).',
                'priority' => 1,
                'rule' => ['r_min' => 4, 'r_max' => 5, 'f_min' => 4, 'f_max' => 5, 'm_min' => 4, 'm_max' => 5],
            ],
            [
                'slug' => 'pelanggan-loyal',
                'name' => 'Pelanggan Loyal',
                'description' => 'Member yang rutin bertransaksi secara konsisten (R≥3, F≥3).',
                'priority' => 2,
                'rule' => ['r_min' => 3, 'r_max' => 5, 'f_min' => 3, 'f_max' => 5, 'm_min' => 1, 'm_max' => 5],
            ],
            [
                'slug' => 'pelanggan-baru',
                'name' => 'Pelanggan Baru',
                'description' => 'Member yang baru pertama kali bertransaksi dalam waktu dekat (F=1, R≥4).',
                'priority' => 3,
                'rule' => ['r_min' => 4, 'r_max' => 5, 'f_min' => 1, 'f_max' => 1, 'm_min' => 1, 'm_max' => 5],
            ],
            [
                'slug' => 'berpotensi',
                'name' => 'Berpotensi',
                'description' => 'Member yang baru bertransaksi beberapa kali namun masih aktif berkunjung (R≥3, F≤2).',
                'priority' => 4,
                'rule' => ['r_min' => 3, 'r_max' => 5, 'f_min' => 1, 'f_max' => 2, 'm_min' => 1, 'm_max' => 5],
            ],
            [
                'slug' => 'berisiko',
                'name' => 'Berisiko',
                'description' => 'Member yang dulunya sering bertransaksi namun sudah lama tidak kembali (R≤2, F≥3).',
                'priority' => 5,
                'rule' => ['r_min' => 1, 'r_max' => 2, 'f_min' => 3, 'f_max' => 5, 'm_min' => 1, 'm_max' => 5],
            ],
            [
                'slug' => 'tidak-aktif',
                'name' => 'Tidak Aktif',
                'description' => 'Member dengan frekuensi rendah dan sudah lama tidak bertransaksi (R≤2, F≤2).',
                'priority' => 6,
                'rule' => ['r_min' => 1, 'r_max' => 2, 'f_min' => 1, 'f_max' => 2, 'm_min' => 1, 'm_max' => 5],
            ],
        ];

        foreach ($segments as $seg) {
            RfmSegment::query()->updateOrCreate(
                ['slug' => $seg['slug']],
                [
                    'name' => $seg['name'],
                    'description' => $seg['description'],
                    'priority' => $seg['priority'],
                    'rule' => $seg['rule'],
                    'is_active' => true,
                ]
            );
        }
    }
}
