<?php

namespace Tests\Feature;

use App\Enums\OrderStatus;
use App\Enums\PaymentMethod;
use App\Enums\PaymentStatus;
use App\Models\DiningTable;
use App\Models\Order;
use App\Models\Payment;
use App\Models\User;
use Database\Seeders\BaseConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QrisSimulatorTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;
    protected DiningTable $table;
    protected Order $order;
    protected Payment $payment;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, BaseConfigSeeder::class]);

        $this->manager = User::factory()->create([
            'email' => 'manager.qris@774hub.test',
        ]);
        $this->manager->assignRole('manager');

        $this->table = DiningTable::create([
            'number' => 88,
            'label' => 'Meja 88',
            'qr_token' => 'table-token-test-88',
            'is_active' => true,
        ]);

        $this->order = Order::create([
            'order_number' => 'ORD-TEST-QRIS-01',
            'order_code' => '8888',
            'public_token' => 'tok-test-qris-01',
            'client_request_id' => 'req-test-qris-01',
            'customer_name' => 'Budi Customer',
            'customer_email' => 'budi.customer@test.com',
            'table_id' => $this->table->id,
            'status' => OrderStatus::MenungguPembayaran,
            'payment_method' => PaymentMethod::Qris,
            'subtotal' => 50000,
            'tax_amount' => 0,
            'total_amount' => 50000,
        ]);

        $this->payment = Payment::create([
            'order_id' => $this->order->id,
            'method' => PaymentMethod::Qris,
            'status' => PaymentStatus::Pending,
            'amount' => 50000,
            'provider' => 'mock_qris',
            'provider_reference' => 'QRIS-REF-TEST-8888',
            'qris_payload' => '00020101021226670014ID.LINKAJA.WWW0118936009110022288885204581253033605802ID5911774 HUB CAFE6007BANDUNG61054011562200716ORD-TEST-QRIS-016304ABCD',
            'expires_at' => now()->addMinutes(15),
        ]);
    }

    public function test_manager_can_view_qris_sandbox_page(): void
    {
        $response = $this->actingAs($this->manager)->get('/sandbox/payments');
        $response->assertStatus(200);
        $response->assertSee('Simulator Callback Webhook QRIS');
        $response->assertSee('QRIS-REF-TEST-8888');
    }

    public function test_manager_can_trigger_qris_simulation_paid_without_custom_event_id(): void
    {
        // Testing trigger POST without passing custom_event_id input (form default)
        $response = $this->actingAs($this->manager)->post('/sandbox/payments/trigger', [
            'payment_id' => $this->payment->id,
            'scenario' => 'paid',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('toast_success');

        $this->assertEquals(OrderStatus::Dibayar, $this->order->fresh()->status);
        $this->assertEquals(PaymentStatus::Paid, $this->payment->fresh()->status);
    }

    public function test_manager_can_trigger_qris_simulation_duplicate(): void
    {
        // First payment succeeds
        $this->actingAs($this->manager)->post('/sandbox/payments/trigger', [
            'payment_id' => $this->payment->id,
            'scenario' => 'paid',
        ]);

        // Duplicate replay event
        $response = $this->actingAs($this->manager)->post('/sandbox/payments/trigger', [
            'payment_id' => $this->payment->id,
            'scenario' => 'duplicate',
        ]);

        $response->assertRedirect();
        $this->assertEquals(OrderStatus::Dibayar, $this->order->fresh()->status);
    }

    public function test_customer_can_view_qris_payment_page_with_svg(): void
    {
        $response = $this->get("/order/{$this->order->public_token}/payment");
        $response->assertStatus(200);
        $response->assertSee('Pembayaran QRIS — 774 HUB');
        $response->assertSee('QRIS-REF-TEST-8888');
        $response->assertSee('<svg', false);
    }

    public function test_customer_can_simulate_qris_payment_success(): void
    {
        $response = $this->post("/order/{$this->order->public_token}/payment/simulate-qris", [
            'scenario' => 'paid',
        ]);

        $response->assertRedirect("/order/{$this->order->public_token}");
        $this->assertEquals(OrderStatus::Dibayar, $this->order->fresh()->status);
    }

    public function test_manager_can_generate_table_qr_svg(): void
    {
        $response = $this->actingAs($this->manager)->get("/manager/tables/{$this->table->id}/qr.svg");
        $response->assertStatus(200);
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $response->assertSee('<svg', false);
    }
}
