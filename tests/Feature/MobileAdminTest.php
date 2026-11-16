<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\PaymentGateway;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileAdminTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        Cache::flush();
    }

    private function user(string $role, array $permissions = []): User
    {
        foreach ($permissions as $permission) {
            RolePermission::firstOrCreate(['role' => $role, 'permission_key' => $permission]);
        }
        $user = User::create([
            'name' => ucfirst($role), 'email' => Str::lower(Str::random(10)).'@example.com',
            'password' => 'password', 'role' => $role, 'branch_id' => null,
        ]);
        Sanctum::actingAs($user);

        return $user;
    }

    public function test_admin_sees_every_module_and_staff_only_what_they_may_manage(): void
    {
        $this->user('admin');
        $keys = collect($this->getJson('/api/mobile/staff/admin/modules')->assertOk()->json('data'))->pluck('key');
        foreach (['users', 'branches', 'delivery-zones', 'chart-of-accounts', 'journals', 'payment-gateways', 'settings', 'role-permissions', 'efris-config'] as $key) {
            $this->assertContains($key, $keys);
        }

        Cache::flush();
        $this->user('cashier', ['manage_branches']);
        $keys = collect($this->getJson('/api/mobile/staff/admin/modules')->json('data'))->pluck('key');
        $this->assertSame(['branches'], $keys->all());
        $this->getJson('/api/mobile/staff/admin/users')->assertForbidden();
        $this->getJson('/api/mobile/staff/admin/settings')->assertForbidden();
    }

    public function test_admin_creates_edits_and_deletes_a_branch(): void
    {
        $this->user('admin');

        $this->getJson('/api/mobile/staff/admin/branches/schema')->assertOk()->assertJsonPath('data.fields.0.name', 'name');
        $id = $this->postJson('/api/mobile/staff/admin/branches', ['name' => 'Jinja', 'location' => 'Main St'])
            ->assertCreated()->assertJsonPath('data.values.name', 'Jinja')->json('data.id');

        $this->getJson('/api/mobile/staff/admin/branches?q=jinja')->assertOk()->assertJsonPath('data.0.title', 'Jinja');
        $this->putJson("/api/mobile/staff/admin/branches/{$id}", ['name' => 'Jinja Road', 'location' => 'Main St'])->assertOk();
        $this->assertSame('Jinja Road', Branch::find($id)->name);

        $this->deleteJson("/api/mobile/staff/admin/branches/{$id}")->assertOk();
        $this->assertNull(Branch::find($id));
    }

    public function test_users_get_hashed_passwords_and_blank_password_keeps_the_old_one(): void
    {
        $admin = $this->user('admin');

        $id = $this->postJson('/api/mobile/staff/admin/users', ['name' => 'Ann', 'email' => 'ann@example.com', 'password' => 'secret123', 'role' => 'cashier', 'branch_id' => null])
            ->assertCreated()->assertJsonPath('data.values.password', null)->json('data.id');
        $this->assertTrue(Hash::check('secret123', User::find($id)->password));

        $this->putJson("/api/mobile/staff/admin/users/{$id}", ['name' => 'Ann B', 'email' => 'ann@example.com', 'password' => '', 'role' => 'cashier', 'branch_id' => null])->assertOk();
        $this->assertTrue(Hash::check('secret123', User::find($id)->password));

        $this->postJson('/api/mobile/staff/admin/users', ['name' => 'Dup', 'email' => 'ann@example.com', 'password' => 'secret123', 'role' => 'cashier', 'branch_id' => null])
            ->assertUnprocessable()->assertJsonValidationErrors('email');
        $this->deleteJson("/api/mobile/staff/admin/users/{$admin->id}")->assertUnprocessable();
    }

    public function test_gateway_secrets_are_never_sent_and_blank_keeps_them(): void
    {
        $this->user('admin');
        $body = ['gateway_name' => 'IOTEC', 'gateway_code' => 'iotec', 'display_name' => 'Mobile Money', 'provider_type' => 'mobile_money', 'client_secret' => 'top-secret', 'is_active' => true];

        $response = $this->postJson('/api/mobile/staff/admin/payment-gateways', $body)->assertCreated();
        $this->assertStringNotContainsString('top-secret', $response->getContent());
        $response->assertJsonPath('data.values.client_secret__set', true);
        $id = $response->json('data.id');

        $this->putJson("/api/mobile/staff/admin/payment-gateways/{$id}", ['client_secret' => ''] + $body)->assertOk();
        $this->assertSame('top-secret', PaymentGateway::find($id)->client_secret);
        $this->assertStringNotContainsString('top-secret', $this->getJson("/api/mobile/staff/admin/payment-gateways/{$id}")->getContent());
    }

    public function test_view_only_modules_refuse_changes(): void
    {
        $this->user('admin');
        $this->postJson('/api/mobile/staff/admin/efris-invoices', [])->assertForbidden();
        $this->getJson('/api/mobile/staff/admin/nope')->assertNotFound();
    }

    public function test_admin_updates_settings_with_the_website_rules(): void
    {
        $this->user('admin');
        $values = $this->getJson('/api/mobile/staff/admin/settings')->assertOk()->json('data.values');

        $this->postJson('/api/mobile/staff/admin/settings', ['site_name' => 'Unihub', 'theme_primary' => '#1d2b72'] + $values)
            ->assertOk()->assertJsonPath('data.values.site_name', 'Unihub');
        $this->assertSame('#1d2b72', Setting::where('setting_key', 'theme_primary')->value('setting_value'));

        $this->postJson('/api/mobile/staff/admin/settings', ['theme_primary' => 'blue'] + $values)
            ->assertUnprocessable()->assertJsonValidationErrors('theme_primary');
    }

    public function test_admin_edits_role_permissions_and_adds_a_role(): void
    {
        $this->user('admin');
        Permission::firstOrCreate(['permission_key' => 'manage_sales'], ['label' => 'Manage sales', 'category' => 'Sales', 'description' => 'Sell']);

        $keys = collect($this->getJson('/api/mobile/staff/admin/role-permissions')->assertOk()->json('data.groups'))->pluck('permissions')->flatten(1)->pluck('key');
        $this->assertContains('manage_sales', $keys);
        $this->putJson('/api/mobile/staff/admin/role-permissions', ['permissions' => ['cashier' => ['manage_sales']]])->assertOk();
        $this->assertTrue(RolePermission::where(['role' => 'cashier', 'permission_key' => 'manage_sales'])->exists());

        $this->postJson('/api/mobile/staff/admin/roles', ['label' => 'Store Keeper'])->assertCreated();
        $this->postJson('/api/mobile/staff/admin/roles', ['label' => 'Admin'])->assertUnprocessable();
    }
}
