<?php

namespace App\Http\Controllers;

use App\Models\Expense;
use App\Models\Branch;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class ExpensesController extends Controller
{
    public const PAYMENT_METHODS = \App\Services\SalePaymentService::METHODS;

    use ExportsCsv;

    /** Period, search, branch and payment method filters shared by the list and its exports. */
    private function applyExpenseFilters($query, Request $request)
    {
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'expense_date');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['title', 'category', 'description']);
        if ($request->filled('branch_id')) {
            $query->where('branch_id', $request->integer('branch_id'));
        }
        if ($request->filled('payment_method')) {
            $query->where('payment_method', $request->query('payment_method'));
        }

        return $query;
    }

    public function index(Request $request)
    {
        $query = $this->applyExpenseFilters(Expense::query(), $request);
        $expenses = $query->with(['branch', 'createdBy', 'updatedBy'])->latest()->paginate($this->perPage($request))->withQueryString();
        $stat1 = 'UGX '.number_format(Expense::sum('amount'), 0);
        $stat2 = 'UGX '.number_format(Expense::whereMonth('expense_date', now()->month)->whereYear('expense_date', now()->year)->sum('amount'), 0);
        $periodStats = $this->periodStats(Expense::class, 'expense_date', 'amount');
        $branch_options = Branch::orderBy('id')->get();
        return view('expenses.index', ['items' => $expenses, 'branch_options' => $branch_options, 'paymentMethods' => self::PAYMENT_METHODS, 'stat1' => $stat1, 'stat2' => $stat2, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['title', 'amount', 'payment_method', 'expense_date', 'category', 'description', 'branch_id', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = $this->applyExpenseFilters(Expense::query(), $request);
        $rows = $query->with(['branch'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'branch_id') { $out[] = $row->branch?->name ?? $row->branch_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('expenses-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['title', 'amount', 'payment_method', 'expense_date', 'category', 'description', 'branch_id', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = $this->applyExpenseFilters(Expense::query(), $request);
        $rows = $query->with(['branch'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'branch_id') { $out[] = $row->branch?->name ?? $row->branch_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'Expenses', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('expenses-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required',
            'amount' => 'required|numeric',
            'payment_method' => ['nullable', 'string', 'max:100'],
            'expense_date' => 'required|date',
            'category' => 'required',
            'description' => 'nullable',
            'branch_id' => 'required|exists:branches,id',
        ]);
        Expense::create($validated);
        return redirect()->route('expenses.index')->with('status', 'Expense created successfully.');
    }

    public function show(Expense $expense)
    {
        return view('expenses.show', ['expense' => $expense]);
    }

    public function update(Request $request, Expense $expense)
    {
        $validated = $request->validate([
            'title' => 'sometimes|required',
            'amount' => 'sometimes|required|numeric',
            'payment_method' => ['nullable', 'string', 'max:100'],
            'expense_date' => 'sometimes|required|date',
            'category' => 'sometimes|required',
            'description' => 'nullable',
            'branch_id' => 'sometimes|required|exists:branches,id',
        ]);
        $expense->update($validated);
        return redirect()->route('expenses.index')->with('status', 'Expense updated successfully.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return redirect()->route('expenses.index')->with('status', 'Expense deleted.');
    }
}
