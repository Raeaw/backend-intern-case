<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_create_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/products', [
                'name' => 'Kopi Susu',
                'price' => 18000,
                'status' => 'active',
            ]);

        $response->assertStatus(201);
        $this->assertDatabaseHas('products', ['name' => 'Kopi Susu']);
    }

    public function test_regular_user_cannot_create_product(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/products', [
                'name' => 'Produk Ilegal',
                'price' => 5000,
                'status' => 'active',
            ]);

        $response->assertStatus(403);
    }
}
