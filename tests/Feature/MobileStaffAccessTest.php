<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class MobileStaffAccessTest extends TestCase
{
    use RefreshDatabase;

    private function user(string $role, ?int $branchId = null): User
    {
        return User::create([
            'name' => ucfirst($role), 'email' => Str::lower(Str::random(10)).'@example.com',
            'password' => Hash::make('password'), 'role' => $role, 'branch_id' => $branchId,
            'email_verified_at' => now(),
        ]);
    }

    public function test_customers_cannot_read_the_business_dashboard(): void
    {
        Sanctum::actingAs($this->user('customer'));

        $this->getJson('/api/mobile/dashboard')->assertForbidden();
    }

    public function test_staff_can_read_the_business_dashboard(): void
    {
        Sanctum::actingAs($this->user('cashier'));

        $this->getJson('/api/mobile/dashboard')
            ->assertOk()
            ->assertJsonStructure(['stats', 'recent_sales', 'recent_orders', 'currency']);
    }

    public function test_staff_login_returns_role_and_permissions(): void
    {
        $user = $this->user('admin');

        $this->postJson('/api/mobile/login', ['email' => $user->email, 'password' => 'password'])
            ->assertOk()
            ->assertJsonPath('user.role', 'admin')
            ->assertJsonPath('user.permissions', ['*']);
    }

    public function test_cashier_with_expense_permission_can_list_expenses(): void
    {
        $branch = Branch::create(['name' => 'Main Branch', 'location' => 'Kampala']);
        RolePermission::firstOrCreate(['role' => 'cashier', 'permission_key' => 'manage_expenses']);
        Sanctum::actingAs($this->user('cashier', $branch->id));

        $this->getJson('/api/mobile/expenses')->assertOk();
    }

    public function test_cashier_without_expense_permission_is_refused(): void
    {
        $branch = Branch::create(['name' => 'Main Branch', 'location' => 'Kampala']);
        RolePermission::where('role', 'cashier')->where('permission_key', 'manage_expenses')->delete();
        Sanctum::actingAs($this->user('cashier', $branch->id));

        $this->getJson('/api/mobile/expenses')->assertForbidden();
    }
}
