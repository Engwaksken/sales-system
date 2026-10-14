<?php

namespace App\Http\Controllers;

use App\Models\ExpenseCategory;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class ExpenseCategoriesController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = ExpenseCategory::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['name']);
        $expense_categories = $query->latest()->paginate($this->perPage($request));
        $periodStats = $this->periodStats(ExpenseCategory::class, 'created_at');
        return view('expense_categories.index', ['items' => $expense_categories, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['name', 'status', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = ExpenseCategory::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['name']);
        $rows = $query->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('expense_categories-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['name', 'status', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = ExpenseCategory::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['name']);
        $rows = $query->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'ExpenseCategories', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('expense_categories-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required',
            'status' => 'boolean',
        ]);
        ExpenseCategory::create($validated);
        return redirect()->route('expense_categories.index')->with('status', 'ExpenseCategory created successfully.');
    }

    public function show(ExpenseCategory $expense_category)
    {
        return view('expense_categories.show', ['expense_category' => $expense_category]);
    }

    public function update(Request $request, ExpenseCategory $expense_category)
    {
        $validated = $request->validate([
            'name' => 'sometimes|required',
            'status' => 'boolean',
        ]);
        $expense_category->update($validated);
        return redirect()->route('expense_categories.index')->with('status', 'ExpenseCategory updated successfully.');
    }

    public function destroy(ExpenseCategory $expense_category)
    {
        $expense_category->delete();
        return redirect()->route('expense_categories.index')->with('status', 'ExpenseCategory deleted.');
    }
}
