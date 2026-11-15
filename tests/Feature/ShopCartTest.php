<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Stock;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class ShopCartTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->branch = Branch::create(['name' => 'Main Branch', 'location' => 'Kampala']);
        $this->product = Product::create(['name' => 'Steel Bucket', 'is_active' => true]);
        Stock::create([
            'product_id' => $this->product->id, 'branch_id' => $this->branch->id,
            'quantity' => 3, 'total_stock' => 3, 'selling_price' => 10000,
        ]);
    }

    public function test_checkout_lets_shoppers_edit_their_cart(): void
    {
        $this->get(route('shop.index', ['branch' => $this->branch->id]))
            ->assertOk()
            ->assertSee('id="cartClearBtn"', false)
            ->assertSee('id="cartUndo"', false)
            ->assertSee('function changeQty', false)
            ->assertSee('function removeItem', false);
    }

    public function test_checkout_still_refuses_more_than_is_in_stock(): void
    {
        // The cart caps quantities in the browser; the server is the real guard.
        $this->postJson(route('checkout.store'), [
            'customer_name' => 'Jane Buyer',
            'phone' => '0700000000',
            'delivery_location' => 'Kampala Road',
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash-on-delivery',
            'items' => [['id' => $this->product->id, 'qty' => 5]],
        ])->assertStatus(422);

        $this->assertSame(3, (int) Stock::sole()->quantity);
    }
}
