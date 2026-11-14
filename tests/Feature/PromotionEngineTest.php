<?php

namespace Tests\Feature;

use App\Domain\Ordering\OrderPricingService;
use App\Domain\Promotion\PromotionEngine;
use App\Enums\PromotionRuleType;
use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Member;
use App\Models\Order;
use App\Models\Product;
use App\Models\Promotion;
use App\Models\Reward;
use App\Models\RewardRedemption;
use App\Models\User;
use Carbon\Carbon;
use Database\Seeders\BaseConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class PromotionEngineTest extends TestCase
{
    use RefreshDatabase;

    protected DiningTable $table;
    protected Product $product;
    protected Member $member;
    protected User $memberUser;
    protected PromotionEngine $engine;
    protected OrderPricingService $pricingService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, BaseConfigSeeder::class]);

        $this->engine = app(PromotionEngine::class);
        $this->pricingService = app(OrderPricingService::class);

        $this->table = DiningTable::create([
            'number' => '07',
            'label' => 'Meja 07',
            'qr_token' => 'qr_promo_table_07',
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
            'name' => 'Latte Art 774',
            'slug' => 'latte-art-774',
            'price' => 28000,
            'is_active' => true,
            'is_available' => true,
        ]);

        $this->memberUser = User::factory()->create();
        $this->memberUser->assignRole('member');
        $this->member = Member::create([
            'user_id' => $this->memberUser->id,
            'member_code' => 'MBR-774-0088',
            'points_balance' => 10,
            'consent_at' => now(),
            'joined_at' => now(),
        ]);
    }

    public function test_comeback_promo_eligible_for_inactive_member(): void
    {
        $promo = Promotion::create([
            'code' => 'COMEBACK-20',
            'name' => 'Comeback Promo 20%',
            'rule_type' => PromotionRuleType::Comeback->value,
            'benefit_type' => 'percent',
            'benefit_value' => 20,
            'min_transaction' => 50000,
            'target_segments' => ['all'],
            'conditions' => ['inactive_days' => 30],
            'is_active' => true,
        ]);

        // Scenario 1: Member last visited 45 days ago -> eligible
        $order1 = Order::create([
            'order_number' => '774-20260901-0001',
            'order_code' => 'CB01',
            'public_token' => (string) Str::random(32),
            'client_request_id' => (string) Str::uuid(),
            'member_id' => $this->member->id,
            'customer_name' => $this->memberUser->name,
            'status' => 'Selesai',
            'subtotal' => 60000,
            'total_amount' => 60000,
            'paid_at' => now()->subDays(45),
            'completed_at' => now()->subDays(45),
        ]);
        $order1->created_at = now()->subDays(45);
        $order1->save();

        $this->assertTrue($this->engine->isEligible($promo, $this->member, 60000, now()));

        // Scenario 2: Active member who visited yesterday -> not eligible
        Order::create([
            'order_number' => '774-20261112-0001',
            'order_code' => 'CB02',
            'public_token' => (string) Str::random(32),
            'client_request_id' => (string) Str::uuid(),
            'member_id' => $this->member->id,
            'customer_name' => $this->memberUser->name,
            'status' => 'Selesai',
            'subtotal' => 60000,
            'total_amount' => 60000,
            'paid_at' => now()->subDay(),
            'completed_at' => now()->subDay(),
        ]);

        $this->assertFalse($this->engine->isEligible($promo, $this->member, 60000, now()));
    }

    public function test_repeat_order_reward_rule(): void
    {
        $promo = Promotion::create([
            'code' => 'REPEAT-5X',
            'name' => 'Reward Repeat Order 5x',
            'rule_type' => PromotionRuleType::RepeatOrder->value,
            'benefit_type' => 'nominal',
            'benefit_value' => 15000,
            'min_transaction' => 40000,
            'target_segments' => ['all'],
            'conditions' => ['min_orders' => 5, 'window_days' => 30],
            'is_active' => true,
        ]);

        // Member currently has 0 orders in last 30 days -> ineligible
        $this->assertFalse($this->engine->isEligible($promo, $this->member, 50000, now()));

        // Create 5 successful orders within 30 days
        for ($i = 1; $i <= 5; $i++) {
            Order::create([
                'order_number' => "774-2026110{$i}-0001",
                'order_code' => "RP0{$i}",
                'public_token' => (string) Str::random(32),
                'client_request_id' => (string) Str::uuid(),
                'member_id' => $this->member->id,
                'customer_name' => $this->memberUser->name,
                'status' => 'Selesai',
                'subtotal' => 50000,
                'total_amount' => 50000,
                'paid_at' => now()->subDays(5 - $i),
                'completed_at' => now()->subDays(5 - $i),
            ]);
        }

        // Now eligible
        $this->assertTrue($this->engine->isEligible($promo, $this->member, 50000, now()));
    }

    public function test_slow_hour_promo_rule(): void
    {
        $promo = Promotion::create([
            'code' => 'SLOW-AFTERNOON',
            'name' => 'Happy Hours 14-17',
            'rule_type' => PromotionRuleType::SlowHour->value,
            'benefit_type' => 'percent',
            'benefit_value' => 15,
            'min_transaction' => 30000,
            'target_segments' => ['all'],
            'conditions' => [
                'days' => [1, 2, 3, 4, 5], // Mon-Fri
                'start_time' => '14:00',
                'end_time' => '17:00',
            ],
            'is_active' => true,
        ]);

        // Wednesday 15:30 -> eligible
        $wednesdayAfternoon = Carbon::parse('2026-11-18 15:30:00'); // Wednesday
        $this->assertTrue($this->engine->isEligible($promo, $this->member, 50000, $wednesdayAfternoon));

        // Wednesday 20:00 -> outside hours, ineligible
        $wednesdayNight = Carbon::parse('2026-11-18 20:00:00');
        $this->assertFalse($this->engine->isEligible($promo, $this->member, 50000, $wednesdayNight));
    }

    public function test_at_most_one_promo_or_reward_per_order_constraint(): void
    {
        $promo = Promotion::create([
            'code' => 'DISC-10',
            'name' => 'Diskon 10%',
            'rule_type' => PromotionRuleType::SlowHour->value,
            'benefit_type' => 'percent',
            'benefit_value' => 10,
            'min_transaction' => 0,
            'target_segments' => ['all'],
            'conditions' => ['days' => [1, 2, 3, 4, 5, 6, 7], 'start_time' => '00:00', 'end_time' => '23:59'],
            'is_active' => true,
        ]);

        $reward = Reward::create([
            'name' => 'Voucher 5rb',
            'points_cost' => 5,
            'benefit_type' => 'nominal',
            'benefit_value' => 5000,
            'valid_days' => 30,
            'is_active' => true,
        ]);

        $redemption = RewardRedemption::create([
            'member_id' => $this->member->id,
            'reward_id' => $reward->id,
            'voucher_code' => 'RWD-TEST01',
            'points_spent' => 5,
            'status' => 'active',
            'redeemed_at' => now(),
            'expires_at' => now()->addDays(30),
        ]);

        $cartLines = [
            ['product_id' => $this->product->id, 'quantity' => 2],
        ];

        // Using both promotion_id AND reward_redemption_id MUST throw ValidationException (Decision D14)
        $this->expectException(ValidationException::class);
        $this->pricingService->calculate(
            $cartLines,
            $this->member,
            $promo->id,
            $redemption->id,
            true
        );
    }

    public function test_guest_cannot_apply_promotions(): void
    {
        $promo = Promotion::create([
            'code' => 'PROMO-MEMBER',
            'name' => 'Promo Spesial',
            'rule_type' => PromotionRuleType::SlowHour->value,
            'benefit_type' => 'percent',
            'benefit_value' => 10,
            'min_transaction' => 0,
            'target_segments' => ['all'],
            'conditions' => ['days' => [1, 2, 3, 4, 5, 6, 7], 'start_time' => '00:00', 'end_time' => '23:59'],
            'is_active' => true,
        ]);

        $cartLines = [
            ['product_id' => $this->product->id, 'quantity' => 1],
        ];

        // Guest ($member = null) attempting to use promo MUST throw ValidationException (Decision D13)
        $this->expectException(ValidationException::class);
        $this->pricingService->calculate(
            $cartLines,
            null, // Guest
            $promo->id,
            null,
            true
        );
    }
}
