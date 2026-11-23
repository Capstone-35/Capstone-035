<?php

namespace Database\Seeders;

use App\Domain\Loyalty\LoyaltyService;
use App\Domain\Rfm\RfmService;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Enums\PromotionRuleType;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Invoice;
use App\Models\InvoiceDelivery;
use App\Models\Member;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\OrderStatusHistory;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\PromotionReturn;
use App\Models\PromotionUsage;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoDataSeeder extends Seeder
{
    public function run(): void
    {
        if (app()->environment('production') || ! config('app.demo_mode', true)) {
            return;
        }

        $password = Hash::make('Demo#774hub');

        // 1. Staff Accounts
        $staffMap = [
            'kasir@774hub.test' => ['name' => 'Budi Kasir', 'role' => 'kasir', 'phone' => '081200000001'],
            'kitchen@774hub.test' => ['name' => 'Agus Barista', 'role' => 'kitchen', 'phone' => '081200000002'],
            'manager@774hub.test' => ['name' => 'Dewi Manager', 'role' => 'manager', 'phone' => '081200000003'],
            'owner@774hub.test' => ['name' => 'Pak Hendra (Owner)', 'role' => 'owner', 'phone' => '081200000004'],
        ];

        foreach ($staffMap as $email => $st) {
            $user = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => $st['name'],
                    'phone' => $st['phone'],
                    'password' => $password,
                    'is_active' => true,
                    'is_demo' => true,
                ]
            );
            $user->syncRoles([$st['role']]);
        }

        // 2. Member Accounts (40 Members)
        $memberUsers = [];
        for ($i = 1; $i <= 40; $i++) {
            $email = "member{$i}@example.test";
            $u = User::query()->updateOrCreate(
                ['email' => $email],
                [
                    'name' => "Member {$i}",
                    'phone' => '08129999'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                    'password' => $password,
                    'is_active' => true,
                    'is_demo' => true,
                ]
            );
            $u->syncRoles(['member']);

            $mbr = Member::query()->updateOrCreate(
                ['user_id' => $u->id],
                [
                    'member_code' => 'MBR-774-'.str_pad((string) $i, 4, '0', STR_PAD_LEFT),
                    'points_balance' => 0, // recalculated below from seeded orders
                    'consent_at' => now()->subDays(rand(60, 150)),
                    'joined_at' => now()->subDays(rand(60, 150)),
                    'is_demo' => true,
                ]
            );
            $memberUsers[$i] = $mbr;
        }

        // 3 & 4. Seed Real 774 HUB Menu Catalog (10 Categories, 44 Products)
        $this->call(MenuCatalogSeeder::class);

        $allProducts = Product::query()
            ->where('is_active', true)
            ->where('is_available', true)
            ->orderBy('id')
            ->get()
            ->all();

        $featuredSlugs = [
            'butterscotch-best-seller',
            'earl-grey-milk',
            'st-ruby',
            'chicken-karaage-bowl',
            'mix-platter',
        ];
        $weightedProducts = $allProducts;
        foreach ($allProducts as $prod) {
            if (in_array($prod->slug, $featuredSlugs, true)) {
                // Boost real best-sellers in demo historical sales
                $weightedProducts[] = $prod;
                $weightedProducts[] = $prod;
            }
        }

        // 5. Dining Tables (12 Meja)
        $tables = [];
        for ($t = 1; $t <= 12; $t++) {
            $num = str_pad((string) $t, 2, '0', STR_PAD_LEFT);
            $token = "meja-{$num}-demotoken774hub";
            $tables[$t] = DiningTable::query()->updateOrCreate(
                ['number' => $num],
                [
                    'label' => "Meja {$num}",
                    'qr_token' => $token,
                    'is_active' => true,
                    'is_demo' => true,
                ]
            );
        }

        // 6. Rewards Catalogue (7 Rewards)
        $rewardsData = [
            ['name' => 'Voucher Potongan Rp5.000', 'pts' => 25, 'type' => 'nominal', 'val' => 5000, 'days' => 30],
            ['name' => 'Voucher Potongan Rp10.000', 'pts' => 50, 'type' => 'nominal', 'val' => 10000, 'days' => 30],
            ['name' => 'Voucher Potongan Rp20.000', 'pts' => 90, 'type' => 'nominal', 'val' => 20000, 'days' => 30],
            ['name' => 'Voucher Potongan Rp35.000', 'pts' => 150, 'type' => 'nominal', 'val' => 35000, 'days' => 45],
            ['name' => 'Diskon 10% Spesial Loyalti', 'pts' => 40, 'type' => 'percent', 'val' => 10, 'max' => 20000, 'days' => 30],
            ['name' => 'Diskon 20% Special Pastry', 'pts' => 70, 'type' => 'percent', 'val' => 20, 'max' => 30000, 'days' => 30],
            ['name' => 'Gratis 1 Kopi Pilihan (Voucher Rp25.000)', 'pts' => 110, 'type' => 'nominal', 'val' => 25000, 'days' => 60],
        ];

        foreach ($rewardsData as $rw) {
            Reward::query()->updateOrCreate(
                ['name' => $rw['name']],
                [
                    'description' => 'Penukaran poin loyalti 774 HUB.',
                    'points_cost' => $rw['pts'],
                    'benefit_type' => $rw['type'],
                    'benefit_value' => $rw['val'],
                    'max_discount' => $rw['max'] ?? null,
                    'valid_days' => $rw['days'],
                    'is_active' => true,
                    'is_demo' => true,
                ]
            );
        }

        // 7. Promotions (All 6 Rule Types from D1)
        $promotionsData = [
            [
                'code' => 'COMEBACK15',
                'name' => 'Comeback Promo — Rindu Ngopi di 774 HUB',
                'rule_type' => PromotionRuleType::Comeback->value,
                'benefit_type' => 'percent',
                'benefit_value' => 15,
                'max_discount' => 25000,
                'min_transaction' => 30000,
                'desc' => 'Diskon 15% untuk Member yang tidak bertransaksi selama lebih dari 30 hari.',
                'conditions' => ['inactive_days' => 30],
                'target_segments' => ['at-risk', 'hibernating', 'about-to-sleep'],
            ],
            [
                'code' => 'REPEATREWARD',
                'name' => 'Repeat Order Reward — Apresiasi Kunjungan Rutin',
                'rule_type' => PromotionRuleType::RepeatOrder->value,
                'benefit_type' => 'nominal',
                'benefit_value' => 15000,
                'max_discount' => null,
                'min_transaction' => 40000,
                'desc' => 'Potongan Rp15.000 untuk Member yang bertransaksi minimal 5 kali dalam 30 hari terakhir.',
                'conditions' => ['required_orders' => 5, 'window_days' => 30],
                'target_segments' => ['champions', 'loyal-customers', 'potential-loyalists'],
            ],
            [
                'code' => 'HARIBESAR20',
                'name' => 'Promo Hari Besar Nasional',
                'rule_type' => PromotionRuleType::HariBesar->value,
                'benefit_type' => 'percent',
                'benefit_value' => 20,
                'max_discount' => 30000,
                'min_transaction' => 50000,
                'desc' => 'Diskon 20% menyambut perayaan hari besar nasional untuk seluruh Member.',
                'conditions' => ['holiday_name' => 'Hari Spesial 774 HUB'],
                'target_segments' => ['all'],
            ],
            [
                'code' => 'WELCOME774',
                'name' => 'Welcome / Next Visit Promo',
                'rule_type' => PromotionRuleType::WelcomeNextVisit->value,
                'benefit_type' => 'percent',
                'benefit_value' => 10,
                'max_discount' => 15000,
                'min_transaction' => 25000,
                'desc' => 'Diskon 10% untuk Member baru dalam 14 hari sejak registrasi akun.',
                'conditions' => ['welcome_days' => 14],
                'target_segments' => ['all'],
            ],
            [
                'code' => 'SLOWHOUR10',
                'name' => 'Slow Hour Afternoon Coffee Promo',
                'rule_type' => PromotionRuleType::SlowHour->value,
                'benefit_type' => 'nominal',
                'benefit_value' => 10000,
                'max_discount' => null,
                'min_transaction' => 35000,
                'desc' => 'Potongan Rp10.000 pada jam sepi pukul 14:00 - 17:00 WIB di hari kerja (Senin–Jumat).',
                'conditions' => [
                    'start_time' => '14:00',
                    'end_time' => '17:00',
                    'days_of_week' => [1, 2, 3, 4, 5],
                ],
                'target_segments' => ['all'],
            ],
            [
                'code' => 'RETURNCUST12',
                'name' => 'Promo Return Tracking Reward',
                'rule_type' => PromotionRuleType::ReturnTracking->value,
                'benefit_type' => 'percent',
                'benefit_value' => 12,
                'max_discount' => 20000,
                'min_transaction' => 30000,
                'desc' => 'Promosi khusus untuk memantau siklus kembalinya pelanggan setelah promo diberikan.',
                'conditions' => ['tracking_window_days' => 30],
                'target_segments' => ['all'],
            ],
        ];

        $promotions = [];
        foreach ($promotionsData as $pm) {
            $promotions[$pm['code']] = Promotion::query()->updateOrCreate(
                ['code' => $pm['code']],
                [
                    'name' => $pm['name'],
                    'rule_type' => $pm['rule_type'],
                    'description' => $pm['desc'],
                    'benefit_type' => $pm['benefit_type'],
                    'benefit_value' => $pm['benefit_value'],
                    'max_discount' => $pm['max_discount'],
                    'starts_at' => now()->subDays(60),
                    'ends_at' => now()->addDays(60),
                    'target_segments' => $pm['target_segments'],
                    'min_transaction' => $pm['min_transaction'],
                    'per_member_limit' => 5,
                    'conditions' => $pm['conditions'],
                    'is_active' => true,
                    'is_demo' => true,
                ]
            );
        }

        // 8. Generate ~450 Historical Orders over 120 Days
        $loyaltyService = app(LoyaltyService::class);
        $taxRate = (float) Setting::getValue('tax_rate', 10.0);

        // Pre-create some reward redemption vouchers for members
        $seededVouchers = [];
        for ($v = 1; $v <= 8; $v++) {
            $m = $memberUsers[rand(1, 15)];
            $rw = Reward::query()->inRandomOrder()->first();
            if ($rw) {
                $seededVouchers[] = RewardRedemption::query()->create([
                    'member_id' => $m->id,
                    'reward_id' => $rw->id,
                    'voucher_code' => 'VOUCH-774-'.strtoupper(Str::random(6)),
                    'points_spent' => $rw->points_cost,
                    'status' => 'active',
                    'redeemed_at' => now()->subDays(rand(5, 20)),
                    'expires_at' => now()->addDays(15),
                ]);
            }
        }

        $now = now();
        $daysBack = 110;

        for ($day = $daysBack; $day >= 0; $day--) {
            $targetDate = $now->copy()->subDays($day);
            $isWeekend = $targetDate->isWeekend();
            // 3-6 orders per weekday, 6-9 on weekends
            $dailyCount = $isWeekend ? rand(5, 8) : rand(3, 5);

            for ($k = 0; $k < $dailyCount; $k++) {
                // Peak hours: 09:00-11:30 or 17:00-21:00
                $hour = rand(0, 1) === 1 ? rand(9, 12) : (rand(0, 1) === 1 ? rand(14, 16) : rand(17, 21));
                $minute = rand(0, 59);
                $createdAt = $targetDate->copy()->setTime($hour, $minute, rand(0, 59));

                $isMember = rand(1, 10) <= 7; // 70% Member
                $member = $isMember ? $memberUsers[rand(1, 40)] : null;
                $customerName = $member ? $member->user->name : 'Tamu ' . chr(rand(65, 90)) . rand(10, 99);
                $customerEmail = $member ? $member->user->email : ('guest' . rand(100, 999) . '@example.test');
                $customerPhone = $member ? $member->user->phone : ('0813' . rand(1000000, 9999999));

                $table = $tables[rand(1, 12)];
                $source = rand(1, 10) <= 8 ? 'qr' : 'pos';
                $paymentMethod = rand(1, 10) <= 6 ? PaymentMethod::Qris : PaymentMethod::Tunai;

                // Select 1 to 4 unique products (with weighted chance for real best-sellers)
                $numItems = rand(1, 4);
                $selectedProds = collect($weightedProducts)->shuffle()->unique('id')->take($numItems);
                $orderItems = [];
                $subtotal = 0;

                foreach ($selectedProds as $prod) {
                    $qty = rand(1, 3);
                    $lineTotal = (int) $prod->price * $qty;
                    $subtotal += $lineTotal;
                    $orderItems[] = [
                        'product_id' => $prod->id,
                        'product_name' => $prod->name,
                        'unit_price' => (int) $prod->price,
                        'quantity' => $qty,
                        'line_total' => $lineTotal,
                        'note' => rand(1, 5) === 1 ? 'Less ice' : null,
                    ];
                }

                // Check promo or voucher
                $discountAmount = 0;
                $chosenPromo = null;
                if ($member && rand(1, 5) === 1) {
                    $chosenPromo = collect($promotions)->random();
                    if ($chosenPromo->benefit_type === 'percent') {
                        $discountAmount = (int) round($subtotal * ($chosenPromo->benefit_value / 100));
                        if ($chosenPromo->max_discount) {
                            $discountAmount = min($discountAmount, (int) $chosenPromo->max_discount);
                        }
                    } else {
                        $discountAmount = min($subtotal, (int) $chosenPromo->benefit_value);
                    }
                }

                $afterDiscount = max(0, $subtotal - $discountAmount);
                $taxAmount = $taxRate > 0 ? (int) round($afterDiscount * ($taxRate / 100)) : 0;
                $totalAmount = $afterDiscount + $taxAmount;

                // Status distribution
                $status = OrderStatus::Selesai;
                if ($day === 0) {
                    // Today might have live orders
                    $stChoice = rand(1, 10);
                    if ($stChoice === 1) {
                        $status = OrderStatus::MenungguPembayaran;
                    } elseif ($stChoice === 2) {
                        $status = OrderStatus::Dibayar;
                    } elseif ($stChoice === 3) {
                        $status = OrderStatus::Diproses;
                    } elseif ($stChoice === 4) {
                        $status = OrderStatus::Siap;
                    } else {
                        $status = OrderStatus::Selesai;
                    }
                } elseif (rand(1, 30) === 1) {
                    $status = OrderStatus::Dibatalkan;
                }

                $paidAt = in_array($status, [OrderStatus::Dibayar, OrderStatus::Diproses, OrderStatus::Siap, OrderStatus::Selesai], true)
                    ? $createdAt->copy()->addMinutes(rand(2, 6))
                    : null;
                $readyAt = in_array($status, [OrderStatus::Siap, OrderStatus::Selesai], true)
                    ? $paidAt?->copy()->addMinutes(rand(4, 12))
                    : null;
                $completedAt = $status === OrderStatus::Selesai
                    ? $readyAt?->copy()->addMinutes(rand(1, 5))
                    : null;

                $datePrefix = $createdAt->format('Ymd');
                $seq = rand(1000, 9999);
                $orderNumber = "774-{$datePrefix}-{$seq}";
                while (Order::query()->where('order_number', $orderNumber)->exists()) {
                    $orderNumber = "774-{$datePrefix}-".rand(1000, 9999);
                }

                $alphabet = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
                $orderCode = '';
                for ($ci = 0; $ci < 4; $ci++) {
                    $orderCode .= $alphabet[rand(0, strlen($alphabet) - 1)];
                }

                $order = Order::query()->create([
                    'order_number' => $orderNumber,
                    'order_code' => $orderCode,
                    'public_token' => Str::random(40),
                    'client_request_id' => Str::uuid()->toString(),
                    'source' => $source,
                    'table_id' => $table->id,
                    'member_id' => $member?->id,
                    'customer_name' => $customerName,
                    'customer_email' => $customerEmail,
                    'customer_phone' => $customerPhone,
                    'status' => $status,
                    'payment_method' => $paymentMethod,
                    'subtotal' => $subtotal,
                    'discount_amount' => $discountAmount,
                    'tax_rate' => $taxRate,
                    'tax_amount' => $taxAmount,
                    'total_amount' => $totalAmount,
                    'promotion_id' => $chosenPromo?->id,
                    'is_repeat_order' => $member ? (rand(1, 5) > 1) : false,
                    'paid_at' => $paidAt,
                    'processing_at' => in_array($status, [OrderStatus::Diproses, OrderStatus::Siap, OrderStatus::Selesai], true) ? $paidAt?->copy()->addMinutes(1) : null,
                    'ready_at' => $readyAt,
                    'completed_at' => $completedAt,
                    'cancelled_at' => $status === OrderStatus::Dibatalkan ? $createdAt->copy()->addMinutes(10) : null,
                    'cancel_reason' => $status === OrderStatus::Dibatalkan ? 'Pelanggan membatalkan pesanan' : null,
                    'is_demo' => true,
                    'created_at' => $createdAt,
                    'updated_at' => $completedAt ?? $paidAt ?? $createdAt,
                ]);

                foreach ($orderItems as $oit) {
                    OrderItem::query()->create([
                        'order_id' => $order->id,
                        'product_id' => $oit['product_id'],
                        'product_name' => $oit['product_name'],
                        'unit_price' => $oit['unit_price'],
                        'quantity' => $oit['quantity'],
                        'note' => $oit['note'],
                        'line_total' => $oit['line_total'],
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);
                }

                OrderStatusHistory::query()->create([
                    'order_id' => $order->id,
                    'from_status' => null,
                    'to_status' => OrderStatus::MenungguPembayaran->value,
                    'source' => $source,
                    'reason' => 'Pesanan dibuat',
                    'created_at' => $createdAt,
                ]);

                if ($paidAt) {
                    OrderStatusHistory::query()->create([
                        'order_id' => $order->id,
                        'from_status' => OrderStatus::MenungguPembayaran->value,
                        'to_status' => OrderStatus::Dibayar->value,
                        'source' => $paymentMethod === PaymentMethod::Qris ? 'webhook' : 'pos',
                        'reason' => 'Pembayaran terverifikasi',
                        'created_at' => $paidAt,
                    ]);

                    Payment::query()->create([
                        'order_id' => $order->id,
                        'method' => $paymentMethod,
                        'status' => PaymentStatus::Paid,
                        'amount' => $totalAmount,
                        'provider' => $paymentMethod === PaymentMethod::Qris ? 'mock_qris' : 'cash',
                        'provider_reference' => 'REF-'.strtoupper(Str::random(10)),
                        'paid_at' => $paidAt,
                        'created_at' => $createdAt,
                        'updated_at' => $paidAt,
                    ]);

                    // Create Invoice
                    $invNumber = "INV-774-{$createdAt->format('Ymd')}-".str_pad((string) $order->id, 4, '0', STR_PAD_LEFT);
                    $inv = Invoice::query()->create([
                        'order_id' => $order->id,
                        'invoice_number' => $invNumber,
                        'issued_at' => $paidAt,
                        'snapshot' => [
                            'invoice_number' => $invNumber,
                            'order_number' => $order->order_number,
                            'customer_name' => $order->customer_name,
                            'customer_email' => $order->customer_email,
                            'total_amount' => $order->total_amount,
                        ],
                        'created_at' => $paidAt,
                        'updated_at' => $paidAt,
                    ]);

                    InvoiceDelivery::query()->create([
                        'invoice_id' => $inv->id,
                        'channel' => 'email',
                        'recipient' => $order->customer_email ?: 'customer@774hub.test',
                        'status' => 'sent',
                        'attempt_no' => 1,
                        'sent_at' => $paidAt->copy()->addSeconds(30),
                        'created_at' => $paidAt,
                        'updated_at' => $paidAt->copy()->addSeconds(30),
                    ]);

                    // Promotion usage
                    if ($chosenPromo && $member) {
                        $pUsage = PromotionUsage::query()->create([
                            'promotion_id' => $chosenPromo->id,
                            'member_id' => $member->id,
                            'order_id' => $order->id,
                            'discount_amount' => $discountAmount,
                            'used_at' => $paidAt,
                            'created_at' => $paidAt,
                            'updated_at' => $paidAt,
                        ]);

                        if ($chosenPromo->rule_type === PromotionRuleType::ReturnTracking) {
                            $hasReturned = rand(1, 2) === 1;
                            $daysReturn = rand(3, 14);
                            PromotionReturn::query()->create([
                                'promotion_usage_id' => $pUsage->id,
                                'member_id' => $member->id,
                                'returned_order_id' => $hasReturned ? $order->id : null,
                                'days_to_return' => $hasReturned ? $daysReturn : null,
                                'return_order_value' => $hasReturned ? $totalAmount : null,
                                'evaluated_at' => $paidAt->copy()->addDays($daysReturn),
                                'created_at' => $paidAt,
                                'updated_at' => $paidAt,
                            ]);
                        }
                    }

                    // Award loyalty points
                    if ($member && $status === OrderStatus::Selesai) {
                        $earnedPts = (int) floor($afterDiscount / 10000);
                        if ($earnedPts > 0) {
                            $member->increment('points_balance', $earnedPts);
                            \App\Models\PointLedger::query()->create([
                                'member_id' => $member->id,
                                'order_id' => $order->id,
                                'type' => 'earn',
                                'points' => $earnedPts,
                                'balance_after' => $member->points_balance,
                                'note' => "Poin transaksi {$order->order_number}",
                                'created_at' => $completedAt ?? $paidAt,
                            ]);
                        }
                    }
                }
            }
        }

        // 9. Run RFM Calculation to populate initial segments
        $rfmService = app(RfmService::class);
        $rfmService->runAnalysis(now(), 90, 'demo_seeder');
    }
}
