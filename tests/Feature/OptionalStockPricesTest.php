<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class OptionalStockPricesTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Branch $branch;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->user = User::create([
            'name' => 'Admin', 'email' => Str::random(10).'@example.com',
            'password' => 'password', 'role' => 'admin', 'branch_id' => null,
        ]);
        $this->branch = Branch::create(['name' => 'Main Branch', 'location' => 'Kampala']);
        $this->product = Product::create(['name' => 'Steel Bucket', 'sku' => 'BKT', 'is_active' => true]);
    }

    private function addStock(array $prices = [])
    {
        return $this->actingAs($this->user)->post(route('stock.store'), [
            'product_id' => $this->product->id,
            'branch_ids' => [$this->branch->id],
            'total_stock' => 10,
            'quantity' => 10,
        ] + $prices);
    }

    public function test_stock_can_be_saved_without_prices(): void
    {
        $this->addStock()->assertSessionHasNoErrors()->assertRedirect(route('stock.index'));

        $stock = Stock::sole();
        $this->assertNull($stock->cost_price);
        $this->assertNull($stock->selling_price);
        $this->assertSame(10, (int) $stock->quantity);
    }

    public function test_topping_up_without_prices_keeps_the_existing_prices(): void
    {
        $this->addStock(['cost_price' => 6000, 'selling_price' => 10000]);
        $this->addStock()->assertSessionHasNoErrors();

        $stock = Stock::sole();
        $this->assertEquals(6000, (float) $stock->cost_price);
        $this->assertEquals(10000, (float) $stock->selling_price);
        $this->assertSame(20, (int) $stock->quantity);
    }

    public function test_pos_refuses_stock_without_a_selling_price(): void
    {
        $this->addStock();

        $this->actingAs($this->user)->post(route('sales.pos-store'), [
            'branch_id' => $this->branch->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
            'payments' => [['method' => 'Cash', 'amount' => 0]],
        ])->assertSessionHasErrors('pos');

        $this->assertSame(0, Sale::count());
        $this->assertSame(10, (int) Stock::sole()->quantity, 'stock must not be taken');
    }

    public function test_stock_page_and_pos_flag_missing_prices(): void
    {
        $this->addStock();

        $this->actingAs($this->user)->get(route('stock.index'))->assertOk()->assertSee('Not set');

        $stockData = $this->actingAs($this->user)->get(route('sales.index'))->assertOk()->viewData('stockData');
        $this->assertNull($stockData->first()['price']);
    }

    public function test_unpriced_stock_is_hidden_from_the_shop_and_refused_at_checkout(): void
    {
        $this->addStock();

        $this->get(route('shop.index', ['branch' => $this->branch->id]))->assertOk()->assertDontSee('Steel Bucket');

        $this->postJson(route('checkout.store'), [
            'customer_name' => 'Jane Buyer',
            'phone' => '0700000000',
            'delivery_location' => 'Kampala Road',
            'branch_id' => $this->branch->id,
            'payment_method' => 'cash-on-delivery',
            'items' => [['id' => $this->product->id, 'qty' => 1]],
        ])->assertStatus(422)->assertJsonPath('status', 'error');

        $this->assertSame(10, (int) Stock::sole()->quantity);

        // Once priced, the same item is for sale.
        Stock::sole()->update(['selling_price' => 10000]);
        $this->get(route('shop.index', ['branch' => $this->branch->id]))->assertOk()->assertSee('Steel Bucket');
    }
}
