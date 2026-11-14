<?php

namespace Tests\Feature;

use App\Domain\Ordering\OrderService;
use App\Domain\Ordering\OrderStateMachine;
use App\Domain\Payment\Drivers\MockQrisGateway;
use App\Domain\Payment\PaymentService;
use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\BaseConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PaymentTest extends TestCase
{
    use RefreshDatabase;

    protected DiningTable $table;
    protected Product $product;
    protected User $kasir;
    protected User $kitchen;
    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, BaseConfigSeeder::class]);

        $this->table = DiningTable::create([
            'number' => '02',
            'label' => 'Meja 02',
            'qr_token' => 'qr_table_02_payment_test',
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
            'name' => 'Kopi Tubruk 774',
            'slug' => 'kopi-tubruk-774',
            'price' => 20000,
            'is_active' => true,
            'is_available' => true,
        ]);

        $this->kasir = User::factory()->create();
        $this->kasir->assignRole('kasir');

        $this->kitchen = User::factory()->create();
        $this->kitchen->assignRole('kitchen');

        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');
    }

    protected function createTestOrder(int $qty = 2): Order
    {
        $orderService = app(OrderService::class);
        return $orderService->create([
            'client_request_id' => (string) Str::uuid(),
            'source' => 'qr',
            'table_id' => $this->table->id,
            'customer_name' => 'Andi Wijaya',
            'customer_email' => 'andi@example.com',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => $qty,
                    'note' => null,
                ],
            ],
            'payment_method' => 'tunai',
        ]);
    }

    public function test_order_state_machine_valid_lifecycle(): void
    {
        $order = $this->createTestOrder();
        $sm = app(OrderStateMachine::class);

        $this->assertEquals(OrderStatus::MenungguPembayaran, $order->status);

        // Menunggu Pembayaran -> Dibayar
        $sm->transition($order, OrderStatus::Dibayar, $this->kasir);
        $this->assertEquals(OrderStatus::Dibayar, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->paid_at);

        // Dibayar -> Diproses
        $sm->transition($order, OrderStatus::Diproses, $this->kitchen);
        $this->assertEquals(OrderStatus::Diproses, $order->fresh()->status);

        // Diproses -> Siap
        $sm->transition($order, OrderStatus::Siap, $this->kitchen);
        $this->assertEquals(OrderStatus::Siap, $order->fresh()->status);

        // Siap -> Selesai
        $sm->transition($order, OrderStatus::Selesai, $this->kasir);
        $this->assertEquals(OrderStatus::Selesai, $order->fresh()->status);
        $this->assertNotNull($order->fresh()->completed_at);
    }

    public function test_order_state_machine_illegal_skip_throws_exception(): void
    {
        $this->expectException(ValidationException::class);

        $order = $this->createTestOrder();
        $sm = app(OrderStateMachine::class);

        // Cannot skip directly from Menunggu Pembayaran to Selesai
        $sm->transition($order, OrderStatus::Selesai, $this->kasir);
    }

    public function test_cancellation_rules_per_actor(): void
    {
        $sm = app(OrderStateMachine::class);

        // 1. Customer cancels while Menunggu Pembayaran
        $order1 = $this->createTestOrder();
        $sm->transition($order1, OrderStatus::Dibatalkan, null, 'customer', 'Customer changed mind');
        $this->assertEquals(OrderStatus::Dibatalkan, $order1->fresh()->status);

        // 2. Once Dibayar, non-manager cannot cancel
        $order2 = $this->createTestOrder();
        $sm->transition($order2, OrderStatus::Dibayar, $this->kasir);

        $this->expectException(ValidationException::class);
        $sm->transition($order2, OrderStatus::Dibatalkan, $this->kasir, 'pos', 'Kasir trying to cancel');
    }

    public function test_manager_can_cancel_paid_order_with_reason(): void
    {
        $order = $this->createTestOrder();
        $sm = app(OrderStateMachine::class);

        $sm->transition($order, OrderStatus::Dibayar, $this->kasir);
        $this->assertEquals(OrderStatus::Dibayar, $order->fresh()->status);

        // Manager cancels with mandatory reason
        $sm->transition($order, OrderStatus::Dibatalkan, $this->manager, 'manager', 'Barista kehabisan bahan biji kopi');
        $this->assertEquals(OrderStatus::Dibatalkan, $order->fresh()->status);
        $this->assertEquals('Barista kehabisan bahan biji kopi', $order->fresh()->cancel_reason);
    }

    public function test_kasir_confirm_cash_payment(): void
    {
        $order = $this->createTestOrder();
        $paymentService = app(PaymentService::class);

        // Kasir confirms cash payment
        $paidOrder = $paymentService->confirmCash($order, $this->kasir, 50000);

        $this->assertEquals(OrderStatus::Dibayar, $paidOrder->status);
        $this->assertNotNull($paidOrder->paid_at);

        $payment = $paidOrder->payments()->where('status', PaymentStatus::Paid->value)->first();
        $this->assertNotNull($payment);
        $this->assertEquals(PaymentMethod::Tunai, $payment->method);
        $this->assertEquals(50000, $payment->cash_received);
    }

    public function test_qris_webhook_valid_signature_transitions_order_to_dibayar(): void
    {
        $order = $this->createTestOrder();
        $paymentService = app(PaymentService::class);

        // Initiate QRIS payment
        $payment = $paymentService->selectPaymentMethod($order, PaymentMethod::Qris);

        $eventId = 'evt_test_' . Str::random(10);
        $payload = [
            'event_id' => $eventId,
            'provider_reference' => $payment->provider_reference,
            'status' => 'paid',
            'amount' => $order->total_amount,
            'order_number' => $order->order_number,
        ];
        $signature = MockQrisGateway::signPayload($payload);

        $response = $this->postJson('/webhooks/payments/mock_qris', $payload, [
            'X-Webhook-Signature' => $signature,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'result' => 'paid',
            'order_number' => $order->order_number,
            'order_status' => OrderStatus::Dibayar->value,
        ]);

        $this->assertEquals(OrderStatus::Dibayar, $order->fresh()->status);
    }

    public function test_qris_webhook_rejects_invalid_signature(): void
    {
        $order = $this->createTestOrder();
        $paymentService = app(PaymentService::class);
        $payment = $paymentService->selectPaymentMethod($order, PaymentMethod::Qris);

        $payload = [
            'event_id' => 'evt_fake_' . Str::random(8),
            'provider_reference' => $payment->provider_reference,
            'status' => 'paid',
            'amount' => $order->total_amount,
            'order_number' => $order->order_number,
        ];

        $response = $this->postJson('/webhooks/payments/mock_qris', $payload, [
            'X-Webhook-Signature' => 'fake_invalid_hmac_signature',
        ]);

        $response->assertStatus(401);
        $this->assertEquals(OrderStatus::MenungguPembayaran, $order->fresh()->status);
    }

    public function test_qris_webhook_rejects_amount_mismatch(): void
    {
        $order = $this->createTestOrder();
        $paymentService = app(PaymentService::class);
        $payment = $paymentService->selectPaymentMethod($order, PaymentMethod::Qris);

        $payload = [
            'event_id' => 'evt_mismatch_' . Str::random(8),
            'provider_reference' => $payment->provider_reference,
            'status' => 'paid',
            'amount' => $order->total_amount + 5000, // Amount mismatch
            'order_number' => $order->order_number,
        ];
        $signature = MockQrisGateway::signPayload($payload);

        $response = $this->postJson('/webhooks/payments/mock_qris', $payload, [
            'X-Webhook-Signature' => $signature,
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'result' => 'amount_mismatch',
        ]);
        $this->assertEquals(OrderStatus::MenungguPembayaran, $order->fresh()->status);
    }

    public function test_qris_webhook_idempotency_duplicate_event_ignored(): void
    {
        $order = $this->createTestOrder();
        $paymentService = app(PaymentService::class);
        $payment = $paymentService->selectPaymentMethod($order, PaymentMethod::Qris);

        $eventId = 'evt_idem_' . Str::random(10);
        $payload = [
            'event_id' => $eventId,
            'provider_reference' => $payment->provider_reference,
            'status' => 'paid',
            'amount' => $order->total_amount,
            'order_number' => $order->order_number,
        ];
        $signature = MockQrisGateway::signPayload($payload);

        // First call
        $response1 = $this->postJson('/webhooks/payments/mock_qris', $payload, [
            'X-Webhook-Signature' => $signature,
        ]);
        $response1->assertStatus(200);
        $response1->assertJson(['result' => 'paid']);

        // Second call with duplicate event_id
        $response2 = $this->postJson('/webhooks/payments/mock_qris', $payload, [
            'X-Webhook-Signature' => $signature,
        ]);
        $response2->assertStatus(200);
        $response2->assertJson(['result' => 'duplicate_ignored']);
    }
}
