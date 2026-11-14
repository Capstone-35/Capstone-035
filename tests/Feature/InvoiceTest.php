<?php

namespace Tests\Feature;

use App\Domain\Invoice\InvoiceService;
use App\Domain\Ordering\OrderService;
use App\Domain\Ordering\OrderStateMachine;
use App\Domain\Payment\PaymentService;
use App\Enums\InvoiceDeliveryStatus;
use App\Enums\OrderStatus;
use App\Jobs\SendInvoiceDeliveryJob;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Invoice;
use App\Models\InvoiceDelivery;
use App\Models\Order;
use App\Models\Product;
use App\Models\User;
use Database\Seeders\BaseConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Tests\TestCase;

class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    protected DiningTable $table;
    protected Product $product;
    protected User $kasir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, BaseConfigSeeder::class]);

        $this->table = DiningTable::create([
            'number' => '04',
            'label' => 'Meja 04',
            'qr_token' => 'qr_invoice_table_04',
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
            'name' => 'Caramel Macchiato',
            'slug' => 'caramel-macchiato',
            'price' => 32000,
            'is_active' => true,
            'is_available' => true,
        ]);

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
            'customer_name' => 'Bambang Sudiro',
            'customer_email' => 'bambang@example.com',
            'customer_phone' => '081234567899',
            'items' => [
                [
                    'product_id' => $this->product->id,
                    'quantity' => 1,
                    'note' => null,
                ],
            ],
            'payment_method' => 'tunai',
        ]);

        $paymentService = app(PaymentService::class);
        return $paymentService->confirmCash($order, $this->kasir, 40000);
    }

    public function test_invoice_automatically_generated_when_order_paid(): void
    {
        $order = $this->createPaidOrder();

        $this->assertEquals(OrderStatus::Dibayar, $order->status);

        $invoice = Invoice::where('order_id', $order->id)->first();
        $this->assertNotNull($invoice);
        $this->assertStringStartsWith('INV-774-', $invoice->invoice_number);
        $this->assertEquals('Bambang Sudiro', $invoice->snapshot['customer_name']);
        $this->assertEquals('bambang@example.com', $invoice->snapshot['customer_email']);
        $this->assertEquals(35200, $invoice->snapshot['total_amount']);
    }

    public function test_invoice_delivery_failure_leaves_order_status_intact(): void
    {
        $order = $this->createPaidOrder();
        $invoice = Invoice::where('order_id', $order->id)->first();
        $this->assertNotNull($invoice);

        // Simulate a delivery failure
        $delivery = InvoiceDelivery::create([
            'invoice_id' => $invoice->id,
            'channel' => 'email',
            'recipient' => 'invalid-email-address',
            'status' => InvoiceDeliveryStatus::Pending,
            'attempt_no' => 1,
            'triggered_by' => 'system',
        ]);

        // Mark failed after 3 attempts
        $delivery->update([
            'attempt_no' => 3,
            'status' => InvoiceDeliveryStatus::Failed,
            'error_message' => 'Connection timeout to SMTP gateway',
        ]);

        $this->assertEquals(InvoiceDeliveryStatus::Failed, $delivery->fresh()->status);
        $this->assertEquals(3, $delivery->fresh()->attempt_no);

        // NFR-07: Order status remains Dibayar, never modified or cancelled
        $this->assertEquals(OrderStatus::Dibayar, $order->fresh()->status);
    }

    public function test_pos_kasir_can_resend_invoice(): void
    {
        $order = $this->createPaidOrder();
        $invoice = Invoice::where('order_id', $order->id)->first();

        $response = $this->actingAs($this->kasir)->post("/pos/invoices/{$invoice->id}/resend", [
            'channel' => 'email',
            'recipient' => 'new_email@example.com',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('invoice_deliveries', [
            'invoice_id' => $invoice->id,
            'recipient' => 'new_email@example.com',
            'channel' => 'email',
        ]);
    }

    public function test_customer_can_view_invoice_page(): void
    {
        $order = $this->createPaidOrder();

        $response = $this->withSession([
            'tracked_order_tokens' => [$order->public_token],
        ])->get("/order/{$order->public_token}/invoice");

        $response->assertStatus(200);
        $response->assertSee('Invoice Digital');
        $response->assertSee($order->order_number);
        $response->assertSee('Caramel Macchiato');
    }
}
