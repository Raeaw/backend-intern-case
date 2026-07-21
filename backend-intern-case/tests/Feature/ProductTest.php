<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_anyone_can_list_products(): void
    {
        Product::factory()->count(3)->create();

        $response = $this->getJson('/api/products');

        $response->assertStatus(200)
            ->assertJsonCount(3, 'data');
    }

    public function test_anyone_can_view_single_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.id', $product->id);
    }

    public function test_viewing_nonexistent_product_returns_404(): void
    {
        $response = $this->getJson('/api/products/9999');

        $response->assertStatus(404);
    }

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

    public function test_product_creation_fails_validation(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->postJson('/api/products', [
                'price' => -100,
                'status' => 'unknown',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['name', 'price', 'status']);
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

    public function test_guest_cannot_create_product(): void
    {
        $response = $this->postJson('/api/products', [
            'name' => 'Produk Tanpa Login',
            'price' => 5000,
            'status' => 'active',
        ]);

        $response->assertStatus(401);
    }

    public function test_admin_can_update_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test')->plainTextToken;

        $product = Product::factory()->create([
            'name' => 'Kopi Hitam',
            'price' => 15000,
        ]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/products/{$product->id}", [
                'price' => 20000,
            ]);

        $response->assertStatus(200)
            ->assertJsonPath('data.price', 20000)
            ->assertJsonPath('data.name', 'Kopi Hitam');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'price' => 20000,
        ]);
    }

    public function test_update_fails_with_invalid_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test')->plainTextToken;

        $product = Product::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/products/{$product->id}", [
                'status' => 'unknown-status',
            ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors(['status']);
    }

    public function test_regular_user_cannot_update_product(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $token = $user->createToken('test')->plainTextToken;

        $product = Product::factory()->create(['price' => 10000]);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson("/api/products/{$product->id}", [
                'price' => 99999,
            ]);

        $response->assertStatus(403);
        $this->assertDatabaseHas('products', ['id' => $product->id, 'price' => 10000]);
    }

    public function test_guest_cannot_update_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->putJson("/api/products/{$product->id}", [
            'price' => 99999,
        ]);

        $response->assertStatus(401);
    }

    public function test_updating_nonexistent_product_returns_404(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->putJson('/api/products/9999', [
                'price' => 10000,
            ]);

        $response->assertStatus(404);
    }

    public function test_admin_can_delete_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test')->plainTextToken;

        $product = Product::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(200)
            ->assertJsonPath('data.message', 'Product deleted successfully.');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }

    public function test_regular_user_cannot_delete_product(): void
    {
        $user = User::factory()->create(['role' => 'user']);
        $token = $user->createToken('test')->plainTextToken;

        $product = Product::factory()->create();

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(403);
        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }

    public function test_guest_cannot_delete_product(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(401);
    }

    public function test_deleting_nonexistent_product_returns_404(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test')->plainTextToken;

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson('/api/products/9999');

        $response->assertStatus(404);
    }

    public function test_deleting_product_that_has_order_items_fails(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $token = $admin->createToken('test')->plainTextToken;

        $product = Product::factory()->create(['status' => 'active', 'price' => 10000]);

        $this->postJson('/api/orders', [
            'customer_name' => 'Budi',
            'customer_email' => 'budi@mail.com',
            'items' => [['product_id' => $product->id, 'qty' => 1]],
        ])->assertStatus(201);

        $response = $this->withHeader('Authorization', "Bearer {$token}")
            ->deleteJson("/api/products/{$product->id}");

        $response->assertStatus(500);

        $this->assertDatabaseHas('products', ['id' => $product->id]);
    }
}
