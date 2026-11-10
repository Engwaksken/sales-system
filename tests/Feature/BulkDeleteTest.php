<?php

namespace Tests\Feature;

use App\Http\Controllers\BulkDeleteController;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Tests\TestCase;

class BulkDeleteTest extends TestCase
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

    private function makeProduct(): Product
    {
        return Product::create([
            'name' => 'Product '.Str::random(4),
            'sku' => Str::random(12),
            'is_active' => true,
        ]);
    }

    public function test_every_registered_module_has_a_bulk_delete_route_and_model(): void
    {
        foreach (BulkDeleteController::RESOURCES as $resource => [$model]) {
            $this->assertTrue(Route::has("{$resource}.bulk-destroy"), "Missing route for {$resource}");
            $this->assertTrue(class_exists($model), "Missing model {$model}");
        }
    }

    public function test_selected_products_are_deleted_and_others_kept(): void
    {
        $user = $this->makeUser();
        [$a, $b, $keep] = [$this->makeProduct(), $this->makeProduct(), $this->makeProduct()];

        $this->actingAs($user)
            ->from(route('products.index'))
            ->post(route('products.bulk-destroy'), ['ids' => [$a->id, $b->id]])
            ->assertRedirect(route('products.index'))
            ->assertSessionHas('status', '2 records deleted.')
            ->assertSessionHasNoErrors();

        $this->assertModelMissing($a);
        $this->assertModelMissing($b);
        $this->assertModelExists($keep);
    }

    public function test_missing_ids_are_reported(): void
    {
        $user = $this->makeUser();
        $product = $this->makeProduct();

        $this->actingAs($user)
            ->post(route('products.bulk-destroy'), ['ids' => [$product->id, 999999]])
            ->assertSessionHas('status', '1 record deleted.')
            ->assertSessionHasErrors('bulk_delete');
    }

    public function test_requires_a_selection(): void
    {
        $this->actingAs($this->makeUser())
            ->post(route('products.bulk-destroy'), ['ids' => []])
            ->assertSessionHasErrors('ids');
    }

    public function test_role_without_permission_is_forbidden(): void
    {
        $product = $this->makeProduct();

        $this->actingAs($this->makeUser('cashier'))
            ->post(route('products.bulk-destroy'), ['ids' => [$product->id]])
            ->assertForbidden();

        $this->assertModelExists($product);
    }

    public function test_users_cannot_bulk_delete_their_own_account(): void
    {
        $me = $this->makeUser();
        $other = $this->makeUser();

        $this->actingAs($me)
            ->post(route('users.bulk-destroy'), ['ids' => [$me->id, $other->id]])
            ->assertSessionHas('status', '1 record deleted.')
            ->assertSessionHasErrors('bulk_delete');

        $this->assertModelExists($me);
        $this->assertModelMissing($other);
    }
}
