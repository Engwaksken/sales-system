<?php

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\DeliveryZone;
use App\Models\PaymentGateway;
use App\Models\Sale;
use App\Models\User;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class OrdersController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = Order::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['order_id', 'customer_name', 'phone', 'email']);
        $orders = $query->latest()->paginate($this->perPage($request));
        $stat1 = 'UGX '.number_format(Order::sum('total_amount'), 0);
        $stat2 = Order::where('status', 'pending')->count();
        $periodStats = $this->periodStats(Order::class, 'created_at', 'total_amount');
        $sale_options = Sale::orderBy('id')->get();
        $customer_options = Customer::orderBy('id')->get();
        $delivery_zone_options = DeliveryZone::orderBy('id')->get();
        $branch_options = Branch::orderBy('id')->get();
        $gateway_options = PaymentGateway::orderBy('id')->get();
        $created_by_user_options = User::orderBy('id')->get();
        return view('orders.index', ['items' => $orders, 'sale_options' => $sale_options, 'customer_options' => $customer_options, 'delivery_zone_options' => $delivery_zone_options, 'branch_options' => $branch_options, 'gateway_options' => $gateway_options, 'created_by_user_options' => $created_by_user_options, 'stat1' => $stat1, 'stat2' => $stat2, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['sale_id', 'order_id', 'customer_id', 'customer_name', 'phone', 'email', 'delivery_location', 'delivery_zone_id', 'branch_id', 'payment_method', 'gateway_id', 'gateway_code', 'gateway_reference', 'gateway_status', 'gateway_message', 'mm_phone', 'mm_network', 'payment_status', 'total_amount', 'discount', 'amount_paid', 'balance_amount', 'status', 'orders_count', 'notes', 'is_guest_order', 'created_by_user_id', 'paid_at', 'delivery_zone', 'delivery_fee', 'subtotal', 'order_number', 'address', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = Order::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['order_id', 'customer_name', 'phone', 'email']);
        $rows = $query->with(['sale', 'customer', 'delivery_zone', 'branch', 'gateway', 'created_by_user'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'sale_id') { $out[] = $row->sale?->id ?? $row->sale_id; continue; }
                if ($col === 'customer_id') { $out[] = $row->customer?->name ?? $row->customer_id; continue; }
                if ($col === 'delivery_zone_id') { $out[] = $row->delivery_zone?->zone_name ?? $row->delivery_zone_id; continue; }
                if ($col === 'branch_id') { $out[] = $row->branch?->name ?? $row->branch_id; continue; }
                if ($col === 'gateway_id') { $out[] = $row->gateway?->display_name ?? $row->gateway_id; continue; }
                if ($col === 'created_by_user_id') { $out[] = $row->created_by_user?->name ?? $row->created_by_user_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('orders-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['sale_id', 'order_id', 'customer_id', 'customer_name', 'phone', 'email', 'delivery_location', 'delivery_zone_id', 'branch_id', 'payment_method', 'gateway_id', 'gateway_code', 'gateway_reference', 'gateway_status', 'gateway_message', 'mm_phone', 'mm_network', 'payment_status', 'total_amount', 'discount', 'amount_paid', 'balance_amount', 'status', 'orders_count', 'notes', 'is_guest_order', 'created_by_user_id', 'paid_at', 'delivery_zone', 'delivery_fee', 'subtotal', 'order_number', 'address', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = Order::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['order_id', 'customer_name', 'phone', 'email']);
        $rows = $query->with(['sale', 'customer', 'delivery_zone', 'branch', 'gateway', 'created_by_user'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'sale_id') { $out[] = $row->sale?->id ?? $row->sale_id; continue; }
                if ($col === 'customer_id') { $out[] = $row->customer?->name ?? $row->customer_id; continue; }
                if ($col === 'delivery_zone_id') { $out[] = $row->delivery_zone?->zone_name ?? $row->delivery_zone_id; continue; }
                if ($col === 'branch_id') { $out[] = $row->branch?->name ?? $row->branch_id; continue; }
                if ($col === 'gateway_id') { $out[] = $row->gateway?->display_name ?? $row->gateway_id; continue; }
                if ($col === 'created_by_user_id') { $out[] = $row->created_by_user?->name ?? $row->created_by_user_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'Orders', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('orders-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'sale_id' => 'nullable|exists:sales,id',
            'order_id' => 'required',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required',
            'phone' => 'required',
            'email' => 'nullable',
            'delivery_location' => 'required',
            'delivery_zone_id' => 'nullable|exists:delivery_zones,id',
            'branch_id' => 'required|exists:branches,id',
            'payment_method' => 'required',
            'gateway_id' => 'nullable|exists:payment_gateways,id',
            'gateway_code' => 'nullable',
            'gateway_reference' => 'nullable',
            'gateway_status' => 'nullable',
            'gateway_message' => 'nullable',
            'mm_phone' => 'nullable',
            'mm_network' => 'nullable',
            'payment_status' => 'required',
            'total_amount' => 'required|numeric',
            'discount' => 'required|numeric',
            'amount_paid' => 'required|numeric',
            'balance_amount' => 'required|numeric',
            'status' => 'required',
            'orders_count' => 'nullable|numeric',
            'notes' => 'nullable',
            'is_guest_order' => 'boolean',
            'created_by_user_id' => 'nullable|exists:users,id',
            'paid_at' => 'nullable',
            'delivery_zone' => 'nullable',
            'delivery_fee' => 'required|numeric',
            'subtotal' => 'required|numeric',
            'order_number' => 'nullable',
            'address' => 'nullable',
        ]);
        Order::create($validated);
        return redirect()->route('orders.index')->with('status', 'Order created successfully.');
    }

    public function show(Order $order)
    {
        return view('orders.show', ['order' => $order]);
    }

    public function update(Request $request, Order $order)
    {
        $validated = $request->validate([
            'sale_id' => 'nullable|exists:sales,id',
            'order_id' => 'sometimes|required',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'sometimes|required',
            'phone' => 'sometimes|required',
            'email' => 'nullable',
            'delivery_location' => 'sometimes|required',
            'delivery_zone_id' => 'nullable|exists:delivery_zones,id',
            'branch_id' => 'sometimes|required|exists:branches,id',
            'payment_method' => 'sometimes|required',
            'gateway_id' => 'nullable|exists:payment_gateways,id',
            'gateway_code' => 'nullable',
            'gateway_reference' => 'nullable',
            'gateway_status' => 'nullable',
            'gateway_message' => 'nullable',
            'mm_phone' => 'nullable',
            'mm_network' => 'nullable',
            'payment_status' => 'sometimes|required',
            'total_amount' => 'sometimes|required|numeric',
            'discount' => 'sometimes|required|numeric',
            'amount_paid' => 'sometimes|required|numeric',
            'balance_amount' => 'sometimes|required|numeric',
            'status' => 'sometimes|required',
            'orders_count' => 'nullable|numeric',
            'notes' => 'nullable',
            'is_guest_order' => 'boolean',
            'created_by_user_id' => 'nullable|exists:users,id',
            'paid_at' => 'nullable',
            'delivery_zone' => 'nullable',
            'delivery_fee' => 'sometimes|required|numeric',
            'subtotal' => 'sometimes|required|numeric',
            'order_number' => 'nullable',
            'address' => 'nullable',
        ]);
        $order->update($validated);
        return redirect()->route('orders.index')->with('status', 'Order updated successfully.');
    }

    public function destroy(Order $order)
    {
        $order->delete();
        return redirect()->route('orders.index')->with('status', 'Order deleted.');
    }
}
