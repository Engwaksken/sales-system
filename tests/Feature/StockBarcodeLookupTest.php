<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class StockBarcodeLookupTest extends TestCase
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

    private function makeProduct(string $barcode): Product
    {
        return Product::create([
            'name' => 'Test Product',
            'sku' => Str::random(12),
            'barcode' => $barcode,
            'branch_id' => null,
            'category_id' => null,
            'description' => 'internal secret notes',
            'image_url' => 'https://example.com/img.png',
            'is_active' => true,
        ]);
    }

    public function test_authorized_lookup_returns_only_the_safe_product_fields(): void
    {
        $user = $this->makeUser();
        $this->makeProduct('BCODE-0001');

        $response = $this->actingAs($user)
            ->getJson(route('stock.lookup-barcode', ['barcode' => 'BCODE-0001']));

        $response->assertOk();

        $this->assertEqualsCanonicalizing(
            ['id', 'name', 'sku', 'barcode', 'image_url', 'is_active'],
            array_keys($response->json())
        );

        $payload = $response->json();
        $this->assertArrayNotHasKey('created_by', $payload);
        $this->assertArrayNotHasKey('updated_by', $payload);
        $this->assertArrayNotHasKey('created_at', $payload);
        $this->assertArrayNotHasKey('updated_at', $payload);
        $this->assertArrayNotHasKey('branch_id', $payload);
        $this->assertArrayNotHasKey('category_id', $payload);
        $this->assertArrayNotHasKey('description', $payload);
    }

    public function test_missing_barcode_triggers_validation_failure(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->getJson(route('stock.lookup-barcode'))
            ->assertStatus(422);
    }

    public function test_lookup_returns_404_when_no_product_matches_the_barcode(): void
    {
        $user = $this->makeUser();

        $this->actingAs($user)
            ->getJson(route('stock.lookup-barcode', ['barcode' => 'DOES-NOT-EXIST']))
            ->assertStatus(404)
            ->assertJson(['message' => 'No product found for this barcode.']);
    }

    public function test_unauthenticated_request_is_blocked(): void
    {
        $this->getJson(route('stock.lookup-barcode', ['barcode' => 'ANY']))
            ->assertStatus(401);
    }

    public function test_user_without_manage_stock_permission_is_blocked(): void
    {
        $user = $this->makeUser('cashier');

        $this->actingAs($user)
            ->getJson(route('stock.lookup-barcode', ['barcode' => 'ANY']))
            ->assertStatus(403);
    }

    public function test_lookup_is_throttled_at_sixty_requests_per_minute(): void
    {
        $user = $this->makeUser();
        $this->makeProduct('THROTTLE-0001');

        $this->actingAs($user);

        for ($i = 0; $i < 60; $i++) {
            $this->getJson(route('stock.lookup-barcode', ['barcode' => 'THROTTLE-0001']))
                ->assertOk();
        }

        $this->getJson(route('stock.lookup-barcode', ['barcode' => 'THROTTLE-0001']))
            ->assertStatus(429);
    }
}
