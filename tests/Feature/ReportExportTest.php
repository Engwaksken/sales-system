<?php

namespace Tests\Feature;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\TestCase;

class ReportExportTest extends TestCase
{
    use RefreshDatabase;

    private User $user;
    private Branch $kampala;
    private Branch $jinja;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::create([
            'name' => 'Admin', 'email' => Str::random(10).'@example.com',
            'password' => 'password', 'role' => 'admin', 'branch_id' => null,
        ]);
        $this->kampala = Branch::create(['name' => 'Kampala Branch', 'location' => 'Kampala']);
        $this->jinja = Branch::create(['name' => 'Jinja Branch', 'location' => 'Jinja']);

        foreach ([[$this->kampala, 'Kampala rent'], [$this->jinja, 'Jinja rent']] as [$branch, $description]) {
            Expense::create([
                'title' => $description, 'category' => 'Rent', 'payment_method' => 'Cash', 'branch_id' => $branch->id, 'amount' => 50000,
                'expense_date' => now()->toDateString(), 'description' => $description,
            ]);
        }
    }

    private function filters(): array
    {
        return [
            'from' => now()->startOfMonth()->toDateString(),
            'to' => now()->toDateString(),
            'branch_id' => $this->kampala->id,
        ];
    }

    public function test_csv_export_contains_only_filtered_rows(): void
    {
        $response = $this->actingAs($this->user)
            ->get(route('reports.export', ['expenses', 'csv']).'?'.http_build_query($this->filters()))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=UTF-8');

        $csv = $response->streamedContent();
        $this->assertStringContainsString('Kampala rent', $csv);
        $this->assertStringNotContainsString('Jinja rent', $csv);
    }

    public function test_print_view_shows_filters_and_rows(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.export', ['expenses', 'print']).'?'.http_build_query($this->filters()))
            ->assertOk()
            ->assertSee('Expenses Report')
            ->assertSee('Kampala Branch')
            ->assertSee('Kampala rent')
            ->assertDontSee('Jinja rent')
            ->assertSee('window.print()', false);
    }

    public function test_pdf_export_downloads(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.export', ['expenses', 'pdf']).'?'.http_build_query($this->filters()))
            ->assertOk()
            ->assertHeader('Content-Type', 'application/pdf');
    }

    public function test_report_page_links_exports_with_current_filters(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.show', 'expenses').'?'.http_build_query($this->filters()))
            ->assertOk()
            ->assertSee('Export CSV')
            ->assertSee('Export PDF')
            ->assertSee('branch_id='.$this->kampala->id, false);
    }

    public function test_unknown_report_type_is_not_found(): void
    {
        $this->actingAs($this->user)
            ->get(route('reports.export', ['nonsense', 'csv']))
            ->assertNotFound();
    }
}
