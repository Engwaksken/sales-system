<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\ExpenseCategory;
use App\Models\RolePermission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class ExpenseFormTest extends TestCase
{
    use RefreshDatabase;

    private Branch $branch;

    protected function setUp(): void
    {
        parent::setUp();
        Mail::fake();
        $this->branch = Branch::create(['name' => 'Main Branch', 'location' => 'Kampala']);
    }

    private function staff(string $role = 'accountant'): User
    {
        RolePermission::firstOrCreate(['role' => $role, 'permission_key' => 'manage_expenses']);

        return User::create([
            'name' => 'Clerk', 'email' => Str::lower(Str::random(8)).'@example.com', 'password' => 'password',
            'role' => $role, 'branch_id' => $this->branch->id,
        ]);
    }

    public function test_common_categories_are_pre_inserted(): void
    {
        $names = ExpenseCategory::pluck('name');

        $this->assertGreaterThanOrEqual(25, $names->count());
        $this->assertTrue($names->contains('Fuel'));
        $this->assertTrue($names->contains('Mobile Money Charges'));
        $this->assertSame($names->count(), $names->map(fn ($n) => strtolower($n))->unique()->count());
    }

    public function test_app_saves_an_expense_without_a_title(): void
    {
        Sanctum::actingAs($this->staff());

        $this->postJson('/api/mobile/expenses', [
            'expense_date' => '2026-11-15', 'amount' => 50000, 'payee' => 'Shell Bugolobi',
            'payment_method' => 'Mobile Money', 'category' => 'Fuel',
        ])->assertCreated()
            ->assertJsonPath('data.title', 'Fuel – Shell Bugolobi')
            ->assertJsonPath('data.payment_method', 'Mobile Money');
    }

    public function test_admin_must_select_a_branch_when_saving_an_expense(): void
    {
        $admin = User::create([
            'name' => 'Admin', 'email' => Str::lower(Str::random(8)).'@example.com', 'password' => 'password',
            'role' => 'admin', 'branch_id' => null,
        ]);
        Sanctum::actingAs($admin);

        $payload = ['expense_date' => '2026-11-15', 'amount' => 50000, 'category' => 'Fuel'];
        $this->postJson('/api/mobile/expenses', $payload)
            ->assertUnprocessable()
            ->assertJsonValidationErrors('branch_id');

        $this->postJson('/api/mobile/expenses', [...$payload, 'branch_id' => $this->branch->id])
            ->assertCreated()
            ->assertJsonPath('data.branch_id', $this->branch->id);
    }

    public function test_app_can_add_a_custom_category_once(): void
    {
        Sanctum::actingAs($this->staff());

        foreach (['Generator fuel', 'generator FUEL'] as $typed) {
            $this->postJson('/api/mobile/expenses', [
                'expense_date' => '2026-11-15', 'amount' => 20000, 'category_new' => $typed,
            ])->assertCreated()->assertJsonPath('data.category', 'Generator fuel');
        }

        $this->assertSame(1, ExpenseCategory::whereRaw('LOWER(name) = ?', ['generator fuel'])->count());
        $this->assertSame('Generator fuel – 15 Nov 2026', Expense::latest('id')->value('title'));
        $this->assertContains('Generator fuel', $this->getJson('/api/mobile/staff/lookups')->json('expense_category_names'));
    }

    public function test_a_category_is_still_required(): void
    {
        Sanctum::actingAs($this->staff());

        $this->postJson('/api/mobile/expenses', ['expense_date' => '2026-11-15', 'amount' => 100])
            ->assertUnprocessable()->assertJsonValidationErrors('category');
    }

    public function test_website_form_accepts_a_new_category_and_blank_title(): void
    {
        $user = $this->staff();

        $this->actingAs($user)->post(route('expenses.store'), [
            'amount' => 15000, 'expense_date' => '2026-11-15', 'payment_method' => 'Cash',
            'category' => Expense::NEW_CATEGORY, 'category_new' => 'Water for staff', 'branch_id' => $this->branch->id,
        ])->assertRedirect(route('expenses.index'))->assertSessionHasNoErrors();

        $expense = Expense::sole();
        $this->assertSame('Water for staff', $expense->category);
        $this->assertSame('Water for staff – 15 Nov 2026', $expense->title);
        $this->assertSame('Cash', $expense->payment_method);
        $this->assertTrue(ExpenseCategory::where('name', 'Water for staff')->exists());

        $this->actingAs($user)->post(route('expenses.store'), [
            'amount' => 1, 'expense_date' => '2026-11-15', 'category' => Expense::NEW_CATEGORY, 'branch_id' => $this->branch->id,
        ])->assertSessionHasErrors('category_new');
    }

    public function test_editing_without_a_title_keeps_the_existing_one(): void
    {
        $user = $this->staff();
        $expense = Expense::create(['title' => 'Monthly rent', 'amount' => 500000, 'expense_date' => '2026-11-01', 'category' => 'Rent', 'branch_id' => $this->branch->id]);

        $this->actingAs($user)->put(route('expenses.update', $expense), ['amount' => 550000])->assertSessionHasNoErrors();

        $this->assertSame('Monthly rent', $expense->fresh()->title);
        $this->assertSame('550000.00', (string) $expense->fresh()->amount);
    }

    public function test_website_form_lists_categories_and_payment_methods(): void
    {
        Expense::create(['title' => 'Old', 'amount' => 1, 'expense_date' => '2026-01-01', 'category' => 'Legacy Typed Category', 'branch_id' => $this->branch->id]);

        $this->actingAs($this->staff())->get(route('expenses.index'))->assertOk()
            ->assertSee('+ Add a new category')
            ->assertSee('Legacy Typed Category')
            ->assertSee('Mobile Money')
            ->assertSee('(optional)', false);
    }
}
