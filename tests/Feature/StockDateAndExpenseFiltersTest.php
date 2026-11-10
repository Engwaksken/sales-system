<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use Tests\TestCase;

class StockDateAndExpenseFiltersTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        $this->user = User::create([
            'name' => 'Admin', 'email' => Str::random(10).'@example.com',
            'password' => 'password', 'role' => 'admin', 'branch_id' => null,
        ]);
        $this->branch = Branch::create(['name' => 'Main Branch', 'location' => 'Kampala']);
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        parent::tearDown();
    }

    private function addStock(array $extra = [])
    {
        $product = Product::firstOrCreate(['sku' => 'BKT'], ['name' => 'Steel Bucket', 'is_active' => true]);

        return $this->actingAs($this->user)->post(route('stock.store'), [
            'product_id' => $product->id, 'branch_ids' => [$this->branch->id],
            'total_stock' => 5, 'quantity' => 5,
        ] + $extra);
    }

    public function test_app_runs_on_kampala_time(): void
    {
        $this->assertSame('Africa/Kampala', config('app.timezone'));
        $this->assertSame('+03:00', now()->format('P'));
    }

    public function test_stock_date_defaults_to_now_in_kampala(): void
    {
        Carbon::setTestNow(Carbon::parse('2026-11-10 14:35:00', 'Africa/Kampala'));

        $this->addStock()->assertSessionHasNoErrors();

        $this->assertSame('2026-11-10 14:35', Stock::sole()->stock_date->format('Y-m-d H:i'));
    }

    public function test_user_can_pick_the_stock_date_and_time(): void
    {
        $this->addStock(['stock_date' => '2026-11-03T09:15'])->assertSessionHasNoErrors();

        $this->assertSame('2026-11-03 09:15', Stock::sole()->stock_date->format('Y-m-d H:i'));
        $this->actingAs($this->user)->get(route('stock.index'))->assertOk()->assertSee('03 Nov 2026 09:15');
    }

    public function test_expenses_can_be_filtered_by_branch_and_payment_method(): void
    {
        $other = Branch::create(['name' => 'Second Branch', 'location' => 'Entebbe']);
        $base = ['amount' => 1000, 'expense_date' => '2026-11-10', 'category' => 'Ops'];
        Expense::create($base + ['title' => 'Fuel cash main', 'branch_id' => $this->branch->id, 'payment_method' => 'Cash']);
        Expense::create($base + ['title' => 'Airtime momo main', 'branch_id' => $this->branch->id, 'payment_method' => 'Mobile Money']);
        Expense::create($base + ['title' => 'Rent cash second', 'branch_id' => $other->id, 'payment_method' => 'Cash']);

        $this->actingAs($this->user)->get(route('expenses.index', ['payment_method' => 'Cash']))
            ->assertOk()->assertSee('Fuel cash main')->assertSee('Rent cash second')->assertDontSee('Airtime momo main');

        $this->actingAs($this->user)->get(route('expenses.index', ['branch_id' => $other->id]))
            ->assertOk()->assertSee('Rent cash second')->assertDontSee('Fuel cash main');

        $this->actingAs($this->user)->get(route('expenses.index', ['branch_id' => $this->branch->id, 'payment_method' => 'Cash']))
            ->assertOk()->assertSee('Fuel cash main')->assertDontSee('Rent cash second')->assertDontSee('Airtime momo main');
    }

    public function test_expense_payment_method_is_saved(): void
    {
        $this->actingAs($this->user)->post(route('expenses.store'), [
            'title' => 'Printer ink', 'amount' => 25000, 'payment_method' => 'Mobile Money',
            'expense_date' => '2026-11-10', 'category' => 'Office', 'branch_id' => $this->branch->id,
        ])->assertSessionHasNoErrors();

        $this->assertSame('Mobile Money', Expense::sole()->payment_method);
    }
}
