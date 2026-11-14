<?php

namespace Tests\Feature;

use App\Models\User;
use Database\Seeders\BaseConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed([RolePermissionSeeder::class, BaseConfigSeeder::class]);
    }

    public function test_member_registration_requires_uu_pdp_consent(): void
    {
        $response = $this->post('/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            // 'consent' omitted
        ]);

        $response->assertSessionHasErrors(['consent']);
        $this->assertDatabaseMissing('users', ['email' => 'budi@example.com']);
    }

    public function test_successful_member_registration(): void
    {
        $response = $this->post('/register', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'phone' => '081234567890',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'consent' => '1',
        ]);

        $response->assertRedirect(route('member.dashboard'));
        $this->assertDatabaseHas('users', ['email' => 'budi@example.com']);
        $this->assertDatabaseHas('members', ['is_demo' => false]);
        $this->assertAuthenticated();

        $user = User::where('email', 'budi@example.com')->first();
        $this->assertTrue($user->hasRole('member'));
    }

    public function test_member_login_redirects_to_member_dashboard(): void
    {
        $user = User::factory()->create([
            'email' => 'member@example.com',
            'password' => Hash::make('secret123'),
        ]);
        $user->assignRole('member');

        $response = $this->post('/login', [
            'contact' => 'member@example.com',
            'password' => 'secret123',
        ]);

        $response->assertRedirect(route('member.dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_staff_login_redirects_according_to_role(): void
    {
        $roles = [
            'kasir' => '/pos',
            'kitchen' => '/kds',
            'manager' => '/manager',
            'owner' => '/owner',
        ];

        foreach ($roles as $role => $expectedRedirect) {
            $user = User::factory()->create([
                'email' => "{$role}@example.com",
                'password' => Hash::make('secret123'),
            ]);
            $user->assignRole($role);

            $response = $this->post('/staff/login', [
                'email' => "{$role}@example.com",
                'password' => 'secret123',
            ]);

            $response->assertRedirect($expectedRedirect);
            $this->assertAuthenticatedAs($user);
            $this->post('/logout');
        }
    }

    public function test_rbac_prevents_unauthorized_access(): void
    {
        // Kasir cannot access manager dashboard
        $kasir = User::factory()->create();
        $kasir->assignRole('kasir');

        $response = $this->actingAs($kasir)->get('/manager');
        $response->assertStatus(403);

        // Kitchen cannot access pos
        $kitchen = User::factory()->create();
        $kitchen->assignRole('kitchen');

        $response = $this->actingAs($kitchen)->get('/pos');
        $response->assertStatus(403);

        // Guest cannot access member dashboard
        $this->post('/logout');
        $response = $this->get('/member');
        $response->assertRedirect('/login');
    }

    public function test_session_idle_timeout_after_30_minutes(): void
    {
        $user = User::factory()->create();
        $user->assignRole('kasir');

        // Simulate request with last_activity_timestamp set 31 minutes ago
        $response = $this->actingAs($user)
            ->withSession(['last_activity_timestamp' => now()->subMinutes(31)->timestamp])
            ->get('/pos');

        $response->assertRedirect(route('staff.login'));
        $this->assertGuest();
    }
}
