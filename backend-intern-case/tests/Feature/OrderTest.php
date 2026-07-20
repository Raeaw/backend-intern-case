<?php

namespace Tests\Feature;

use App\Models\Product;
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
}
