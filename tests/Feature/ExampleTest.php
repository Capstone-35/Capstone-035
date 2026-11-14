<?php

namespace Tests\Feature;

use App\Models\DiningTable;
use Database\Seeders\BaseConfigSeeder;
use Database\Seeders\RolePermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExampleTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_application_returns_a_successful_response(): void
    {
        $this->seed([RolePermissionSeeder::class, BaseConfigSeeder::class]);
        $table = DiningTable::create([
            'number' => '01',
            'label' => 'Meja 01',
            'qr_token' => 'qr_demo_meja_01',
            'is_active' => true,
        ]);

        $response = $this->get('/');

        $response->assertRedirect(route('table.scan', ['qr_token' => $table->qr_token]));
    }
}
