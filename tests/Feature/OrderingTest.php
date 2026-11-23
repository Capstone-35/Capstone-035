<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Product;
use Database\Seeders\BaseConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class OrderingTest extends TestCase
{
    use RefreshDatabase;

    protected DiningTable $table;
    protected Product $productA;
    protected Product $productB;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, BaseConfigSeeder::class]);

        $this->table = DiningTable::create([
            'number' => '05',
            'label' => 'Meja 05',
            'qr_token' => 'qr_valid_table_05',
            'is_active' => true,
        ]);

        $category = Category::create([
            'name' => 'Signature Coffee',
            'slug' => 'signature-coffee',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->productA = Product::create([
            'category_id' => $category->id,
            'name' => 'Kopi Susu Gula Aren 774',
            'slug' => 'kopi-susu-gula-aren-774',
            'price' => 25000,
            'is_active' => true,
            'is_available' => true,
        ]);

        $this->productB = Product::create([
            'category_id' => $category->id,
            'name' => 'Cold Brew Citrus',
            'slug' => 'cold-brew-citrus',
            'price' => 30000,
            'is_active' => true,
            'is_available' => true,
        ]);
    }

    public function test_scan_valid_qr_sets_session_and_shows_menu(): void
    {
        $response = $this->get("/t/{$this->table->qr_token}");
        $response->assertRedirect('/menu');
        $this->assertEquals($this->table->id, session('qr_table_id'));
        $this->assertEquals($this->table->qr_token, session('qr_token'));

        $followResponse = $this->followRedirects($response);
        $followResponse->assertStatus(200);
        $followResponse->assertSee('Meja 05');

        // Customer can also use continue as guest endpoint directly
        $continueResponse = $this->post("/t/{$this->table->qr_token}/guest");
        $continueResponse->assertRedirect('/menu');
        $this->assertEquals($this->table->id, session('qr_table_id'));
        $this->assertEquals($this->table->qr_token, session('qr_token'));
    }

    public function test_invalid_qr_returns_invalid_page(): void
    {
        $response = $this->get('/t/non_existent_qr_token');
        $response->assertStatus(200);
        $response->assertSee('QR Meja Tidak Ditemukan');
    }

    public function test_cart_management_add_update_remove(): void
    {
        // 1. Add to cart
        $response = $this->withSession([
            'qr_table_id' => $this->table->id,
            'qr_token' => $this->table->qr_token,
        ])->post('/cart/items', [
            'product_id' => $this->productA->id,
            'quantity' => 2,
            'note' => 'Less sugar',
        ]);

        $response->assertRedirect();
        $cart = session('customer_cart');
        $this->assertCount(1, $cart);
        $lineKey = array_key_first($cart);
        $this->assertEquals(2, $cart[$lineKey]['quantity']);

        // 2. Update quantity
        $response = $this->put("/cart/items/{$lineKey}", [
            'quantity' => 4,
            'note' => 'Normal sugar',
        ]);
        $response->assertRedirect('/cart');
        $cart = session('customer_cart');
        $this->assertEquals(4, $cart[$lineKey]['quantity']);

        // 3. Remove item
        $response = $this->delete("/cart/items/{$lineKey}");
        $response->assertRedirect('/cart');
        $cart = session('customer_cart');
        $this->assertEmpty($cart);
    }

    public function test_guest_checkout_creates_order_with_status_menunggu_pembayaran(): void
    {
        $lineKey = $this->productA->id . '_' . md5('');
        $clientRequestId = (string) Str::uuid();

        $response = $this->withSession([
            'qr_table_id' => $this->table->id,
            'qr_token' => $this->table->qr_token,
            'customer_cart' => [
                $lineKey => [
                    'line_key' => $lineKey,
                    'product_id' => $this->productA->id,
                    'quantity' => 2,
                    'note' => null,
                ],
            ],
        ])->post('/checkout', [
            'customer_name' => 'Fajar Pratama',
            'customer_email' => 'fajar@example.com',
            'customer_phone' => '081298765432',
            'client_request_id' => $clientRequestId,
        ]);

        $this->assertDatabaseHas('orders', [
            'table_id' => $this->table->id,
            'customer_name' => 'Fajar Pratama',
            'customer_email' => 'fajar@example.com',
            'status' => OrderStatus::MenungguPembayaran->value,
            'subtotal' => 50000,
            'client_request_id' => $clientRequestId,
        ]);

        $order = Order::where('client_request_id', $clientRequestId)->first();
        $this->assertNotNull($order);
        $this->assertEquals(OrderStatus::MenungguPembayaran, $order->status);
        $response->assertRedirect(route('order.payment', ['public_token' => $order->public_token]));
    }

    public function test_checkout_is_idempotent_with_client_request_id(): void
    {
        $lineKey = $this->productA->id . '_' . md5('');
        $clientRequestId = (string) Str::uuid();

        $sessionData = [
            'qr_table_id' => $this->table->id,
            'qr_token' => $this->table->qr_token,
            'customer_cart' => [
                $lineKey => [
                    'line_key' => $lineKey,
                    'product_id' => $this->productA->id,
                    'quantity' => 1,
                    'note' => null,
                ],
            ],
        ];

        // First attempt creates order
        $response1 = $this->withSession($sessionData)->post('/checkout', [
            'customer_name' => 'Dewi Lestari',
            'customer_email' => 'dewi@example.com',
            'client_request_id' => $clientRequestId,
        ]);

        // Duplicate submit with identical client_request_id
        $response2 = $this->withSession($sessionData)->post('/checkout', [
            'customer_name' => 'Dewi Lestari',
            'customer_email' => 'dewi@example.com',
            'client_request_id' => $clientRequestId,
        ]);

        // Ensure exactly 1 order was created
        $count = Order::where('client_request_id', $clientRequestId)->count();
        $this->assertEquals(1, $count);
    }

    public function test_server_side_pricing_protects_against_tampering(): void
    {
        $lineKey = $this->productA->id . '_' . md5('');
        $clientRequestId = (string) Str::uuid();

        // Even if client session had spoofed or arbitrary pricing data, placeOrder recalculates strictly from DB Product
        $this->withSession([
            'qr_table_id' => $this->table->id,
            'qr_token' => $this->table->qr_token,
            'customer_cart' => [
                $lineKey => [
                    'line_key' => $lineKey,
                    'product_id' => $this->productA->id,
                    'quantity' => 3,
                    'unit_price' => 1000, // Client tries to spoof 1000 instead of 25000
                ],
            ],
        ])->post('/checkout', [
            'customer_name' => 'Hacker Try',
            'customer_email' => 'hacker@example.com',
            'client_request_id' => $clientRequestId,
        ]);

        $order = Order::where('client_request_id', $clientRequestId)->first();
        $this->assertNotNull($order);
        // Correct subtotal must be 3 * 25000 = 75000
        $this->assertEquals(75000, $order->subtotal);
    }

    public function test_menu_renders_grouped_category_sections_for_scrollspy(): void
    {
        $secondCategory = Category::create([
            'name' => 'Camilan & Pastry',
            'slug' => 'camilan-pastry',
            'sort_order' => 2,
            'is_active' => true,
        ]);

        Product::create([
            'category_id' => $secondCategory->id,
            'name' => 'Croissant Butter',
            'slug' => 'croissant-butter',
            'price' => 24000,
            'is_active' => true,
            'is_available' => true,
        ]);

        $response = $this->withSession([
            'qr_table_id' => $this->table->id,
            'qr_token' => $this->table->qr_token,
        ])->get('/menu');

        $response->assertStatus(200);
        $response->assertDontSee('Semua Menu');
        $response->assertSee('Menu Andalan & Promo', false);
        $response->assertSee('data-category-chip="menu-andalan"', false);
        $response->assertSee('data-category-chip="signature-coffee"', false);
        $response->assertSee('data-category-chip="camilan-pastry"', false);
        $response->assertSee('data-category-section="menu-andalan"', false);
        $response->assertSee('data-category-section="signature-coffee"', false);
        $response->assertSee('data-category-section="camilan-pastry"', false);
        $response->assertSeeInOrder([
            'data-category-section="menu-andalan"',
            'data-category-section="signature-coffee"',
            'Kopi Susu Gula Aren 774',
            'data-category-section="camilan-pastry"',
            'Croissant Butter',
        ], false);
    }
}
