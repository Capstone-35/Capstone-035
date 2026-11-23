<?php

namespace Tests\Feature;

use App\Domain\Reporting\ReportService;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PromotionRuleType;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\User;
use Database\Seeders\BaseConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class DebugFixesRegressionTest extends TestCase
{
    use RefreshDatabase;

    protected User $owner;
    protected User $manager;
    protected User $kasir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, BaseConfigSeeder::class]);

        $this->owner = User::factory()->create(['is_active' => true]);
        $this->owner->assignRole('owner');

        $this->manager = User::factory()->create(['is_active' => true]);
        $this->manager->assignRole('manager');

        $this->kasir = User::factory()->create(['is_active' => true]);
        $this->kasir->assignRole('kasir');
    }

    public function test_owner_can_create_staff_without_phone_and_cannot_deactivate_self(): void
    {
        $response = $this->actingAs($this->owner)->post(route('owner.users.store'), [
            'name' => 'Barista Baru',
            'email' => 'barista.baru@774hub.local',
            'role' => 'kitchen',
            'password' => 'password123',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('toast_success');
        $this->assertDatabaseHas('users', [
            'email' => 'barista.baru@774hub.local',
            'phone' => null,
        ]);

        // Self-deactivation must be blocked
        $selfResp = $this->actingAs($this->owner)->patch(route('owner.users.update', ['user' => $this->owner->id]), [
            'name' => $this->owner->name,
            'email' => $this->owner->email,
            'role' => 'owner',
            'is_active' => '0',
        ]);

        $selfResp->assertSessionHas('toast_error');
        $this->assertTrue($this->owner->fresh()->is_active);
    }

    public function test_manager_promotion_update_preserves_target_segments_conditions_and_end_of_day(): void
    {
        $promo = Promotion::create([
            'code' => 'PROMO-PRESERVE',
            'name' => 'Promo Awal',
            'rule_type' => PromotionRuleType::Comeback,
            'description' => 'Deskripsi awal',
            'benefit_type' => 'percent',
            'benefit_value' => 15,
            'max_discount' => 20000,
            'min_transaction' => 30000,
            'starts_at' => now()->startOfDay(),
            'ends_at' => now()->addDays(10)->endOfDay(),
            'target_segments' => ['At Risk', 'Hibernating'],
            'conditions' => ['inactive_days' => 21],
            'usage_limit' => 100,
            'per_member_limit' => 2,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->manager)->patch(route('manager.promotions.update', ['promotion' => $promo->id]), [
            'code' => 'PROMO-PRESERVE',
            'name' => 'Promo Diperbarui',
            'rule_type' => PromotionRuleType::Comeback->value,
            'description' => 'Deskripsi baru',
            'benefit_type' => 'percent',
            'benefit_value' => 20,
            'max_discount' => 25000,
            'min_transaction' => 35000,
            'starts_at' => '2026-11-01',
            'ends_at' => '2026-11-30',
            'per_member_limit' => 2,
            'is_active' => '1',
        ]);

        $response->assertRedirect(route('manager.promotions.show', ['promotion' => $promo->id]));
        $promo->refresh();

        $this->assertSame('Promo Diperbarui', $promo->name);
        $this->assertSame(['At Risk', 'Hibernating'], $promo->target_segments);
        $this->assertSame(['inactive_days' => 21], $promo->conditions);
        $this->assertSame('2026-11-30 23:59:59', $promo->ends_at->format('Y-m-d H:i:s'));
    }

    public function test_pos_kasir_can_cancel_unpaid_order_without_reason_key(): void
    {
        $table = DiningTable::create([
            'number' => '15',
            'label' => 'Meja 15',
            'qr_token' => 'qr_pos_cancel_15',
            'is_active' => true,
        ]);

        $order = Order::create([
            'order_number' => 'ORD-CANCEL-TEST-01',
            'order_code' => '774-099',
            'public_token' => (string) Str::uuid(),
            'client_request_id' => (string) Str::uuid(),
            'table_id' => $table->id,
            'customer_name' => 'Tamu Uji',
            'channel' => 'qr_dine_in',
            'status' => OrderStatus::MenungguPembayaran,
            'payment_method' => PaymentMethod::Tunai,
            'subtotal' => 25000,
            'discount_amount' => 0,
            'tax_rate_snapshot' => 0,
            'tax_amount' => 0,
            'total_amount' => 25000,
            'placed_at' => now(),
        ]);

        $response = $this->actingAs($this->kasir)->post(route('pos.orders.cancel', ['order' => $order->order_number]));

        $response->assertRedirect();
        $response->assertSessionHas('toast_info');
        $this->assertSame(OrderStatus::Dibatalkan, $order->fresh()->status);
    }

    public function test_report_service_gracefully_handles_invalid_month_and_dates(): void
    {
        $service = app(ReportService::class);

        $periodA = $service->resolvePeriod('per_bulan', null, null, '2026-99');
        $this->assertTrue($periodA['start']->lte($periodA['end']));

        $periodB = $service->resolvePeriod('custom', 'bukan-tanggal', '2026-99-99');
        $this->assertTrue($periodB['start']->lte($periodB['end']));

        $periodC = $service->resolvePeriod('rentang_bulan', null, null, null, '2026-12', '2026-01');
        $this->assertSame('2026-01-01', $periodC['start']->toDateString());
        $this->assertSame('2026-12-31', $periodC['end']->toDateString());
    }

    public function test_deleted_product_is_auto_pruned_from_customer_cart(): void
    {
        $category = Category::create([
            'name' => 'Coffee',
            'slug' => 'coffee-prune-test',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Menu Sementara',
            'slug' => 'menu-sementara',
            'price' => 20000,
            'is_available' => true,
            'is_active' => true,
        ]);

        $lineKey = $product->id.'_'.md5('');
        $product->delete();

        $response = $this->withSession([
            'customer_cart' => [
                $lineKey => [
                    'line_key' => $lineKey,
                    'product_id' => $product->id,
                    'quantity' => 2,
                    'note' => null,
                ],
            ],
        ])->get('/cart');

        $response->assertOk();
        $this->assertEmpty(session('customer_cart'));
    }

    public function test_logged_in_staff_visiting_login_redirects_to_role_home_and_styleguide_uses_home_route(): void
    {
        $this->actingAs($this->manager)
            ->get(route('staff.login'))
            ->assertRedirect(route('manager.dashboard'));

        $this->actingAs($this->owner)
            ->get(route('styleguide'))
            ->assertOk()
            ->assertSee(route('owner.dashboard'), false);
    }
}
