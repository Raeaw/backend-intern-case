<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_create_order_with_active_product(): void
    {
        $product = Product::factory()->create([
            'price' => 10000,
            'status' => 'active',
        ]);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Budi',
            'customer_email' => 'budi@mail.com',
            'items' => [
                ['product_id' => $product->id, 'qty' => 3],
            ],
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('data.total_price', 30000);

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Budi',
            'total_price' => 30000,
        ]);
    }

    public function test_order_fails_and_rolls_back_when_product_is_inactive(): void
    {
        $product = Product::factory()->create(['status' => 'inactive']);

        $response = $this->postJson('/api/orders', [
            'customer_name' => 'Budi',
            'customer_email' => 'budi@mail.com',
            'items' => [
                ['product_id' => $product->id, 'qty' => 1],
            ],
        ]);

        $response->assertStatus(422);
        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
    }

    public function test_guest_cannot_list_orders(): void
    {
        $response = $this->getJson('/api/orders');

        $response->assertStatus(401);
    }

    public function test_regular_user_cannot_list_orders(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $token = $user->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson('/api/orders');

        $response->assertStatus(403);
    }

    public function test_admin_can_view_order_detail(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test')->plainTextToken;

        $product = Product::factory()->create(['price' => 5000, 'status' => 'active']);

        $order = $this->postJson('/api/orders', [
            'customer_name' => 'Budi',
            'customer_email' => 'budi@mail.com',
            'items' => [['product_id' => $product->id, 'qty' => 2]],
        ])->json('data');

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->getJson("/api/orders/{$order['id']}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $order['id']);
    }
}
