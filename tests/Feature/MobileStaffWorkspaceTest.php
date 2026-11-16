<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\ChatConversation;
use App\Models\Customer;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\RolePermission;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\StockIssue;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileStaffWorkspaceTest extends TestCase
{
    use RefreshDatabase;

    private Branch $main;
    private Branch $other;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Cache::flush();

        $this->main = Branch::create(['name' => 'Main Branch', 'location' => 'Kampala']);
        $this->other = Branch::create(['name' => 'Jinja', 'location' => 'Jinja']);
        $this->product = Product::create(['name' => 'Steel Bucket', 'sku' => 'BKT-1', 'barcode' => '600123', 'is_active' => true]);
        foreach ([$this->main, $this->other] as $branch) {
            Stock::create([
                'product_id' => $this->product->id, 'branch_id' => $branch->id,
                'quantity' => 10, 'total_stock' => 10, 'selling_price' => 10000, 'cost_price' => 6000,
            ]);
        }
    }

    private function staff(string $role, array $permissions = [], ?Branch $branch = null): User
    {
        foreach ($permissions as $permission) {
            RolePermission::firstOrCreate(['role' => $role, 'permission_key' => $permission]);
        }

        $user = User::create([
            'name' => ucfirst($role), 'email' => Str::lower(Str::random(10)).'@example.com',
            'password' => 'password', 'role' => $role, 'branch_id' => ($branch ?? $this->main)->id,
        ]);
        Sanctum::actingAs($user);

        return $user;
    }

    private function stockAt(Branch $branch): float
    {
        return (float) Stock::where('product_id', $this->product->id)->where('branch_id', $branch->id)->value('quantity');
    }

    public function test_cashier_sells_at_own_branch_even_if_another_is_requested(): void
    {
        $this->staff('cashier', ['manage_sales']);

        $this->postJson('/api/mobile/staff/sales', [
            'branch_id' => $this->other->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 2]],
            'payments' => [['method' => 'Cash', 'amount' => 20000]],
        ])->assertCreated()
            ->assertJsonPath('data.payment_status', 'paid')
            ->assertJsonPath('data.items.0.name', 'Steel Bucket')
            ->assertJsonCount(1, 'data.payments');

        $this->assertSame(8.0, $this->stockAt($this->main));
        $this->assertSame(10.0, $this->stockAt($this->other));
    }

    public function test_walk_in_sale_must_be_paid_in_full(): void
    {
        $this->staff('cashier', ['manage_sales']);

        $this->postJson('/api/mobile/staff/sales', [
            'items' => [['product_id' => $this->product->id, 'quantity' => 2]],
            'payments' => [['method' => 'Cash', 'amount' => 5000]],
        ])->assertUnprocessable();

        $this->assertSame(10.0, $this->stockAt($this->main));
    }

    public function test_credit_sale_can_be_paid_off_later(): void
    {
        $this->staff('cashier', ['manage_sales']);
        $customer = Customer::create(['name' => 'Jane Buyer', 'branch_id' => $this->main->id]);

        $saleId = $this->postJson('/api/mobile/staff/sales', [
            'customer_id' => $customer->id,
            'items' => [['product_id' => $this->product->id, 'quantity' => 3]],
        ])->assertCreated()->assertJsonPath('data.balance_amount', 30000)->json('data.id');

        $this->postJson("/api/mobile/staff/sales/{$saleId}/payments", [
            'payments' => [['method' => 'Mobile Money', 'amount' => 30000]],
        ])->assertOk()->assertJsonPath('data.payment_status', 'paid');

        $this->getJson('/api/mobile/staff/pos/customers?q=jane')->assertOk()->assertJsonPath('data.0.id', $customer->id);
        $this->getJson('/api/mobile/staff/sales?q=Jane')->assertOk()->assertJsonPath('data.0.id', $saleId);
        $this->getJson('/api/mobile/staff/payments')->assertOk()->assertJsonCount(1, 'data');
    }

    public function test_payments_filter_by_date_and_status_like_the_dashboard_card(): void
    {
        $this->staff('cashier', ['manage_sales']);
        $saleId = $this->postJson('/api/mobile/staff/sales', [
            'items' => [['product_id' => $this->product->id, 'quantity' => 1]],
            'payments' => [['method' => 'Cash', 'amount' => 10000]],
        ])->assertCreated()->json('data.id');
        $payment = Payment::where('sale_id', $saleId)->sole();
        $today = now()->toDateString();

        $this->getJson("/api/mobile/staff/payments?from={$today}&to={$today}&status=successful")->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/mobile/staff/payments?status=failed')->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/mobile/staff/payments?to='.now()->subDay()->toDateString())->assertOk()->assertJsonCount(0, 'data');
        $this->getJson('/api/mobile/staff/payments?from=not-a-date&status=bogus')->assertOk()->assertJsonCount(1, 'data');

        $card = collect($this->getJson('/api/mobile/dashboard')->json('stats'))->firstWhere('key', 'month_collected');
        $this->assertNotNull($card, 'the Collected This Month card is on the dashboard');
        $this->assertSame('payments', $card['module']);
        $this->assertSame(['from' => now()->startOfMonth()->toDateString(), 'to' => $today, 'status' => 'successful'], $card['filter']);
        $this->assertSame('successful', $payment->status);
    }

    public function test_pos_products_lists_sellable_stock_for_the_branch(): void
    {
        $this->staff('cashier', ['manage_sales']);

        $this->getJson('/api/mobile/staff/pos/products?q=bucket')
            ->assertOk()
            ->assertJsonPath('data.0.product_id', $this->product->id)
            ->assertJsonPath('data.0.price', 10000);
    }

    public function test_staff_cannot_see_other_branch_sales(): void
    {
        $sale = Sale::create([
            'transaction_type' => 'sale', 'invoice_no' => 'INV-X', 'total_amount' => 1000,
            'branch_id' => $this->other->id, 'payment_status' => 'paid', 'sale_date' => now()->toDateString(),
        ]);
        $this->staff('cashier', ['manage_sales']);

        $this->getJson("/api/mobile/staff/sales/{$sale->id}")->assertNotFound();
        $this->getJson('/api/mobile/staff/sales')->assertOk()->assertJsonCount(0, 'data');
    }

    public function test_admin_sees_every_branch(): void
    {
        Sale::create([
            'transaction_type' => 'sale', 'invoice_no' => 'INV-X', 'total_amount' => 1000,
            'branch_id' => $this->other->id, 'payment_status' => 'paid', 'sale_date' => now()->toDateString(),
        ]);
        $this->staff('admin');

        $this->getJson('/api/mobile/staff/sales')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/mobile/staff/lookups')->assertOk()->assertJsonCount(2, 'branches')->assertJsonPath('is_admin', true);
    }

    public function test_permissions_match_the_website(): void
    {
        $this->staff('cashier', ['manage_sales']);

        $this->getJson('/api/mobile/staff/stock')->assertForbidden();
        $this->getJson('/api/mobile/staff/orders')->assertForbidden();
        $this->getJson('/api/mobile/staff/chats')->assertForbidden();
        $this->getJson('/api/mobile/staff/catalog/products')->assertForbidden();
        $this->getJson('/api/mobile/staff/reports/profit')->assertForbidden();
        $this->getJson('/api/mobile/resources/users')->assertForbidden();
    }

    public function test_customers_are_kept_out_of_the_staff_api(): void
    {
        $user = User::create([
            'name' => 'Buyer', 'email' => 'buyer@example.com', 'password' => 'password',
            'role' => 'customer', 'branch_id' => null,
        ]);
        Sanctum::actingAs($user);

        $this->getJson('/api/mobile/staff/lookups')->assertForbidden();
        $this->getJson('/api/mobile/staff/sales')->assertForbidden();
    }

    public function test_store_keeper_receives_and_issues_stock(): void
    {
        $this->staff('manager', ['manage_stock']);

        $this->postJson('/api/mobile/staff/stock', [
            'product_id' => $this->product->id, 'quantity' => 5, 'selling_price' => 12000,
        ])->assertCreated()->assertJsonPath('topped_up', 1);
        $this->assertSame(15.0, $this->stockAt($this->main));

        $this->postJson('/api/mobile/staff/stock-issues', [
            'product_id' => $this->product->id, 'type' => 'damage', 'quantity' => 2, 'reason' => 'Dented',
        ])->assertCreated();
        $this->assertSame(13.0, $this->stockAt($this->main));
        $this->assertSame(1, StockIssue::count());

        $this->getJson('/api/mobile/staff/stock?q=bucket')->assertOk()->assertJsonCount(1, 'data');
        $this->getJson('/api/mobile/staff/stock/products?q=steel')->assertOk()->assertJsonPath('data.0.id', $this->product->id);
        $this->getJson('/api/mobile/staff/stock/lookup-barcode?barcode=600123')->assertOk()->assertJsonPath('data.name', 'Steel Bucket');

        $stockId = Stock::where('branch_id', $this->main->id)->value('id');
        $this->putJson("/api/mobile/staff/stock/{$stockId}", ['selling_price' => 15000])
            ->assertOk()->assertJsonPath('data.selling_price', 15000);
    }

    public function test_order_stage_change_is_logged(): void
    {
        $order = Order::create([
            'order_id' => 'ORD-1', 'order_number' => 'ORD-1', 'customer_name' => 'Jane', 'phone' => '0700',
            'delivery_location' => 'Kampala', 'branch_id' => $this->main->id, 'payment_method' => 'cash',
            'payment_status' => 'pending', 'total_amount' => 1000, 'discount' => 0, 'amount_paid' => 0,
            'balance_amount' => 1000, 'status' => 'pending', 'delivery_fee' => 0, 'subtotal' => 1000,
        ]);
        $this->staff('manager', ['manage_orders']);

        $this->patchJson("/api/mobile/staff/orders/{$order->id}/status", ['status' => 'confirmed'])
            ->assertOk()
            ->assertJsonPath('data.status', 'confirmed')
            ->assertJsonPath('data.logs.0.action', 'status_changed');

        $this->patchJson("/api/mobile/staff/orders/{$order->id}/status", ['status' => 'teleported'])->assertUnprocessable();
    }

    public function test_catalog_crud_follows_website_rules(): void
    {
        $this->staff('manager', ['manage_products', 'manage_categories', 'manage_customers', 'manage_suppliers', 'manage_expenses']);

        $categoryId = $this->postJson('/api/mobile/staff/catalog/categories', ['category_name' => 'Hardware', 'status' => 'active'])
            ->assertCreated()->json('data.id');

        $productId = $this->postJson('/api/mobile/staff/catalog/products', [
            'name' => 'Hammer', 'sku' => 'HM-1', 'category_id' => $categoryId, 'is_active' => true,
        ])->assertCreated()->assertJsonPath('data.category_name', 'Hardware')->json('data.id');
        $this->assertSame($this->main->id, Product::find($productId)->branch_id);

        $this->putJson("/api/mobile/staff/catalog/products/{$productId}", ['name' => 'Claw Hammer'])
            ->assertOk()->assertJsonPath('data.name', 'Claw Hammer');
        $this->postJson('/api/mobile/staff/catalog/products', ['name' => 'Dup', 'barcode' => '600123'])->assertUnprocessable();

        $this->postJson('/api/mobile/staff/catalog/customers', ['name' => 'Peter', 'branch_id' => $this->other->id])
            ->assertCreated()->assertJsonPath('data.branch_id', $this->main->id);
        $this->postJson('/api/mobile/staff/catalog/suppliers', ['name' => 'Acme', 'contact_name' => 'Sam', 'status' => 'active'])
            ->assertCreated();
        $this->postJson('/api/mobile/staff/catalog/expense-categories', ['name' => 'Transport', 'status' => true])
            ->assertCreated();

        $this->getJson('/api/mobile/staff/catalog/products?q=claw')->assertOk()->assertJsonCount(1, 'data');
        $this->deleteJson("/api/mobile/staff/catalog/products/{$productId}")->assertOk();
    }

    public function test_support_agent_replies_to_and_closes_a_chat(): void
    {
        $conversation = ChatConversation::create(['first_name' => 'Jane', 'last_name' => 'Doe', 'phone' => '0700', 'email' => 'jane@example.com', 'status' => 'open']);
        $conversation->messages()->create(['sender_type' => 'customer', 'message' => 'Hello?', 'is_read' => false]);
        $this->staff('cashier', ['manage_chats']);

        $this->getJson('/api/mobile/staff/chats')->assertOk()->assertJsonPath('data.0.unread_count', 1);
        $this->getJson("/api/mobile/staff/chats/{$conversation->id}")->assertOk()->assertJsonCount(1, 'data.messages');
        $this->postJson("/api/mobile/staff/chats/{$conversation->id}/reply", ['message' => 'Hi Jane'])
            ->assertCreated()->assertJsonCount(2, 'data.messages');
        $this->postJson("/api/mobile/staff/chats/{$conversation->id}/close")->assertOk()->assertJsonPath('data.status', 'closed');
        $this->getJson('/api/mobile/staff/chats')->assertJsonPath('data.0.unread_count', 0);
    }

    public function test_reports_are_returned_as_tables(): void
    {
        $this->staff('accountant', ['manage_statements']);

        $this->getJson('/api/mobile/staff/reports')->assertOk()->assertJsonFragment(['type' => 'profit']);
        foreach (['overview', 'sales', 'stock', 'profit', 'income_statement'] as $type) {
            $this->getJson("/api/mobile/staff/reports/{$type}")->assertOk()->assertJsonStructure(['headers', 'keys', 'rows', 'money_columns']);
        }
    }

    public function test_admin_modules_are_viewable(): void
    {
        $this->staff('admin');

        foreach (['users', 'branches', 'delivery-zones', 'chart-of-accounts', 'accounting-journals', 'efris-invoices', 'mobile-money'] as $resource) {
            $this->getJson("/api/mobile/resources/{$resource}")->assertOk();
        }
        $this->getJson('/api/mobile/resources/users')->assertJsonMissingPath('data.0.password');
    }
}
