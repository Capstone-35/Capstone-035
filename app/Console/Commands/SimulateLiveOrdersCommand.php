<?php

namespace App\Console\Commands;

use App\Domain\Ordering\OrderService;
use App\Domain\Payment\PaymentService;
use App\Enums\PaymentMethod;
use App\Models\DiningTable;
use App\Models\Member;
use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

class SimulateLiveOrdersCommand extends Command
{
    protected $signature = 'demo:simulate-orders {--count=3 : Jumlah pesanan baru yang disimulasikan} {--paid : Langsung tandai sebagian sebagai Dibayar agar tampil di KDS}';

    protected $description = 'Membuat pesanan langsung (live) untuk demonstrasi POS dan KDS pada 774 HUB';

    public function handle(OrderService $orderService, PaymentService $paymentService): int
    {
        if (app()->environment('production') || ! config('app.demo_mode', true)) {
            $this->error('Perintah demo:simulate-orders hanya dapat dijalankan ketika APP_DEMO_MODE=true dan bukan di production.');

            return self::FAILURE;
        }

        $count = max(1, min(20, (int) $this->option('count')));
        $products = Product::query()->where('is_active', true)->where('is_available', true)->get();
        $tables = DiningTable::query()->where('is_active', true)->get();
        $members = Member::query()->with('user')->get();

        if ($products->isEmpty() || $tables->isEmpty()) {
            $this->error('Data produk atau meja belum tersedia. Jalankan php artisan migrate:fresh --seed terlebih dahulu.');

            return self::FAILURE;
        }

        for ($i = 0; $i < $count; $i++) {
            $table = $tables->random();
            $useMember = $members->isNotEmpty() && ($i % 2 === 0);
            $member = $useMember ? $members->random() : null;

            $picked = $products->random(min(2, $products->count()));
            $items = [];
            foreach ($picked as $prod) {
                $items[] = [
                    'product_id' => $prod->id,
                    'quantity' => random_int(1, 2),
                    'note' => $i === 0 ? 'Less sugar, es sedikit' : null,
                ];
            }

            $method = ($i % 2 === 0) ? PaymentMethod::Qris->value : PaymentMethod::Tunai->value;

            $order = $orderService->create([
                'client_request_id' => (string) Str::uuid(),
                'source' => 'qr',
                'table_id' => $table->id,
                'member_id' => $member?->id,
                'customer_name' => $member ? $member->user->name : 'Tamu Simulasi '.($i + 1),
                'customer_email' => $member ? $member->user->email : 'tamu'.($i + 1).'@example.test',
                'items' => $items,
                'payment_method' => $method,
            ]);

            $order->update(['is_demo' => true]);

            if ($this->option('paid') && $i === 0) {
                $kasir = \App\Models\User::role('kasir')->first();
                if ($kasir) {
                    $paymentService->confirmCash($order, $kasir, $order->total_amount);
                }
            }

            $this->line("✔ Pesanan dibuat: {$order->order_number} (Kode: {$order->order_code}) — {$table->label} — Status: {$order->fresh()->status->value}");
        }

        return self::SUCCESS;
    }
}
