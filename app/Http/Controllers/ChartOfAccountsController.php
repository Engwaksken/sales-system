<?php

namespace App\Http\Controllers;

use App\Models\ChartOfAccount;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class ChartOfAccountsController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = ChartOfAccount::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['account_code', 'account_name', 'account_category', 'parent_code']);
        $chart_of_accounts = $query->latest()->paginate($this->perPage($request));
        $stat1 = ChartOfAccount::where('is_active', true)->count();
        $periodStats = $this->periodStats(ChartOfAccount::class, 'created_at');
        return view('chart_of_accounts.index', ['items' => $chart_of_accounts, 'stat1' => $stat1, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['account_code', 'account_name', 'account_type', 'account_category', 'normal_balance', 'is_active', 'parent_code', 'description', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = ChartOfAccount::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['account_code', 'account_name', 'account_category', 'parent_code']);
        $rows = $query->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('chart_of_accounts-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['account_code', 'account_name', 'account_type', 'account_category', 'normal_balance', 'is_active', 'parent_code', 'description', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = ChartOfAccount::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['account_code', 'account_name', 'account_category', 'parent_code']);
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
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'ChartOfAccounts', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('chart_of_accounts-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'account_code' => 'required',
            'account_name' => 'required',
            'account_type' => 'required|in:Asset,Liability,Equity,Revenue,COGS,Expense',
            'account_category' => 'required',
            'normal_balance' => 'required|in:Debit,Credit',
            'is_active' => 'boolean',
            'parent_code' => 'nullable',
            'description' => 'nullable',
        ]);
        ChartOfAccount::create($validated);
        return redirect()->route('chart_of_accounts.index')->with('status', 'ChartOfAccount created successfully.');
    }

    public function show(ChartOfAccount $chart_of_account)
    {
        return view('chart_of_accounts.show', ['chart_of_account' => $chart_of_account]);
    }

    public function update(Request $request, ChartOfAccount $chart_of_account)
    {
        $validated = $request->validate([
            'account_code' => 'sometimes|required',
            'account_name' => 'sometimes|required',
            'account_type' => 'sometimes|required|in:Asset,Liability,Equity,Revenue,COGS,Expense',
            'account_category' => 'sometimes|required',
            'normal_balance' => 'sometimes|required|in:Debit,Credit',
            'is_active' => 'boolean',
            'parent_code' => 'nullable',
            'description' => 'nullable',
        ]);
        $chart_of_account->update($validated);
        return redirect()->route('chart_of_accounts.index')->with('status', 'ChartOfAccount updated successfully.');
    }

    public function destroy(ChartOfAccount $chart_of_account)
    {
        $chart_of_account->delete();
        return redirect()->route('chart_of_accounts.index')->with('status', 'ChartOfAccount deleted.');
    }
}
