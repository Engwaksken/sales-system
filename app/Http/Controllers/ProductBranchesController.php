<?php

namespace App\Http\Controllers;

use App\Models\ProductBranch;
use App\Models\Branch;
use App\Models\Product;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class ProductBranchesController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = ProductBranch::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $product_branches = $query->latest()->paginate($this->perPage($request));
        $periodStats = $this->periodStats(ProductBranch::class, 'created_at');
        $product_options = Product::orderBy('id')->get();
        $branch_options = Branch::orderBy('id')->get();
        return view('product_branches.index', ['items' => $product_branches, 'product_options' => $product_options, 'branch_options' => $branch_options, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['product_id', 'branch_id', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = ProductBranch::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $rows = $query->with(['product', 'branch'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'product_id') { $out[] = $row->product?->name ?? $row->product_id; continue; }
                if ($col === 'branch_id') { $out[] = $row->branch?->name ?? $row->branch_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('product_branches-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['product_id', 'branch_id', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = ProductBranch::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $rows = $query->with(['product', 'branch'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'product_id') { $out[] = $row->product?->name ?? $row->product_id; continue; }
                if ($col === 'branch_id') { $out[] = $row->branch?->name ?? $row->branch_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'ProductBranches', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('product_branches-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'branch_id' => 'required|exists:branches,id',
        ]);
        ProductBranch::create($validated);
        return redirect()->route('product_branches.index')->with('status', 'ProductBranch created successfully.');
    }

    public function show(ProductBranch $product_branch)
    {
        return view('product_branches.show', ['product_branch' => $product_branch]);
    }

    public function update(Request $request, ProductBranch $product_branch)
    {
        $validated = $request->validate([
            'product_id' => 'sometimes|required|exists:products,id',
            'branch_id' => 'sometimes|required|exists:branches,id',
        ]);
        $product_branch->update($validated);
        return redirect()->route('product_branches.index')->with('status', 'ProductBranch updated successfully.');
    }

    public function destroy(ProductBranch $product_branch)
    {
        $product_branch->delete();
        return redirect()->route('product_branches.index')->with('status', 'ProductBranch deleted.');
    }
}
