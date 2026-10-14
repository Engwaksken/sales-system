<?php

namespace App\Http\Controllers;

use App\Models\OrderPaymentReceipt;
use App\Models\Customer;
use App\Models\Order;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class OrderPaymentReceiptsController extends Controller
{
    use ExportsCsv;

    public function index(Request $request)
    {
        $query = OrderPaymentReceipt::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['order_number', 'receipt_number', 'customer_name', 'phone']);
        $order_payment_receipts = $query->latest()->paginate($this->perPage($request));
        $periodStats = $this->periodStats(OrderPaymentReceipt::class, 'created_at');
        $order_options = Order::orderBy('id')->get();
        $customer_options = Customer::orderBy('id')->get();
        return view('order_payment_receipts.index', ['items' => $order_payment_receipts, 'order_options' => $order_options, 'customer_options' => $customer_options, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['order_id', 'order_number', 'receipt_number', 'customer_id', 'customer_name', 'phone', 'email', 'payment_method', 'gateway_code', 'gateway_reference', 'gateway_status', 'amount_paid', 'currency', 'notes', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = OrderPaymentReceipt::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['order_number', 'receipt_number', 'customer_name', 'phone']);
        $rows = $query->with(['order', 'customer'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'order_id') { $out[] = $row->order?->id ?? $row->order_id; continue; }
                if ($col === 'customer_id') { $out[] = $row->customer?->name ?? $row->customer_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('order_payment_receipts-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['order_id', 'order_number', 'receipt_number', 'customer_id', 'customer_name', 'phone', 'email', 'payment_method', 'gateway_code', 'gateway_reference', 'gateway_status', 'amount_paid', 'currency', 'notes', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = OrderPaymentReceipt::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['order_number', 'receipt_number', 'customer_name', 'phone']);
        $rows = $query->with(['order', 'customer'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'order_id') { $out[] = $row->order?->id ?? $row->order_id; continue; }
                if ($col === 'customer_id') { $out[] = $row->customer?->name ?? $row->customer_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'OrderPaymentReceipts', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('order_payment_receipts-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'order_id' => 'required|exists:orders,id',
            'order_number' => 'required',
            'receipt_number' => 'required',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'required',
            'phone' => 'nullable',
            'email' => 'nullable',
            'payment_method' => 'required',
            'gateway_code' => 'nullable',
            'gateway_reference' => 'nullable',
            'gateway_status' => 'nullable',
            'amount_paid' => 'required|numeric',
            'currency' => 'required',
            'notes' => 'nullable',
        ]);
        OrderPaymentReceipt::create($validated);
        return redirect()->route('order_payment_receipts.index')->with('status', 'OrderPaymentReceipt created successfully.');
    }

    public function show(OrderPaymentReceipt $order_payment_receipt)
    {
        return view('order_payment_receipts.show', ['order_payment_receipt' => $order_payment_receipt]);
    }

    public function update(Request $request, OrderPaymentReceipt $order_payment_receipt)
    {
        $validated = $request->validate([
            'order_id' => 'sometimes|required|exists:orders,id',
            'order_number' => 'sometimes|required',
            'receipt_number' => 'sometimes|required',
            'customer_id' => 'nullable|exists:customers,id',
            'customer_name' => 'sometimes|required',
            'phone' => 'nullable',
            'email' => 'nullable',
            'payment_method' => 'sometimes|required',
            'gateway_code' => 'nullable',
            'gateway_reference' => 'nullable',
            'gateway_status' => 'nullable',
            'amount_paid' => 'sometimes|required|numeric',
            'currency' => 'sometimes|required',
            'notes' => 'nullable',
        ]);
        $order_payment_receipt->update($validated);
        return redirect()->route('order_payment_receipts.index')->with('status', 'OrderPaymentReceipt updated successfully.');
    }

    public function destroy(OrderPaymentReceipt $order_payment_receipt)
    {
        $order_payment_receipt->delete();
        return redirect()->route('order_payment_receipts.index')->with('status', 'OrderPaymentReceipt deleted.');
    }
}
