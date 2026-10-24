<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ProductBarcodeValidationTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role = 'admin'): User
    {
        return User::create([
            'name' => 'Test User',
            'email' => Str::random(12).'@example.com',
            'password' => 'password',
            'role' => $role,
            'branch_id' => null,
        ]);
    }

    private function makeProduct(?string $barcode = null): Product
    {
        return Product::create([
            'name' => 'Test Product',
            'sku' => Str::random(12),
            'barcode' => $barcode,
            'branch_id' => null,
            'category_id' => null,
            'description' => null,
            'is_active' => true,
        ]);
    }

    public function test_store_creates_product_with_barcode(): void
    {
        $this->actingAs($this->makeUser());

        $response = $this->post(route('products.store'), [
            'name' => 'New Product',
            'barcode' => 'BCODE-1001',
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', ['name' => 'New Product', 'barcode' => 'BCODE-1001']);
    }

    public function test_store_rejects_duplicate_barcode(): void
    {
        $this->actingAs($this->makeUser());
        $this->makeProduct('BCODE-2001');

        $response = $this->post(route('products.store'), [
            'name' => 'Duplicate Barcode',
            'barcode' => 'BCODE-2001',
        ]);

        $response->assertSessionHasErrors('barcode');
        $this->assertDatabaseMissing('products', ['name' => 'Duplicate Barcode']);
    }

    public function test_store_treats_empty_barcode_as_null(): void
    {
        $this->actingAs($this->makeUser());

        $this->post(route('products.store'), [
            'name' => 'No Barcode',
            'barcode' => '',
        ])->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', ['name' => 'No Barcode', 'barcode' => null]);
    }

    public function test_update_keeps_own_barcode(): void
    {
        $this->actingAs($this->makeUser());
        $product = $this->makeProduct('BCODE-3001');

        $response = $this->put(route('products.update', $product), [
            'name' => 'Updated Product',
            'barcode' => 'BCODE-3001',
        ]);

        $response->assertRedirect(route('products.index'));
        $this->assertDatabaseHas('products', ['id' => $product->id, 'barcode' => 'BCODE-3001']);
    }

    public function test_update_rejects_duplicate_barcode_from_another_product(): void
    {
        $this->actingAs($this->makeUser());
        $product = $this->makeProduct('BCODE-4001');
        $other = $this->makeProduct('BCODE-4002');

        $response = $this->put(route('products.update', $product), [
            'name' => 'Clash',
            'barcode' => 'BCODE-4002',
        ]);

        $response->assertSessionHasErrors('barcode');
        $this->assertDatabaseHas('products', ['id' => $product->id, 'barcode' => 'BCODE-4001']);
        $this->assertDatabaseHas('products', ['id' => $other->id, 'barcode' => 'BCODE-4002']);
    }

    public function test_update_treats_empty_barcode_as_null(): void
    {
        $this->actingAs($this->makeUser());
        $product = $this->makeProduct('BCODE-5001');

        $this->put(route('products.update', $product), [
            'name' => 'Cleared Barcode',
            'barcode' => '',
        ])->assertRedirect(route('products.index'));

        $this->assertDatabaseHas('products', ['id' => $product->id, 'barcode' => null]);
    }
}
