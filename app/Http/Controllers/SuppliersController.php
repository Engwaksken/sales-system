<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use App\Models\Branch;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class SuppliersController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = Supplier::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['name', 'contact_name', 'email', 'phone']);
        $suppliers = $query->latest()->paginate($this->perPage($request));
        $stat1 = Supplier::where('status', 'active')->count();
        $periodStats = $this->periodStats(Supplier::class, 'created_at');
        $branch_options = Branch::orderBy('id')->get();
        return view('suppliers.index', ['items' => $suppliers, 'branch_options' => $branch_options, 'stat1' => $stat1, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['branch_id', 'name', 'contact_name', 'email', 'phone', 'address', 'status', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = Supplier::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['name', 'contact_name', 'email', 'phone']);
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
        return $this->csvResponse('suppliers-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['branch_id', 'name', 'contact_name', 'email', 'phone', 'address', 'status', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = Supplier::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['name', 'contact_name', 'email', 'phone']);
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
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'Suppliers', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('suppliers-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'name' => 'required',
            'contact_name' => 'required',
            'email' => 'nullable',
            'phone' => 'nullable',
            'address' => 'nullable',
            'status' => 'required|in:active,inactive',
        ]);
        Supplier::create($validated);
        return redirect()->route('suppliers.index')->with('status', 'Supplier created successfully.');
    }

    public function show(Supplier $supplier)
    {
        return view('suppliers.show', ['supplier' => $supplier]);
    }

    public function update(Request $request, Supplier $supplier)
    {
        $validated = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'name' => 'sometimes|required',
            'contact_name' => 'sometimes|required',
            'email' => 'nullable',
            'phone' => 'nullable',
            'address' => 'nullable',
            'status' => 'sometimes|required|in:active,inactive',
        ]);
        $supplier->update($validated);
        return redirect()->route('suppliers.index')->with('status', 'Supplier updated successfully.');
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return redirect()->route('suppliers.index')->with('status', 'Supplier deleted.');
    }
}
