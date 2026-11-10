<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Permission;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Tests\TestCase;

class NewStaffRolesTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $role): User
    {
        return User::create([
            'name' => User::roleLabel($role), 'email' => Str::random(10).'@example.com',
            'password' => 'password', 'role' => $role, 'branch_id' => null,
        ]);
    }

    public function test_admin_can_create_users_with_the_new_roles(): void
    {
        Mail::fake();
        $admin = $this->makeUser('admin');
        $branch = Branch::create(['name' => 'Main Branch', 'location' => 'Kampala']);

        foreach (['general_manager', 'marketing_manager', 'accountant'] as $role) {
            $this->actingAs($admin)->post(route('users.store'), [
                'name' => "New {$role}",
                'email' => "{$role}@example.com",
                'password' => 'secret-password',
                'password_confirmation' => 'secret-password',
                'role' => $role,
                'branch_id' => $branch->id,
            ])->assertSessionHasNoErrors()->assertRedirect(route('users.index'));

            $this->assertSame($role, User::where('email', "{$role}@example.com")->value('role'));
        }
    }

    public function test_unknown_roles_are_rejected(): void
    {
        $this->actingAs($this->makeUser('admin'))->post(route('users.store'), [
            'name' => 'Bad', 'email' => 'bad@example.com',
            'password' => 'secret-password', 'password_confirmation' => 'secret-password',
            'role' => 'superuser',
        ])->assertSessionHasErrors('role');
    }

    public function test_new_roles_get_default_permissions_and_appear_in_the_matrix(): void
    {
        Permission::create(['permission_key' => 'manage_expenses', 'label' => 'Manage Expenses', 'category' => 'Finance']);

        $this->assertTrue(RolePermission::where('role', 'accountant')->where('permission_key', 'manage_expenses')->exists());
        $this->assertTrue(RolePermission::where('role', 'marketing_manager')->where('permission_key', 'manage_customers')->exists());
        $this->assertTrue(RolePermission::where('role', 'general_manager')->where('permission_key', 'manage_stock')->exists());
        $this->assertFalse(RolePermission::where('role', 'marketing_manager')->where('permission_key', 'manage_expenses')->exists());

        $this->actingAs($this->makeUser('admin'))->get(route('role_permissions.index'))
            ->assertOk()->assertSee('General Manager')->assertSee('Marketing Manager')->assertSee('Accountant');
    }

    public function test_permissions_gate_the_new_roles(): void
    {
        $accountant = $this->makeUser('accountant');
        $marketing = $this->makeUser('marketing_manager');

        $this->actingAs($accountant)->get(route('expenses.index'))->assertOk();
        $this->actingAs($marketing)->get(route('expenses.index'))->assertForbidden();
        $this->actingAs($marketing)->get(route('customers.index'))->assertOk();
    }
}
