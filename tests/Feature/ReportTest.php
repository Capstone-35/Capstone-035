<?php

namespace Tests\Feature;

use App\Domain\Reporting\ReportService;
use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Member;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\BaseConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $manager;
    protected User $kasir;
    protected User $kitchen;
    protected Member $memberA;
    protected Member $memberB;
    protected User $userA;
    protected User $userB;
    protected DiningTable $table;
    protected Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, BaseConfigSeeder::class]);

        $this->owner = User::factory()->create();
        $this->owner->assignRole('owner');

        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');

        $this->kasir = User::factory()->create();
        $this->kasir->assignRole('kasir');

        $this->kitchen = User::factory()->create();
        $this->kitchen->assignRole('kitchen');

        $this->userA = User::factory()->create();
        $this->userA->assignRole('member');
        $this->memberA = Member::create([
            'user_id' => $this->userA->id,
            'member_code' => 'MBR-774-0010',
            'consent_at' => now(),
            'joined_at' => now(),
        ]);

        $this->userB = User::factory()->create();
        $this->userB->assignRole('member');
        $this->memberB = Member::create([
            'user_id' => $this->userB->id,
            'member_code' => 'MBR-774-0020',
            'consent_at' => now(),
            'joined_at' => now(),
        ]);

        $this->table = DiningTable::create([
            'number' => '08',
            'label' => 'Meja 08',
            'qr_token' => 'qr_report_table_08',
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Signature Coffee',
            'slug' => 'signature-coffee',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->product = Product::create([
            'category_id' => $category->id,
            'name' => 'Americano',
            'slug' => 'americano',
            'price' => 20000,
            'is_active' => true,
            'is_available' => true,
        ]);
    }

    public function test_report_service_revenue_equals_paid_orders_sum(): void
    {
        // 1. Paid order 1: 50.000
        Order::create([
            'order_number' => '774-20261113-0011',
            'order_code' => 'RP11',
            'public_token' => (string) Str::random(32),
            'client_request_id' => (string) Str::uuid(),
            'customer_name' => 'Customer 1',
            'status' => OrderStatus::Dibayar->value,
            'subtotal' => 50000,
            'total_amount' => 55000,
            'paid_at' => now(),
        ]);

        // 2. Paid order 2 (Selesai): 40.000
        Order::create([
            'order_number' => '774-20261113-0012',
            'order_code' => 'RP12',
            'public_token' => (string) Str::random(32),
            'client_request_id' => (string) Str::uuid(),
            'customer_name' => 'Customer 2',
            'status' => OrderStatus::Selesai->value,
            'subtotal' => 40000,
            'total_amount' => 44000,
            'paid_at' => now(),
            'completed_at' => now(),
        ]);

        // 3. Cancelled order: 30.000 (must be excluded from revenue)
        Order::create([
            'order_number' => '774-20261113-0013',
            'order_code' => 'RP13',
            'public_token' => (string) Str::random(32),
            'client_request_id' => (string) Str::uuid(),
            'customer_name' => 'Customer 3',
            'status' => OrderStatus::Dibatalkan->value,
            'subtotal' => 30000,
            'total_amount' => 33000,
            'cancelled_at' => now(),
        ]);

        // 4. Unpaid order: 20.000 (must be excluded from revenue)
        Order::create([
            'order_number' => '774-20261113-0014',
            'order_code' => 'RP14',
            'public_token' => (string) Str::random(32),
            'client_request_id' => (string) Str::uuid(),
            'customer_name' => 'Customer 4',
            'status' => OrderStatus::MenungguPembayaran->value,
            'subtotal' => 20000,
            'total_amount' => 22000,
        ]);

        $reportService = app(ReportService::class);
        $period = $reportService->resolvePeriod('harian');
        $summary = $reportService->getSummary($period['start'], $period['end']);

        // Total revenue must strictly equal 55000 + 44000 = 99000
        $this->assertEquals(99000, $summary['total_revenue']);
        $this->assertEquals(2, $summary['tx_count']);
    }

    public function test_only_manager_and_owner_can_access_reports(): void
    {
        // Owner has access
        $response = $this->actingAs($this->owner)->get('/owner/reports/sales');
        $response->assertStatus(200);

        // Manager has access
        $response = $this->actingAs($this->manager)->get('/manager/reports');
        $response->assertStatus(200);

        // Kasir forbidden (403)
        $response = $this->actingAs($this->kasir)->get('/owner/reports/sales');
        $response->assertStatus(403);

        // Kitchen forbidden (403)
        $response = $this->actingAs($this->kitchen)->get('/owner/reports/sales');
        $response->assertStatus(403);
    }

    public function test_customer_data_isolation_returns_404_on_unauthorized_order(): void
    {
        // Order belongs to Member A
        $orderA = Order::create([
            'order_number' => '774-20261113-0099',
            'order_code' => 'ISO1',
            'public_token' => 'token_member_a_secret_123',
            'client_request_id' => (string) Str::uuid(),
            'member_id' => $this->memberA->id,
            'customer_name' => $this->userA->name,
            'status' => OrderStatus::Dibayar->value,
            'subtotal' => 20000,
            'total_amount' => 22000,
            'paid_at' => now(),
        ]);

        // Member B attempts to view Member A's order tracking -> 404 (anti-enumeration, Section 5.3)
        $response = $this->actingAs($this->userB)->get("/order/{$orderA->public_token}");
        $response->assertStatus(404);

        // Member B attempts to view Member A's invoice -> 404
        $response = $this->actingAs($this->userB)->get("/order/{$orderA->public_token}/invoice");
        $response->assertStatus(404);

        // Member A viewing own order -> 200
        $response = $this->actingAs($this->userA)->get("/order/{$orderA->public_token}");
        $response->assertStatus(200);
    }

    public function test_manager_can_filter_reports_by_single_month_and_month_range(): void
    {
        // Filter per bulan (Oktober 2026)
        $response = $this->actingAs($this->manager)->get('/manager/reports?period=per_bulan&month_num=10&year=2026');
        $response->assertStatus(200);
        $response->assertSee('Bulan Oktober 2026');

        // Export CSV per bulan
        $csvResponse = $this->actingAs($this->manager)->get('/manager/reports?period=per_bulan&month_num=10&year=2026&format=csv');
        $csvResponse->assertStatus(200);
        $csvResponse->assertHeader('content-disposition', 'attachment; filename=laporan-manager-774hub-bulan-2026-10.csv');

        // Filter rentang bulan (Agustus 2026 s/d November 2026)
        $rangeResponse = $this->actingAs($this->manager)->get('/manager/reports?period=rentang_bulan&month_from_num=8&year_from=2026&month_to_num=11&year_to=2026');
        $rangeResponse->assertStatus(200);
        $rangeResponse->assertSee('Rentang Bulan: Agustus 2026 – November 2026');

        // Export CSV rentang bulan
        $rangeCsvResponse = $this->actingAs($this->manager)->get('/manager/reports?period=rentang_bulan&month_from_num=8&year_from=2026&month_to_num=11&year_to=2026&format=csv');
        $rangeCsvResponse->assertStatus(200);
        $rangeCsvResponse->assertHeader('content-disposition', 'attachment; filename=laporan-manager-774hub-rentang-2026-08-sd-2026-11.csv');
    }
}
