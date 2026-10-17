<?php

namespace App\Http\Controllers;

use App\Models\Stock;
use App\Models\Branch;
use App\Models\Product;
use App\Models\Supplier;
use Dompdf\Dompdf;
use Dompdf\Options;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class StockController extends Controller
{
    use ExportsCsv;

    /** Ported from stock_pdf.php / export_stock_pdf.php. */
    public function reportPdf()
    {
        $rows = Stock::with(['product', 'supplier'])->latest()->get();

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('stock.report-pdf', [
            'rows' => $rows,
            'generatedBy' => Auth::user()?->name ?? 'System',
        ])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();

        return $dompdf->stream('stock-report-'.now()->format('Ymd').'.pdf');
    }

    public function index(Request $request)
    {
        $query = Stock::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $stock = $query->latest()->paginate($this->perPage($request));
        $stat1 = Stock::where('quantity', '<=', 5)->count();
        $stat2 = 'UGX '.number_format(Stock::all()->sum(fn ($s) => $s->quantity * $s->cost_price), 0);
        $periodStats = $this->periodStats(Stock::class, 'created_at', 'selling_price');
        $supplier_options = Supplier::orderBy('id')->get();
        $product_options = Product::orderBy('id')->get();
        $branch_options = Branch::orderBy('id')->get();
        return view('stock.index', ['items' => $stock, 'supplier_options' => $supplier_options, 'product_options' => $product_options, 'branch_options' => $branch_options, 'stat1' => $stat1, 'stat2' => $stat2, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['supplier_id', 'product_id', 'branch_id', 'total_stock', 'quantity', 'unit_type', 'cost_price', 'expiry_date', 'selling_price', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = Stock::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $rows = $query->with(['supplier', 'product', 'branch'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'supplier_id') { $out[] = $row->supplier?->name ?? $row->supplier_id; continue; }
                if ($col === 'product_id') { $out[] = $row->product?->name ?? $row->product_id; continue; }
                if ($col === 'branch_id') { $out[] = $row->branch?->name ?? $row->branch_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('stock-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['supplier_id', 'product_id', 'branch_id', 'total_stock', 'quantity', 'unit_type', 'cost_price', 'expiry_date', 'selling_price', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = Stock::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $rows = $query->with(['supplier', 'product', 'branch'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'supplier_id') { $out[] = $row->supplier?->name ?? $row->supplier_id; continue; }
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
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'Stock', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('stock-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'product_id' => 'required|exists:products,id',
            // New stock form sends several branches; a single branch_id is still accepted.
            'branch_ids' => 'required_without:branch_id|array|min:1',
            'branch_ids.*' => 'integer|distinct|exists:branches,id',
            'branch_id' => 'nullable|exists:branches,id',
            'total_stock' => 'required|numeric',
            'quantity' => 'required|numeric',
            'unit_type' => 'nullable',
            'cost_price' => 'required|numeric',
            'expiry_date' => 'nullable|date',
            'selling_price' => 'required|numeric',
        ], ['branch_ids.required_without' => 'Select at least one branch.']);

        $branchIds = array_map('intval', $validated['branch_ids'] ?? [$validated['branch_id']]);
        unset($validated['branch_ids'], $validated['branch_id']);
        $added = $toppedUp = 0;

        DB::transaction(function () use ($validated, $branchIds, &$added, &$toppedUp): void {
            foreach ($branchIds as $branchId) {
                // One row per product, branch and supplier: sales read the first row, so top it up.
                $stock = Stock::query()
                    ->where('product_id', $validated['product_id'])
                    ->where('branch_id', $branchId)
                    ->when($validated['supplier_id'] ?? null, fn ($q, $id) => $q->where('supplier_id', $id), fn ($q) => $q->whereNull('supplier_id'))
                    ->lockForUpdate()
                    ->first();

                if ($stock) {
                    $stock->fill(array_filter([
                        'unit_type' => $validated['unit_type'] ?? null,
                        'cost_price' => $validated['cost_price'],
                        'selling_price' => $validated['selling_price'],
                        'expiry_date' => $validated['expiry_date'] ?? null,
                    ], fn ($value) => $value !== null));
                    $stock->quantity = (float) $stock->quantity + (float) $validated['quantity'];
                    $stock->total_stock = (float) $stock->total_stock + (float) $validated['total_stock'];
                    $stock->save();
                    $toppedUp++;
                } else {
                    Stock::create($validated + ['branch_id' => $branchId]);
                    $added++;
                }
            }
        });

        $message = count($branchIds) === 1
            ? ($toppedUp ? 'Stock topped up successfully.' : 'Stock created successfully.')
            : 'Stock saved to '.count($branchIds)." branches ({$added} new, {$toppedUp} topped up).";

        return redirect()->route('stock.index')->with('status', $message);
    }

    public function show(Stock $stock)
    {
        return view('stock.show', ['stock' => $stock]);
    }

    public function update(Request $request, Stock $stock)
    {
        $validated = $request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'product_id' => 'sometimes|required|exists:products,id',
            'branch_id' => 'sometimes|required|exists:branches,id',
            'total_stock' => 'sometimes|required|numeric',
            'quantity' => 'sometimes|required|numeric',
            'unit_type' => 'nullable',
            'cost_price' => 'sometimes|required|numeric',
            'expiry_date' => 'nullable|date',
            'selling_price' => 'sometimes|required|numeric',
        ]);
        $stock->update($validated);
        return redirect()->route('stock.index')->with('status', 'Stock updated successfully.');
    }

    public function destroy(Stock $stock)
    {
        $stock->delete();
        return redirect()->route('stock.index')->with('status', 'Stock deleted.');
    }
}
