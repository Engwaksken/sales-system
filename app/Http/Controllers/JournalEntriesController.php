<?php

namespace App\Http\Controllers;

use App\Models\JournalEntry;
use App\Models\AccountingJournal;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class JournalEntriesController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = JournalEntry::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['account_code', 'description']);
        $journal_entries = $query->latest()->paginate($this->perPage($request));
        $periodStats = $this->periodStats(JournalEntry::class, 'created_at');
        $journal_options = AccountingJournal::orderBy('id')->get();
        return view('journal_entries.index', ['items' => $journal_entries, 'journal_options' => $journal_options, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['journal_id', 'account_code', 'description', 'debit', 'credit', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = JournalEntry::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['account_code', 'description']);
        $rows = $query->with(['journal'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'journal_id') { $out[] = $row->journal?->journal_no ?? $row->journal_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('journal_entries-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['journal_id', 'account_code', 'description', 'debit', 'credit', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = JournalEntry::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['account_code', 'description']);
        $rows = $query->with(['journal'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'journal_id') { $out[] = $row->journal?->journal_no ?? $row->journal_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'JournalEntries', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('journal_entries-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'journal_id' => 'required|exists:accounting_journals,id',
            'account_code' => 'required',
            'description' => 'nullable',
            'debit' => 'required|numeric',
            'credit' => 'required|numeric',
        ]);
        JournalEntry::create($validated);
        return redirect()->route('journal_entries.index')->with('status', 'JournalEntry created successfully.');
    }

    public function show(JournalEntry $journal_entry)
    {
        return view('journal_entries.show', ['journal_entry' => $journal_entry]);
    }

    public function update(Request $request, JournalEntry $journal_entry)
    {
        $validated = $request->validate([
            'journal_id' => 'sometimes|required|exists:accounting_journals,id',
            'account_code' => 'sometimes|required',
            'description' => 'nullable',
            'debit' => 'sometimes|required|numeric',
            'credit' => 'sometimes|required|numeric',
        ]);
        $journal_entry->update($validated);
        return redirect()->route('journal_entries.index')->with('status', 'JournalEntry updated successfully.');
    }

    public function destroy(JournalEntry $journal_entry)
    {
        $journal_entry->delete();
        return redirect()->route('journal_entries.index')->with('status', 'JournalEntry deleted.');
    }
}
