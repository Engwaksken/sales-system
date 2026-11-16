<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Customer;
use App\Models\Product;
use App\Models\RolePermission;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\UnitType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class DashboardUnitsProfileTest extends TestCase
{
    use RefreshDatabase;

    private Branch $main;
    private Branch $other;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Cache::flush();
        $this->main = Branch::create(['name' => 'Main Branch', 'location' => 'Kampala']);
        $this->other = Branch::create(['name' => 'Jinja', 'location' => 'Jinja']);
    }

    private function user(string $role, array $permissions = [], ?Branch $branch = null): User
    {
        foreach ($permissions as $permission) {
            RolePermission::firstOrCreate(['role' => $role, 'permission_key' => $permission]);
        }

        return User::create([
            'name' => ucfirst($role), 'email' => Str::lower(Str::random(10)).'@example.com',
            'password' => Hash::make('password'), 'role' => $role, 'branch_id' => ($branch ?? $this->main)->id,
        ]);
    }

    private function sale(Branch $branch, float $total): void
    {
        Sale::create([
            'transaction_type' => 'sale', 'invoice_no' => 'INV-'.Str::random(5), 'total_amount' => $total,
            'balance_amount' => 0, 'payment_status' => 'paid', 'branch_id' => $branch->id, 'sale_date' => now()->toDateString(),
        ]);
    }

    public function test_mobile_dashboard_cards_follow_permissions_and_say_where_they_open(): void
    {
        Sanctum::actingAs($this->user('manager', ['manage_stock']));

        $stats = collect($this->getJson('/api/mobile/dashboard')->assertOk()->json('stats'));

        $this->assertSame(['low_stock', 'notifications'], $stats->pluck('key')->all());
        $this->assertSame('stock', $stats->firstWhere('key', 'low_stock')['module']);
        $this->assertEquals(['low' => 1], $stats->firstWhere('key', 'low_stock')['filter']);
    }

    public function test_mobile_dashboard_is_branch_scoped_and_has_charts(): void
    {
        $this->sale($this->main, 1000);
        $this->sale($this->other, 5000);

        Sanctum::actingAs($this->user('cashier', ['manage_sales']));
        $data = $this->getJson('/api/mobile/dashboard')->assertOk();
        $this->assertSame(1000, (int) collect($data->json('stats'))->firstWhere('key', 'today_sales')['value']);
        $this->assertCount(7, $data->json('charts.monthly'));
        $this->assertCount(14, $data->json('charts.daily'));
        $this->assertSame(1000, (int) collect($data->json('charts.daily'))->last()['value']);

        Sanctum::actingAs($this->user('admin'));
        $this->assertSame(6000, (int) collect($this->getJson('/api/mobile/dashboard')->json('stats'))->firstWhere('key', 'today_sales')['value']);
    }

    public function test_outstanding_sales_filter_matches_the_card(): void
    {
        Sale::create(['transaction_type' => 'sale', 'invoice_no' => 'INV-OWE', 'total_amount' => 900, 'balance_amount' => 400,
            'payment_status' => 'partial', 'branch_id' => $this->main->id, 'sale_date' => now()->toDateString()]);
        $this->sale($this->main, 100);
        Sanctum::actingAs($this->user('cashier', ['manage_sales']));

        $this->getJson('/api/mobile/staff/sales?status=outstanding')->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('data.0.invoice_no', 'INV-OWE');
    }

    public function test_units_are_pre_inserted_and_offered_to_stock_forms(): void
    {
        $this->assertGreaterThan(30, UnitType::count());
        $this->assertTrue(UnitType::where('abbreviation', 'pcs')->exists());
        $this->assertTrue(UnitType::where('abbreviation', 'kg')->exists());

        Sanctum::actingAs($this->user('manager', ['manage_stock']));
        $units = collect($this->getJson('/api/mobile/staff/lookups')->assertOk()->json('unit_types'));
        $this->assertSame('Pieces (pcs)', $units->firstWhere('value', 'pcs')['label']);
    }

    public function test_stock_keepers_can_add_and_switch_off_units_from_the_app(): void
    {
        Sanctum::actingAs($this->user('manager', ['manage_stock']));

        $id = $this->postJson('/api/mobile/staff/catalog/unit-types', ['name' => 'Crate of 24', 'abbreviation' => 'crate24', 'category' => 'Count'])
            ->assertCreated()->json('data.id');
        $this->postJson('/api/mobile/staff/catalog/unit-types', ['name' => 'Again', 'abbreviation' => 'crate24'])->assertUnprocessable();
        $this->putJson("/api/mobile/staff/catalog/unit-types/{$id}", ['is_active' => false])->assertOk()->assertJsonPath('data.status', 'inactive');

        $this->assertFalse(UnitType::options()->contains('value', 'crate24'));
        // A row already using a switched-off unit keeps it selectable.
        $this->assertTrue(UnitType::options('crate24')->contains('value', 'crate24'));
    }

    public function test_website_unit_page_and_stock_form_dropdown(): void
    {
        $keeper = $this->user('manager', ['manage_stock']);
        $this->actingAs($keeper)->get(route('unit_types.index'))->assertOk()->assertSee('Kilogram');
        $this->actingAs($keeper)->post(route('unit_types.store'), ['name' => 'Bucket', 'abbreviation' => 'bkt', 'category' => 'Count'])
            ->assertRedirect(route('unit_types.index'));
        $this->actingAs($keeper)->get(route('stock.index'))->assertOk()
            ->assertSee('<select name="unit_type"', false)->assertSee('Bucket (bkt)');

        $product = Product::create(['name' => 'Steel Bucket', 'is_active' => true]);
        Stock::create(['product_id' => $product->id, 'branch_id' => $this->main->id, 'quantity' => 1, 'total_stock' => 1, 'unit_type' => 'bkt']);
        $this->actingAs($keeper)->delete(route('unit_types.destroy', UnitType::where('abbreviation', 'bkt')->first()))
            ->assertSessionHasErrors('unit');

        $this->actingAs($this->user('cashier', ['manage_sales']))->get(route('unit_types.index'))->assertForbidden();
    }

    public function test_users_manage_their_profile_and_password_in_the_app(): void
    {
        Storage::fake('public');
        $user = $this->user('cashier', ['manage_sales']);
        Sanctum::actingAs($user);

        $this->getJson('/api/mobile/profile')->assertOk()->assertJsonPath('data.branch_name', 'Main Branch')->assertJsonPath('data.is_customer', false);
        $path = $this->post('/api/mobile/profile', ['name' => 'Grace N.', 'photo' => UploadedFile::fake()->image('me.jpg')], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('data.name', 'Grace N.')->json('data.photo_url');
        $this->assertStringContainsString('/media/avatars/', $path);
        Storage::disk('public')->assertExists($user->fresh()->profile_pic);

        $this->putJson('/api/mobile/profile/password', ['current_password' => 'wrong', 'password' => 'newpass123', 'password_confirmation' => 'newpass123'])
            ->assertUnprocessable()->assertJsonValidationErrors('current_password');
        $this->putJson('/api/mobile/profile/password', ['current_password' => 'password', 'password' => 'newpass123', 'password_confirmation' => 'newpass123'])
            ->assertOk();
        $this->assertTrue(Hash::check('newpass123', $user->fresh()->password));
    }

    public function test_customers_also_update_phone_and_address(): void
    {
        $user = $this->user('customer');
        Sanctum::actingAs($user);

        $this->post('/api/mobile/profile', ['name' => 'Jane', 'phone' => '0700111222', 'address' => 'Plot 5, Kira'], ['Accept' => 'application/json'])
            ->assertOk()->assertJsonPath('data.phone', '0700111222')->assertJsonPath('data.address', 'Plot 5, Kira');
        $this->assertSame('Plot 5, Kira', Customer::where('user_id', $user->id)->value('address'));
    }

    public function test_website_dashboard_cards_link_to_their_lists(): void
    {
        $admin = $this->user('admin');

        $this->actingAs($admin)->get(route('dashboard'))->assertOk()
            ->assertSee(route('orders.index', ['status' => 'pending']), false)
            ->assertSee(route('stock.index', ['low' => 1]), false)
            ->assertSee(route('sales.index', ['payment_status' => 'outstanding']), false)
            ->assertSee('fa-boxes-stacked', false);
    }
}
