<?php

namespace App\Http\Controllers;

use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Sale;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class SaleItemsController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = SaleItem::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $sale_items = $query->latest()->paginate($this->perPage($request));
        $periodStats = $this->periodStats(SaleItem::class, 'created_at');
        $sale_options = Sale::orderBy('id')->get();
        $product_options = Product::orderBy('id')->get();
        return view('sale_items.index', ['items' => $sale_items, 'sale_options' => $sale_options, 'product_options' => $product_options, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['sale_id', 'product_id', 'quantity', 'price', 'vat_amount', 'total', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = SaleItem::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $rows = $query->with(['sale', 'product'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'sale_id') { $out[] = $row->sale?->id ?? $row->sale_id; continue; }
                if ($col === 'product_id') { $out[] = $row->product?->name ?? $row->product_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('sale_items-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['sale_id', 'product_id', 'quantity', 'price', 'vat_amount', 'total', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = SaleItem::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $rows = $query->with(['sale', 'product'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'sale_id') { $out[] = $row->sale?->id ?? $row->sale_id; continue; }
                if ($col === 'product_id') { $out[] = $row->product?->name ?? $row->product_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'SaleItems', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('sale_items-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => 'nullable|exists:sales,id',
            'product_id' => 'nullable|exists:products,id',
            'quantity' => 'nullable|numeric',
            'price' => 'nullable|numeric',
            'vat_amount' => 'nullable|numeric',
            'total' => 'nullable|numeric',
        ]);
        SaleItem::create($validated);
        return redirect()->route('sale_items.index')->with('status', 'SaleItem created successfully.');
    }

    public function show(SaleItem $sale_item)
    {
        return view('sale_items.show', ['sale_item' => $sale_item]);
    }

    public function update(Request $request, SaleItem $sale_item)
    {
        $validated = $request->validate([
            'sale_id' => 'nullable|exists:sales,id',
            'product_id' => 'nullable|exists:products,id',
            'quantity' => 'nullable|numeric',
            'price' => 'nullable|numeric',
            'vat_amount' => 'nullable|numeric',
            'total' => 'nullable|numeric',
        ]);
        $sale_item->update($validated);
        return redirect()->route('sale_items.index')->with('status', 'SaleItem updated successfully.');
    }

    public function destroy(SaleItem $sale_item)
    {
        $sale_item->delete();
        return redirect()->route('sale_items.index')->with('status', 'SaleItem deleted.');
    }
}
