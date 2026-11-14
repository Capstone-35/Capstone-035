<?php

namespace Tests\Feature;

use App\Enums\PromotionRuleType;
use App\Models\Promotion;
use App\Models\User;
use Database\Seeders\BaseConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ManagerPromotionTest extends TestCase
{
    use RefreshDatabase;

    protected User $manager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, BaseConfigSeeder::class, \Database\Seeders\DemoDataSeeder::class]);

        $this->manager = User::factory()->create([
            'email' => 'manager.test@774hub.test',
        ]);
        $this->manager->assignRole('manager');
    }

    public function test_manager_can_view_promotions_index(): void
    {
        $response = $this->actingAs($this->manager)->get('/manager/promotions');
        $response->assertStatus(200);
        $response->assertSee('Daftar 6 Aturan Promosi Behavioral');
        $response->assertSee('COMEBACK15');
        $response->assertSee('REPEATREWARD');
    }

    public function test_manager_can_view_promotion_create_page(): void
    {
        $response = $this->actingAs($this->manager)->get('/manager/promotions/create');
        $response->assertStatus(200);
        $response->assertSee('Buat Promosi Behavioral Baru');
    }

    public function test_manager_can_store_new_promotion(): void
    {
        $response = $this->actingAs($this->manager)->post('/manager/promotions', [
            'code' => 'TESTPROMO20',
            'name' => 'Diskon Uji Coba 20%',
            'rule_type' => PromotionRuleType::Comeback->value,
            'description' => 'Deskripsi promosi pengujian.',
            'benefit_type' => 'percent',
            'benefit_value' => 20,
            'max_discount' => 15000,
            'starts_at' => now()->toDateString(),
            'ends_at' => now()->addDays(14)->toDateString(),
            'min_transaction' => 35000,
            'per_member_limit' => 2,
            'is_active' => '1',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('promotions', [
            'code' => 'TESTPROMO20',
            'benefit_value' => 20,
            'is_active' => true,
        ]);
    }

    public function test_manager_can_view_promotion_detail(): void
    {
        $promo = Promotion::query()->firstOrFail();

        $response = $this->actingAs($this->manager)->get("/manager/promotions/{$promo->id}");
        $response->assertStatus(200);
        $response->assertSee($promo->name);
        $response->assertSee($promo->code);
    }

    public function test_manager_can_toggle_promotion(): void
    {
        $promo = Promotion::query()->where('is_active', true)->firstOrFail();

        $response = $this->actingAs($this->manager)->post("/manager/promotions/{$promo->id}/toggle");
        $response->assertRedirect();

        $this->assertFalse((bool) $promo->fresh()->is_active);
    }

    public function test_manager_can_view_performance_page(): void
    {
        $response = $this->actingAs($this->manager)->get('/manager/promotions/performance');
        $response->assertStatus(200);
        $response->assertSee('Evaluasi Keefektifan Promosi');
    }
}
