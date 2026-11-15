<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\RolePermission;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ActivityLogTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;
    private Product $product;
    private Stock $stock;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Cache::flush();

        $this->branch = Branch::create(['name' => 'Main Branch', 'location' => 'Kampala']);
        $this->product = Product::create(['name' => 'Steel Bucket', 'sku' => 'BKT-1', 'is_active' => true]);
        $this->stock = Stock::create([
            'product_id' => $this->product->id, 'branch_id' => $this->branch->id,
            'quantity' => 10, 'total_stock' => 10, 'selling_price' => 10000, 'cost_price' => 6000,
        ]);
        ActivityLog::query()->delete();
    }

    private function user(string $role, array $permissions = []): User
    {
        foreach ($permissions as $permission) {
            RolePermission::firstOrCreate(['role' => $role, 'permission_key' => $permission]);
        }

        $user = User::create([
            'name' => ucfirst($role).' '.Str::random(4), 'email' => Str::lower(Str::random(10)).'@example.com',
            'password' => bcrypt('password'), 'role' => $role, 'branch_id' => $this->branch->id,
            'email_verified_at' => now(),
        ]);
        // Granting permissions is itself flagged; keep test setup out of the trail.
        ActivityLog::query()->whereIn('subject_type', ['role_permission', 'user'])->delete();

        return $user;
    }

    private function sellOnMobile(User $cashier, int $qty = 2): int
    {
        Sanctum::actingAs($cashier);

        return $this->postJson('/api/mobile/staff/sales', [
            'items' => [['product_id' => $this->product->id, 'quantity' => $qty]],
            'payments' => [['method' => 'Cash', 'amount' => 10000 * $qty]],
        ])->assertCreated()->json('data.id');
    }

    public function test_a_normal_pos_sale_is_logged_without_flags(): void
    {
        $cashier = $this->user('cashier', ['manage_sales']);
        $this->sellOnMobile($cashier);

        $sale = ActivityLog::where('subject_type', 'sale')->where('action', 'created')->sole();
        $this->assertSame($cashier->id, $sale->user_id);
        $this->assertSame('mobile', $sale->source);
        $this->assertSame('pos_sale', $sale->context);
        $this->assertSame(0, ActivityLog::where('flagged', true)->count());
        // The stock taken off by the sale is recorded as part of the sale, not as a hand edit.
        $this->assertSame([10, 8], array_map('intval', ActivityLog::where('subject_type', 'stock')->sole()->changes['quantity']));
    }

    public function test_raising_stock_by_hand_after_a_sale_is_flagged(): void
    {
        $cashier = $this->user('cashier', ['manage_sales', 'manage_stock']);
        $this->sellOnMobile($cashier);

        // The pattern the owner described: sell, then put the count back.
        $this->putJson("/api/mobile/staff/stock/{$this->stock->id}", ['quantity' => 10])->assertOk();

        $log = ActivityLog::where('flagged', true)->sole();
        $this->assertSame($cashier->id, $log->user_id);
        $this->assertStringContainsString('raised by hand from 8 to 10', $log->flag_reason);
        $this->assertSame(['8', '10'], array_map(fn ($v) => (string) (int) $v, $log->changes['quantity']));
    }

    public function test_receiving_stock_is_not_flagged(): void
    {
        Sanctum::actingAs($this->user('manager', ['manage_stock']));

        $this->postJson('/api/mobile/staff/stock', ['product_id' => $this->product->id, 'quantity' => 5])->assertCreated();

        $this->assertSame(0, ActivityLog::where('flagged', true)->count());
        $this->assertSame('stock_received', ActivityLog::where('subject_type', 'stock')->sole()->context);
    }

    public function test_changing_a_price_without_touching_the_count_is_not_flagged(): void
    {
        Sanctum::actingAs($this->user('manager', ['manage_stock']));

        $this->putJson("/api/mobile/staff/stock/{$this->stock->id}", ['selling_price' => 12000])->assertOk();

        $log = ActivityLog::where('subject_type', 'stock')->sole();
        $this->assertFalse($log->flagged);
        $this->assertSame([10000, 12000], array_map('intval', $log->changes['selling_price']));
    }

    public function test_deleting_or_editing_a_sale_on_the_website_is_flagged(): void
    {
        $saleId = $this->sellOnMobile($this->user('cashier', ['manage_sales']));
        $admin = $this->user('admin');
        $sale = Sale::find($saleId);

        $item = SaleItem::where('sale_id', $saleId)->first();
        $this->actingAs($admin)->put(route('sale_items.update', $item), ['quantity' => 1, 'total' => 10000]);
        $this->actingAs($admin)->delete(route('payments.destroy', Payment::where('sale_id', $saleId)->first()));
        $this->actingAs($admin)->delete(route('sales.destroy', $sale));

        $reasons = ActivityLog::where('flagged', true)->where('user_id', $admin->id)->pluck('flag_reason')->implode(' | ');
        $this->assertStringContainsString('Sold item changed after the sale', $reasons);
        $this->assertStringContainsString('Payment receipt deleted', $reasons);
        $this->assertStringContainsString('Sale deleted', $reasons);
        $this->assertSame('web', ActivityLog::where('subject_type', 'sale')->where('action', 'deleted')->value('source'));
    }

    public function test_sign_ins_are_logged(): void
    {
        $user = $this->user('cashier');

        $this->postJson('/api/mobile/login', ['email' => $user->email, 'password' => 'wrong'])->assertUnprocessable();
        $this->postJson('/api/mobile/login', ['email' => $user->email, 'password' => 'password'])->assertOk();
        $this->post('/login', ['email' => $user->email, 'password' => 'wrong']);
        // The website finishes sign-in after an emailed code with Auth::login, which fires Login.
        \Illuminate\Support\Facades\Auth::login($user);

        $this->assertSame(['mobile', 'web'], ActivityLog::where('action', 'login_failed')->orderBy('id')->pluck('source')->all());
        $this->assertSame(['mobile', 'web'], ActivityLog::where('action', 'login')->orderBy('id')->pluck('source')->all());
    }

    public function test_only_permitted_users_can_read_the_log(): void
    {
        $cashier = $this->user('cashier', ['manage_sales']);
        $this->sellOnMobile($cashier);

        $this->getJson('/api/mobile/staff/activity-logs')->assertForbidden();
        $this->actingAs($cashier)->get(route('activity-logs.index'))->assertForbidden();

        $owner = $this->user('manager', ['view_activity_logs']);
        Sanctum::actingAs($owner);
        $this->getJson('/api/mobile/staff/activity-logs?module=sale')->assertOk()->assertJsonPath('data.0.user_id', $cashier->id);
        $this->getJson('/api/mobile/staff/activity-logs/summary')->assertOk()->assertJsonStructure(['flagged_this_week', 'top_flagged', 'users', 'modules']);
        $this->actingAs($this->user('admin'))->get(route('activity-logs.index', ['flagged' => 1]))->assertOk()->assertSee('Activity log');
    }

    public function test_flagged_filter_returns_only_suspicious_entries(): void
    {
        $cashier = $this->user('cashier', ['manage_sales', 'manage_stock']);
        $this->sellOnMobile($cashier);
        $this->putJson("/api/mobile/staff/stock/{$this->stock->id}", ['quantity' => 10])->assertOk();

        Sanctum::actingAs($this->user('admin'));
        $this->getJson('/api/mobile/staff/activity-logs?flagged=1')
            ->assertOk()
            ->assertJsonCount(1, 'data')
            ->assertJsonPath('data.0.flagged', true);
        $this->getJson('/api/mobile/staff/activity-logs/summary')->assertJsonPath('top_flagged.0.user_id', $cashier->id);
    }

    public function test_branding_is_public_and_uses_admin_uploads(): void
    {
        Setting::create(['setting_key' => 'site_name', 'setting_value' => 'Bweyamo Hardware']);
        Setting::create(['setting_key' => 'system_logo', 'setting_value' => 'uploads/settings/logo-1.png']);
        Setting::create(['setting_key' => 'system_favicon', 'setting_value' => 'uploads/settings/favicon-1.png']);
        Cache::flush();

        $this->getJson('/api/mobile/branding')
            ->assertOk()
            ->assertJsonPath('site_name', 'Bweyamo Hardware')
            ->assertJsonPath('logo_url', url('uploads/settings/logo-1.png'))
            ->assertJsonPath('favicon_url', url('uploads/settings/favicon-1.png'));
    }

    public function test_customer_records_do_not_break_when_logging(): void
    {
        Sanctum::actingAs($this->user('manager', ['manage_customers']));
        $this->postJson('/api/mobile/staff/catalog/customers', ['name' => 'Jane'])->assertCreated();
        $id = Customer::where('name', 'Jane')->value('id');
        $this->putJson("/api/mobile/staff/catalog/customers/{$id}", ['name' => 'Jane Doe'])->assertOk();

        $this->assertSame(['Jane', 'Jane Doe'], ActivityLog::where('subject_type', 'customer')->where('action', 'updated')->sole()->changes['name']);
    }
}
