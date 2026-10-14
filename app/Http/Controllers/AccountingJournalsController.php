<?php

namespace App\Http\Controllers;

use App\Models\AccountingJournal;
use App\Models\Branch;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class AccountingJournalsController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = AccountingJournal::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'journal_date');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['journal_no', 'reference_no', 'reference_type', 'description']);
        $accounting_journals = $query->latest()->paginate($this->perPage($request));
        $periodStats = $this->periodStats(AccountingJournal::class, 'journal_date');
        $branch_options = Branch::orderBy('id')->get();
        return view('accounting_journals.index', ['items' => $accounting_journals, 'branch_options' => $branch_options, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['journal_no', 'journal_type', 'reference_no', 'reference_type', 'reference_id', 'journal_date', 'description', 'branch_id', 'created_by', 'is_posted', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = AccountingJournal::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'journal_date');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['journal_no', 'reference_no', 'reference_type', 'description']);
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
        return $this->csvResponse('accounting_journals-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['journal_no', 'journal_type', 'reference_no', 'reference_type', 'reference_id', 'journal_date', 'description', 'branch_id', 'created_by', 'is_posted', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = AccountingJournal::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'journal_date');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['journal_no', 'reference_no', 'reference_type', 'description']);
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
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'AccountingJournals', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('accounting_journals-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'journal_no' => 'required',
            'journal_type' => 'required|in:GJ,SJ,PJ,CRJ,CPJ,MJ',
            'reference_no' => 'nullable',
            'reference_type' => 'nullable',
            'reference_id' => 'nullable|numeric',
            'journal_date' => 'required|date',
            'description' => 'required',
            'branch_id' => 'nullable|exists:branches,id',
            'is_posted' => 'boolean',
        ]);
        AccountingJournal::create($validated);
        return redirect()->route('accounting_journals.index')->with('status', 'AccountingJournal created successfully.');
    }

    public function show(AccountingJournal $accounting_journal)
    {
        return view('accounting_journals.show', ['accounting_journal' => $accounting_journal]);
    }

    public function update(Request $request, AccountingJournal $accounting_journal)
    {
        $validated = $request->validate([
            'journal_no' => 'sometimes|required',
            'journal_type' => 'sometimes|required|in:GJ,SJ,PJ,CRJ,CPJ,MJ',
            'reference_no' => 'nullable',
            'reference_type' => 'nullable',
            'reference_id' => 'nullable|numeric',
            'journal_date' => 'sometimes|required|date',
            'description' => 'sometimes|required',
            'branch_id' => 'nullable|exists:branches,id',
            'is_posted' => 'boolean',
        ]);
        $accounting_journal->update($validated);
        return redirect()->route('accounting_journals.index')->with('status', 'AccountingJournal updated successfully.');
    }

    public function destroy(AccountingJournal $accounting_journal)
    {
        $accounting_journal->delete();
        return redirect()->route('accounting_journals.index')->with('status', 'AccountingJournal deleted.');
    }
}
