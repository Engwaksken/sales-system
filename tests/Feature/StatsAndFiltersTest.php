<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class StatsAndFiltersTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Branch $branch;
    private Product $bucket;
    private Product $basin;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();

        $this->user = User::create([
            'name' => 'Admin', 'email' => Str::random(10).'@example.com',
            'password' => 'password', 'role' => 'admin', 'branch_id' => null,
        ]);
        $this->branch = Branch::create(['name' => 'Main Branch', 'location' => 'Kampala']);
        $this->bucket = Product::create(['name' => 'Steel Bucket', 'sku' => 'BKT', 'is_active' => true]);
        $this->basin = Product::create(['name' => 'Plastic Basin', 'sku' => 'BSN', 'is_active' => true]);

        foreach ([[$this->bucket, 40, 25], [$this->basin, 10, 3]] as [$product, $total, $qty]) {
            Stock::create([
                'product_id' => $product->id, 'branch_id' => $this->branch->id,
                'total_stock' => $total, 'quantity' => $qty,
                'selling_price' => 10000, 'cost_price' => 5000,
            ]);
        }
    }

    private function sell(Product $product, array $payments, ?int $customerId = null): void
    {
        $this->actingAs($this->user)->post(route('sales.pos-store'), [
            'branch_id' => $this->branch->id,
            'customer_id' => $customerId,
            'items' => [['product_id' => $product->id, 'quantity' => 1]],
            'payments' => $payments,
        ])->assertSessionHasNoErrors();
    }

    public function test_stock_table_shows_total_stock_and_footer_totals(): void
    {
        $this->actingAs($this->user)->get(route('stock.index'))
            ->assertOk()
            ->assertSee('Total Stock')
            ->assertSee('In Stock')
            ->assertSee('Units In Stock')
            ->assertSee('28 of 50'); // 25 + 3 in stock, 40 + 10 received
    }

    public function test_stock_can_be_filtered_by_product(): void
    {
        $this->actingAs($this->user)->get(route('stock.index', ['product_id' => $this->basin->id]))
            ->assertOk()
            ->assertSee('3 of 10')
            ->assertSee('Total (filtered)');
    }

    public function test_sales_can_be_filtered_by_product_and_payment_method(): void
    {
        $customer = Customer::create(['name' => 'Jane Buyer', 'phone' => '0700']);
        $this->sell($this->bucket, [['method' => 'Cash', 'amount' => 10000]]);
        $this->sell($this->basin, [['method' => 'Cash', 'amount' => 4000], ['method' => 'Mobile Money', 'amount' => 6000]]);
        $this->sell($this->basin, [], $customer->id);

        $invoices = fn (array $query) => $this->actingAs($this->user)->get(route('sales.index', $query))
            ->assertOk()->viewData('items')->pluck('payment_method')->sort()->values()->all();

        $this->assertSame(['Cash'], $invoices(['product_id' => $this->bucket->id]));
        $this->assertSame(['Credit', 'Split (Cash + Mobile Money)'], $invoices(['product_id' => $this->basin->id]));
        // A split sale counts under each of its methods.
        $this->assertSame(['Split (Cash + Mobile Money)'], $invoices(['payment_method' => 'Mobile Money']));
        $this->assertSame(['Cash', 'Split (Cash + Mobile Money)'], $invoices(['payment_method' => 'Cash']));
        $this->assertSame(['Credit'], $invoices(['payment_method' => 'Credit']));
    }

    public function test_dashboard_and_sales_show_four_card_rows(): void
    {
        $this->actingAs($this->user)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('lg:grid-cols-4', false)
            ->assertSee('Outstanding Credit')
            ->assertSee('Collected This Month');

        $this->actingAs($this->user)->get(route('sales.index'))
            ->assertOk()
            ->assertSee('Total Collected');
    }
}
