<?php

namespace Tests\Feature;

use App\Domain\Ordering\OrderService;
use App\Domain\Ordering\OrderStateMachine;
use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\BaseConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class KdsTest extends TestCase
{
    use RefreshDatabase;

    protected DiningTable $table;
    protected Product $product;
    protected User $kitchen;
    protected User $kasir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, BaseConfigSeeder::class]);

        $this->table = DiningTable::create([
            'number' => '03',
            'label' => 'Meja 03',
            'qr_token' => 'qr_kds_table_03',
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
            'name' => 'Espresso Doppio',
            'slug' => 'espresso-doppio',
            'price' => 18000,
            'is_active' => true,
            'is_available' => true,
        ]);

        $this->kitchen = User::factory()->create();
        $this->kitchen->assignRole('kitchen');

        $this->kasir = User::factory()->create();
        $this->kasir->assignRole('kasir');
    }

    protected function createPaidOrder(): Order
    {
        $orderService = app(OrderService::class);
        $order = $orderService->create([
            'client_request_id' => (string) Str::uuid(),
            'source' => 'qr',
            'table_id' => $this->table->id,
            'customer_name' => 'Rian Hidayat',
            'customer_email' => 'rian@example.com',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 2,
                    'note' => 'Extra hot',
                ],
            ],
            'payment_method' => 'tunai',
        ]);

        $sm = app(OrderStateMachine::class);
        $sm->transition($order, OrderStatus::Dibayar, $this->kasir);

        return $order->fresh(['items', 'table']);
    }

    public function test_kitchen_user_can_access_kds(): void
    {
        $response = $this->actingAs($this->kitchen)->get('/kds');
        $response->assertStatus(200);
        $response->assertSee('Kitchen Display System');
    }

    public function test_kds_only_shows_paid_orders_not_unpaid(): void
    {
        // 1. Create an unpaid order
        $orderService = app(OrderService::class);
        $unpaidOrder = $orderService->create([
            'client_request_id' => (string) Str::uuid(),
            'source' => 'qr',
            'table_id' => $this->table->id,
            'customer_name' => 'Unpaid Customer',
            'customer_email' => 'unpaid@example.com',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'note' => null,
                ],
            ],
        ]);

        // 2. Create a paid order
        $paidOrder = $this->createPaidOrder();

        $response = $this->actingAs($this->kitchen)->get('/kds');
        $response->assertStatus(200);

        // Paid order must appear
        $response->assertSee($paidOrder->order_number);

        // Unpaid order must NEVER appear
        $response->assertDontSee($unpaidOrder->order_number);
    }

    public function test_kds_feed_strictly_omits_prices_and_revenue(): void
    {
        $paidOrder = $this->createPaidOrder();

        $response = $this->actingAs($this->kitchen)->getJson('/kds/feed');
        $response->assertStatus(200);

        $json = $response->json();
        $this->assertNotEmpty($json['orders']);
        $firstOrder = $json['orders'][0];

        // Should contain operational fields
        $this->assertArrayHasKey('order_number', $firstOrder);
        $this->assertArrayHasKey('order_code', $firstOrder);
        $this->assertArrayHasKey('items', $firstOrder);

        // Strictly NO pricing or financial keys exposed
        $this->assertArrayNotHasKey('subtotal', $firstOrder);
        $this->assertArrayNotHasKey('total_amount', $firstOrder);
        $this->assertArrayNotHasKey('discount_amount', $firstOrder);
        $this->assertArrayNotHasKey('tax_amount', $firstOrder);

        // Individual item must have product_name, quantity, note, but NO unit_price or line_total
        $firstItem = $firstOrder['items'][0];
        $this->assertArrayNotHasKey('unit_price', $firstItem);
        $this->assertArrayNotHasKey('line_total', $firstItem);
    }

    public function test_kitchen_start_processing_order(): void
    {
        $order = $this->createPaidOrder();

        $response = $this->actingAs($this->kitchen)->post("/kds/orders/{$order->order_number}/start");
        $response->assertRedirect();

        $this->assertEquals(OrderStatus::Diproses, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->processing_at);
    }

    public function test_kitchen_mark_ready_requires_quality_confirmation(): void
    {
        $order = $this->createPaidOrder();
        $sm = app(OrderStateMachine::class);
        $sm->transition($order, OrderStatus::Diproses, $this->kitchen);

        // Attempt without quality confirmation (D11 quality loop)
        $failResponse = $this->actingAs($this->kitchen)->post("/kds/orders/{$order->order_number}/ready", [
            'quality_confirmed' => 0,
        ]);
        $this->assertEquals(OrderStatus::Diproses, $order->fresh()->status);

        // Attempt with quality confirmed
        $successResponse = $this->actingAs($this->kitchen)->post("/kds/orders/{$order->order_number}/ready", [
            'quality_confirmed' => 1,
        ]);
        $successResponse->assertRedirect();

        $this->assertEquals(OrderStatus::Siap, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->ready_at);
    }

    public function test_handover_verifies_order_code_and_completes_order(): void
    {
        $order = $this->createPaidOrder();
        $sm = app(OrderStateMachine::class);
        $sm->transition($order, OrderStatus::Diproses, $this->kitchen);
        $sm->transition($order, OrderStatus::Siap, $this->kitchen);

        // 1. Wrong code rejected
        $failResponse = $this->actingAs($this->kitchen)->post("/kds/orders/{$order->order_number}/handover", [
            'order_code' => 'ZZZZ',
        ]);
        $this->assertEquals(OrderStatus::Siap, $order->fresh()->status);

        // 2. Correct code completes order
        $successResponse = $this->actingAs($this->kitchen)->post("/kds/orders/{$order->order_number}/handover", [
            'order_code' => $order->order_code,
        ]);
        $successResponse->assertRedirect();

        $fresh = $order->fresh();
        $this->assertEquals(OrderStatus::Selesai, $fresh->status);
        $this->assertNotNull($fresh->completed_at);
        $this->assertEquals($this->kitchen->id, $fresh->handed_over_by);
    }
}
