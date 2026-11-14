<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class DashboardMarginTest extends TestCase
{
    use RefreshDatabase;

    public function test_dashboard_shows_margin_and_branch_and_staff_leaders(): void
    {
        Mail::fake();

        $admin = User::create([
            'name' => 'Admin', 'email' => Str::random(10).'@example.com',
            'password' => 'password', 'role' => 'admin', 'branch_id' => null,
        ]);
        $cashier = User::create([
            'name' => 'Grace Cashier', 'email' => Str::random(10).'@example.com',
            'password' => 'password', 'role' => 'cashier', 'branch_id' => null,
        ]);
        $branch = Branch::create(['name' => 'Ntinda Branch', 'location' => 'Kampala']);
        $costed = Product::create(['name' => 'Steel Bucket', 'sku' => 'BKT', 'is_active' => true]);
        $uncosted = Product::create(['name' => 'Mystery Item', 'sku' => 'MYS', 'is_active' => true]);

        Stock::create([
            'product_id' => $costed->id, 'branch_id' => $branch->id,
            'total_stock' => 10, 'quantity' => 6, 'cost_price' => 6000, 'selling_price' => 10000,
        ]);

        // Two buckets at 10,000 less a 2,000 invoice discount → 18,000 net, 12,000 cost.
        $sale = Sale::create([
            'transaction_type' => 'sale', 'user_id' => $cashier->id, 'branch_id' => $branch->id,
            'invoice_no' => 'INV-T-1', 'total_amount' => 18000, 'discount' => 2000, 'vat' => 0,
            'sale_date' => now()->toDateString(), 'payment_status' => 'paid',
        ]);
        SaleItem::create(['sale_id' => $sale->id, 'product_id' => $costed->id, 'quantity' => 2, 'price' => 10000, 'total' => 20000]);

        // A product with no cost price must not count as pure profit.
        $other = Sale::create([
            'transaction_type' => 'sale', 'user_id' => $cashier->id, 'branch_id' => $branch->id,
            'invoice_no' => 'INV-T-2', 'total_amount' => 2000, 'discount' => 0, 'vat' => 0,
            'sale_date' => now()->toDateString(), 'payment_status' => 'paid',
        ]);
        SaleItem::create(['sale_id' => $other->id, 'product_id' => $uncosted->id, 'quantity' => 1, 'price' => 2000, 'total' => 2000]);

        $response = $this->actingAs($admin)->get(route('dashboard'))->assertOk();

        $margin = $response->viewData('margin');
        $this->assertEqualsWithDelta(18000, $margin['costed_revenue'], 0.01);
        $this->assertEqualsWithDelta(6000, $margin['gross_profit'], 0.01);
        $this->assertEqualsWithDelta(33.33, $margin['margin'], 0.01);
        $this->assertEqualsWithDelta(90, $margin['coverage'], 0.01);

        $this->assertSame('Ntinda Branch', $response->viewData('monthlyBestBranch')->name);
        $this->assertSame('Grace Cashier', $response->viewData('topStaffMember')->name);
        $this->assertSame('Steel Bucket', $response->viewData('topProducts')->first()->name);

        $response->assertSee('Gross Margin')
            ->assertSee('Top Salesperson This Month')
            ->assertSee('Collapse all')
            ->assertDontSee('Why Switch')
            ->assertDontSee('QuickBooks')
            ->assertDontSee('Margin is measured on');
    }

    public function test_customer_dashboard_renders_trend_charts(): void
    {
        $customer = User::create([
            'name' => 'Buyer', 'email' => Str::random(10).'@example.com',
            'password' => 'password', 'role' => 'customer', 'branch_id' => null,
        ]);

        $this->actingAs($customer)->get(route('dashboard'))
            ->assertOk()
            ->assertSee('My Purchase Trend')
            ->assertDontSee('Branches &amp; Staff', false);
    }
}
