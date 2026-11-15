<?php

namespace Tests\Feature;

use App\Models\AppNotification;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\NotificationPreference;
use App\Models\Product;
use App\Models\RolePermission;
use App\Models\Setting;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class NotificationPreferencesTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Cache::flush();
        $this->branch = Branch::create(['name' => 'Main Branch', 'location' => 'Kampala']);
    }

    private function user(string $role, array $permissions = []): User
    {
        foreach ($permissions as $permission) {
            RolePermission::firstOrCreate(['role' => $role, 'permission_key' => $permission]);
        }

        return User::create([
            'name' => ucfirst($role).' '.Str::random(4), 'email' => Str::lower(Str::random(10)).'@example.com',
            'password' => bcrypt('password'), 'role' => $role, 'branch_id' => $this->branch->id,
        ]);
    }

    public function test_each_role_only_sees_options_it_can_receive(): void
    {
        Sanctum::actingAs($this->user('customer'));
        $customerKeys = collect($this->getJson('/api/mobile/notification-preferences')->assertOk()->json('data.options'))->pluck('key')->all();
        $this->assertEqualsCanonicalizing(['announcements', 'order_updates', 'chat_replies', 'email_receipts'], $customerKeys);

        Sanctum::actingAs($this->user('cashier', ['manage_stock']));
        $staff = $this->getJson('/api/mobile/notification-preferences')->assertOk();
        $this->assertEqualsCanonicalizing(['announcements', 'low_stock'], collect($staff->json('data.options'))->pluck('key')->all());
        $staff->assertJsonPath('data.low_stock_threshold', 5);
    }

    public function test_users_can_save_settings_but_not_options_outside_their_role(): void
    {
        $cashier = $this->user('cashier', ['manage_stock']);
        Sanctum::actingAs($cashier);

        $this->putJson('/api/mobile/notification-preferences', [
            'preferences' => ['announcements' => false, 'flagged_activity' => true],
            'low_stock_threshold' => 12,
        ])->assertOk()->assertJsonPath('data.low_stock_threshold', 12);

        $saved = NotificationPreference::where('user_id', $cashier->id)->sole();
        $this->assertFalse($saved->preferences['announcements']);
        $this->assertArrayNotHasKey('flagged_activity', $saved->preferences);
    }

    public function test_muted_announcements_leave_the_inbox_and_badge(): void
    {
        $customer = $this->user('customer');
        AppNotification::create(['title' => 'Sale this weekend', 'message' => 'Everything 10% off', 'type' => 'info']);
        Sanctum::actingAs($customer);

        $this->getJson('/api/mobile/customer/notifications/unread-count')->assertJsonPath('count', 1);

        $this->putJson('/api/mobile/notification-preferences', ['preferences' => ['announcements' => false]])->assertOk();
        $customer->unsetRelation('notificationPreference');

        $this->getJson('/api/mobile/customer/notifications/unread-count')->assertJsonPath('count', 0);
        $this->getJson('/api/mobile/customer/notifications')->assertJsonCount(0, 'data');
    }

    public function test_low_stock_alert_uses_each_users_own_limit(): void
    {
        $keeper = $this->user('manager', ['manage_stock']);
        $cashier = $this->user('cashier', ['manage_sales']);
        NotificationPreference::saveFor($keeper, [], 8);

        $product = Product::create(['name' => 'Steel Bucket', 'is_active' => true]);
        Stock::create(['product_id' => $product->id, 'branch_id' => $this->branch->id, 'quantity' => 10, 'total_stock' => 10, 'selling_price' => 1000]);

        Sanctum::actingAs($cashier);
        $this->postJson('/api/mobile/staff/sales', [
            'items' => [['product_id' => $product->id, 'quantity' => 3]],
            'payments' => [['method' => 'Cash', 'amount' => 3000]],
        ])->assertCreated();

        $alert = AppNotification::where('user_id', $keeper->id)->sole();
        $this->assertStringContainsString('Low stock: Steel Bucket', $alert->title);
        $this->assertStringContainsString('down to 7 (your alert level is 8)', $alert->message);
        $this->assertSame(0, AppNotification::where('user_id', $cashier->id)->count());
    }

    public function test_low_stock_alert_can_be_switched_off(): void
    {
        $keeper = $this->user('manager', ['manage_stock']);
        NotificationPreference::saveFor($keeper, ['low_stock' => false]);
        $product = Product::create(['name' => 'Bucket', 'is_active' => true]);
        $stock = Stock::create(['product_id' => $product->id, 'branch_id' => $this->branch->id, 'quantity' => 6, 'total_stock' => 6]);

        $stock->update(['quantity' => 1]);

        $this->assertSame(0, AppNotification::where('user_id', $keeper->id)->count());
    }

    public function test_owners_are_alerted_about_flagged_changes(): void
    {
        $owner = $this->user('manager', ['view_activity_logs']);
        $quiet = $this->user('accountant', ['view_activity_logs']);
        NotificationPreference::saveFor($quiet, ['flagged_activity' => false]);
        $clerk = $this->user('cashier', ['manage_stock']);

        $product = Product::create(['name' => 'Bucket', 'is_active' => true]);
        $stock = Stock::create(['product_id' => $product->id, 'branch_id' => $this->branch->id, 'quantity' => 6, 'total_stock' => 6]);
        Sanctum::actingAs($clerk);
        $this->putJson("/api/mobile/staff/stock/{$stock->id}", ['quantity' => 9])->assertOk();

        $alert = AppNotification::where('user_id', $owner->id)->where('title', 'like', '%Stock count%')->sole();
        $this->assertStringContainsString('Flagged: Stock count raised by hand', $alert->title);
        $this->assertSame(0, AppNotification::where('user_id', $quiet->id)->count());
        $this->assertSame(0, AppNotification::where('user_id', $clerk->id)->count());
    }

    public function test_customers_can_turn_off_receipt_emails(): void
    {
        $user = $this->user('customer');
        $customer = Customer::create(['name' => 'Jane', 'email' => 'jane@example.com', 'user_id' => $user->id]);

        $this->assertTrue(NotificationPreference::customerWantsEmail($customer));
        NotificationPreference::saveFor($user, ['email_receipts' => false]);
        $this->assertFalse(NotificationPreference::customerWantsEmail($customer->fresh()));
        // Walk-in customers without an account keep getting receipts.
        $this->assertTrue(NotificationPreference::customerWantsEmail(Customer::create(['name' => 'Walk-in', 'email' => 'w@example.com'])));
    }

    public function test_website_settings_page_saves_ticked_boxes(): void
    {
        $owner = $this->user('manager', ['view_activity_logs', 'manage_stock']);

        $this->actingAs($owner)->get(route('notification-settings.edit'))->assertOk()->assertSee('Flagged activity')->assertSee('Low stock alerts');
        $this->actingAs($owner)->put(route('notification-settings.update'), [
            'enabled' => ['flagged_activity'],
            'low_stock_threshold' => 3,
        ])->assertRedirect(route('notification-settings.edit'));

        $saved = NotificationPreference::where('user_id', $owner->id)->sole();
        $this->assertTrue($saved->preferences['flagged_activity']);
        $this->assertFalse($saved->preferences['low_stock']);
        $this->assertFalse($saved->preferences['announcements']);
        $this->assertSame(3, $saved->low_stock_threshold);
    }

    public function test_branding_command_reads_the_settings_table(): void
    {
        Setting::create(['setting_key' => 'site_name', 'setting_value' => 'Unihub Sales System']);
        Setting::create(['setting_key' => 'system_favicon', 'setting_value' => 'uploads/settings/missing.png']);

        Artisan::call('mobile:branding');
        $out = json_decode(Artisan::output(), true);

        $this->assertSame('Unihub Sales System', $out['site_name']);
        $this->assertNull($out['favicon_path']);
        $this->assertSame(url('uploads/settings/missing.png'), $out['favicon_url']);
    }
}
