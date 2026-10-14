<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class BranchesController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = Branch::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['name', 'location', 'contact', 'branch_email']);
        $branches = $query->latest()->paginate($this->perPage($request));
        $periodStats = $this->periodStats(Branch::class, 'created_at');
        return view('branches.index', ['items' => $branches, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['name', 'location', 'contact', 'branch_email', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = Branch::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['name', 'location', 'contact', 'branch_email']);
        $rows = $query->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('branches-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['name', 'location', 'contact', 'branch_email', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = Branch::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['name', 'location', 'contact', 'branch_email']);
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
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'Branches', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('branches-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'nullable',
            'location' => 'nullable',
            'contact' => 'nullable',
            'branch_email' => 'nullable',
        ]);
        Branch::create($validated);
        return redirect()->route('branches.index')->with('status', 'Branch created successfully.');
    }

    public function show(Branch $branch)
    {
        return view('branches.show', ['branch' => $branch]);
    }

    public function update(Request $request, Branch $branch)
    {
        $validated = $request->validate([
            'name' => 'nullable',
            'location' => 'nullable',
            'contact' => 'nullable',
            'branch_email' => 'nullable',
        ]);
        $branch->update($validated);
        return redirect()->route('branches.index')->with('status', 'Branch updated successfully.');
    }

    public function destroy(Branch $branch)
    {
        $branch->delete();
        return redirect()->route('branches.index')->with('status', 'Branch deleted.');
    }
}
