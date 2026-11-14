<?php

namespace Tests\Feature;

use App\Domain\Loyalty\LoyaltyService;
use App\Domain\Ordering\OrderService;
use App\Domain\Ordering\OrderStateMachine;
use App\Domain\Payment\PaymentService;
use App\Enums\OrderStatus;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Member;
use App\Models\Order;
use App\Models\PointLedger;
use App\Models\Product;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use Database\Seeders\BaseConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class LoyaltyTest extends TestCase
{
    use RefreshDatabase;

    protected DiningTable $table;
    protected Product $product;
    protected Member $member;
    protected User $memberUser;
    protected User $kasir;
    protected User $kitchen;
    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, BaseConfigSeeder::class]);

        $this->table = DiningTable::create([
            'number' => '06',
            'label' => 'Meja 06',
            'qr_token' => 'qr_loyalty_table_06',
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
            'name' => 'V60 Pour Over',
            'slug' => 'v60-pour-over',
            'price' => 30000,
            'is_active' => true,
            'is_available' => true,
        ]);

        $this->memberUser = User::factory()->create();
        $this->memberUser->assignRole('member');
        $this->member = Member::create([
            'user_id' => $this->memberUser->id,
            'member_code' => 'MBR-774-0099',
            'points_balance' => 0,
            'consent_at' => now(),
            'joined_at' => now(),
        ]);

        $this->kasir = User::factory()->create();
        $this->kasir->assignRole('kasir');

        $this->kitchen = User::factory()->create();
        $this->kitchen->assignRole('kitchen');

        $this->manager = User::factory()->create();
        $this->manager->assignRole('manager');
    }

    protected function createMemberOrder(int $qty = 2): Order
    {
        $orderService = app(OrderService::class);
        return $orderService->create([
            'client_request_id' => (string) Str::uuid(),
            'source' => 'qr',
            'table_id' => $this->table->id,
            'member_id' => $this->member->id,
            'customer_name' => $this->memberUser->name,
            'customer_email' => $this->memberUser->email,
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

    public function test_points_awarded_only_when_order_is_selesai_by_default(): void
    {
        $orderService = app(OrderService::class);
        $order = $this->createMemberOrder(2);
        $sm = app(OrderStateMachine::class);
        $paymentService = app(PaymentService::class);

        // Menunggu Pembayaran -> Dibayar (total amount with 10% tax = 66.000)
        $paymentService->confirmCash($order, $this->kasir, 70000);
        $this->assertEquals(0, $this->member->fresh()->points_balance);

        // Dibayar -> Diproses
        $orderService->startProcessing($order, $this->kitchen);
        $this->assertEquals(0, $this->member->fresh()->points_balance);

        // Diproses -> Siap
        $orderService->markReady($order, $this->kitchen, true);
        $this->assertEquals(0, $this->member->fresh()->points_balance);

        // Siap -> Selesai via Handover
        $orderService->handover($order, $this->kasir, $order->order_code);

        // Now points MUST be awarded (60.000 / 10.000 = 6 points)
        $this->assertEquals(6, $this->member->fresh()->points_balance);
        $this->assertDatabaseHas('point_ledger', [
            'member_id' => $this->member->id,
            'order_id' => $order->id,
            'type' => 'earn',
            'points' => 6,
        ]);
    }

    public function test_points_calculation_floor_formula(): void
    {
        $orderService = app(OrderService::class);

        $customProduct = Product::create([
            'category_id' => $this->product->category_id,
            'name' => 'Single Espresso',
            'slug' => 'single-espresso-loyalty',
            'price' => 25000,
            'is_active' => true,
            'is_available' => true,
        ]);

        $order = $orderService->create([
            'client_request_id' => (string) Str::uuid(),
            'source' => 'qr',
            'table_id' => $this->table->id,
            'member_id' => $this->member->id,
            'customer_name' => $this->memberUser->name,
            'customer_email' => $this->memberUser->email,
            'items' => [
                [
                    'product_id' => $customProduct->id,
                    'quantity' => 1,
                    'note' => null,
                ],
            ],
            'payment_method' => 'tunai',
        ]);

        $paymentService = app(PaymentService::class);
        $paymentService->confirmCash($order, $this->kasir, 30000);

        $orderService->startProcessing($order, $this->kitchen);
        $orderService->markReady($order, $this->kitchen, true);
        $orderService->handover($order, $this->kasir, $order->order_code);

        // 25.000 floor / 10.000 = 2 points
        $this->assertEquals(2, $this->member->fresh()->points_balance);
    }

    public function test_guest_and_cancelled_orders_never_produce_points(): void
    {
        $orderService = app(OrderService::class);

        // 1. Guest order
        $guestOrder = $orderService->create([
            'client_request_id' => (string) Str::uuid(),
            'source' => 'qr',
            'table_id' => $this->table->id,
            'customer_name' => 'Guest Person',
            'customer_email' => 'guest@example.com',
            'items' => [
                ['product_id' => $this->product->id, 'quantity' => 2, 'note' => null],
            ],
            'payment_method' => 'tunai',
        ]);

        $paymentService = app(PaymentService::class);
        $paymentService->confirmCash($guestOrder, $this->kasir, 70000);

        $orderService->startProcessing($guestOrder, $this->kitchen);
        $orderService->markReady($guestOrder, $this->kitchen, true);
        $orderService->handover($guestOrder, $this->kasir, $guestOrder->order_code);

        // Zero point ledgers for guest order
        $this->assertDatabaseMissing('point_ledger', ['order_id' => $guestOrder->id]);
    }

    public function test_reward_redemption_and_insufficient_points_validation(): void
    {
        $loyaltyService = app(LoyaltyService::class);

        // Give member 20 points
        $this->member->update(['points_balance' => 20]);

        $reward = Reward::create([
            'name' => 'Potongan Rp10.000',
            'points_cost' => 15,
            'benefit_type' => 'nominal',
            'benefit_value' => 10000,
            'valid_days' => 30,
            'is_active' => true,
        ]);

        // 1. Successful redemption
        $redemption = $loyaltyService->redeemReward($this->member, $reward);
        $this->assertEquals(5, $this->member->fresh()->points_balance);
        $this->assertEquals('active', $redemption->status);
        $this->assertNotNull($redemption->voucher_code);

        // 2. Second attempt fails due to insufficient points (has 5, needs 15)
        $this->expectException(ValidationException::class);
        $loyaltyService->redeemReward($this->member->fresh(), $reward);
    }

    public function test_manager_cancellation_reverses_earned_points(): void
    {
        $orderService = app(OrderService::class);
        $order = $this->createMemberOrder(2);
        $paymentService = app(PaymentService::class);
        $paymentService->confirmCash($order, $this->kasir, 70000);

        $orderService->startProcessing($order, $this->kitchen);
        $orderService->markReady($order, $this->kitchen, true);
        $orderService->handover($order, $this->kasir, $order->order_code);

        $this->assertEquals(6, $this->member->fresh()->points_balance);

        // 2. Manager cancels the order
        $loyaltyService = app(LoyaltyService::class);
        $loyaltyService->reverseForCancelledOrder($order);

        $this->assertEquals(0, $this->member->fresh()->points_balance);
        $this->assertDatabaseHas('point_ledger', [
            'member_id' => $this->member->id,
            'order_id' => $order->id,
            'type' => 'reversal',
            'points' => -6,
        ]);
    }
}
