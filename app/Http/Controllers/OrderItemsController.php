<?php

namespace App\Http\Controllers;

use App\Models\OrderItem;
use App\Models\Order;
use App\Models\Product;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class OrderItemsController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = OrderItem::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['product_name', 'sku']);
        $order_items = $query->latest()->paginate($this->perPage($request));
        $periodStats = $this->periodStats(OrderItem::class, 'created_at');
        $order_options = Order::orderBy('id')->get();
        $product_options = Product::orderBy('id')->get();
        return view('order_items.index', ['items' => $order_items, 'order_options' => $order_options, 'product_options' => $product_options, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['order_id', 'product_id', 'product_name', 'sku', 'quantity', 'price', 'subtotal', 'cost_price', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = OrderItem::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['product_name', 'sku']);
        $rows = $query->with(['order', 'product'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'order_id') { $out[] = $row->order?->id ?? $row->order_id; continue; }
                if ($col === 'product_id') { $out[] = $row->product?->name ?? $row->product_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('order_items-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['order_id', 'product_id', 'product_name', 'sku', 'quantity', 'price', 'subtotal', 'cost_price', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = OrderItem::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['product_name', 'sku']);
        $rows = $query->with(['order', 'product'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'order_id') { $out[] = $row->order?->id ?? $row->order_id; continue; }
                if ($col === 'product_id') { $out[] = $row->product?->name ?? $row->product_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'OrderItems', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('order_items-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'product_id' => 'required|exists:products,id',
            'product_name' => 'required',
            'sku' => 'nullable',
            'quantity' => 'required|numeric',
            'price' => 'required|numeric',
            'subtotal' => 'required|numeric',
            'cost_price' => 'nullable|numeric',
        ]);
        OrderItem::create($validated);
        return redirect()->route('order_items.index')->with('status', 'OrderItem created successfully.');
    }

    public function show(OrderItem $order_item)
    {
        return view('order_items.show', ['order_item' => $order_item]);
    }

    public function update(Request $request, OrderItem $order_item)
    {
        $validated = $request->validate([
            'order_id' => 'sometimes|required|exists:orders,id',
            'product_id' => 'sometimes|required|exists:products,id',
            'product_name' => 'sometimes|required',
            'sku' => 'nullable',
            'quantity' => 'sometimes|required|numeric',
            'price' => 'sometimes|required|numeric',
            'subtotal' => 'sometimes|required|numeric',
            'cost_price' => 'nullable|numeric',
        ]);
        $order_item->update($validated);
        return redirect()->route('order_items.index')->with('status', 'OrderItem updated successfully.');
    }

    public function destroy(OrderItem $order_item)
    {
        $order_item->delete();
        return redirect()->route('order_items.index')->with('status', 'OrderItem deleted.');
    }
}
