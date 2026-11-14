<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DiningTable;
use App\Models\Member;
use App\Models\Order;
use App\Models\Product;
use App\Models\Reward;
use App\Models\User;
use Database\Seeders\BaseConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Tests\TestCase;

class MemberPortalTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;
    protected Member $member;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, BaseConfigSeeder::class]);

        $this->user = User::factory()->create([
            'name' => 'Budi Santoso',
            'email' => 'budi.member@774hub.test',
            'phone' => '081234567800',
            'password' => Hash::make('password123'),
        ]);
        $this->user->assignRole('member');

        $this->member = Member::create([
            'user_id' => $this->user->id,
            'member_code' => 'MBR-774-0050',
            'points_balance' => 30,
            'consent_at' => now(),
            'joined_at' => now(),
        ]);
    }

    public function test_member_can_view_dashboard(): void
    {
        $response = $this->actingAs($this->user)->get('/member');
        $response->assertStatus(200);
        $response->assertSee('MBR-774-0050');
        $response->assertSee('Budi Santoso');
        $response->assertSee('30 Poin');
    }

    public function test_member_can_view_loyalty_page(): void
    {
        $response = $this->actingAs($this->user)->get('/member/loyalty');
        $response->assertStatus(200);
        $response->assertSee('Saldo Poin Member');
        $response->assertSee('30 Poin');
    }

    public function test_member_can_redeem_reward(): void
    {
        $reward = Reward::create([
            'name' => 'Potongan Rp10.000',
            'points_cost' => 15,
            'benefit_type' => 'nominal',
            'benefit_value' => 10000,
            'valid_days' => 30,
            'is_active' => true,
        ]);

        $response = $this->actingAs($this->user)->post("/member/rewards/{$reward->id}/redeem");
        $response->assertRedirect('/member/loyalty');

        $this->assertEquals(15, $this->member->fresh()->points_balance);
        $this->assertDatabaseHas('reward_redemptions', [
            'member_id' => $this->member->id,
            'reward_id' => $reward->id,
            'status' => 'active',
        ]);
    }

    public function test_member_can_view_profile(): void
    {
        $response = $this->actingAs($this->user)->get('/member/profile');
        $response->assertStatus(200);
        $response->assertSee('Profil &amp; Keamanan Akun', false);
        $response->assertSee('Budi Santoso');
    }

    public function test_member_can_update_profile(): void
    {
        $response = $this->actingAs($this->user)->patch('/member/profile', [
            'name' => 'Budi Santoso Updated',
            'email' => 'budi.updated@774hub.test',
            'phone' => '081299998888',
        ]);

        $response->assertRedirect('/member/profile');
        $this->assertDatabaseHas('users', [
            'id' => $this->user->id,
            'name' => 'Budi Santoso Updated',
            'email' => 'budi.updated@774hub.test',
            'phone' => '081299998888',
        ]);
    }

    public function test_member_can_view_orders_history(): void
    {
        $response = $this->actingAs($this->user)->get('/member/orders');
        $response->assertStatus(200);
        $response->assertSee('Riwayat Transaksi Saya');
    }

    public function test_member_can_update_password_successfully(): void
    {
        $response = $this->actingAs($this->user)->put('/member/profile/password', [
            'current_password' => 'password123',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect('/member/profile');
        $response->assertSessionHas('toast_success');

        $this->assertTrue(Hash::check('newpassword123', $this->user->fresh()->password));
    }

    public function test_member_cannot_update_password_with_wrong_current(): void
    {
        $response = $this->actingAs($this->user)->put('/member/profile/password', [
            'current_password' => 'wrongpassword',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertSessionHasErrors('current_password');
        $this->assertTrue(Hash::check('password123', $this->user->fresh()->password));
    }

    public function test_member_can_logout(): void
    {
        $response = $this->actingAs($this->user)->post('/logout');
        $response->assertRedirect();
        $this->assertGuest();
    }
}
