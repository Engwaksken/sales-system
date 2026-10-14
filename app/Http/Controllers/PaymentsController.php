<?php

namespace App\Http\Controllers;

use App\Models\Payment;
use App\Models\Customer;
use App\Models\Sale;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ReceiptPdfService;
use App\Support\DocumentBranding;
use App\Support\ExportsCsv;
use Illuminate\Http\Request;

class PaymentsController extends Controller
{
    use ExportsCsv;

    /** Stream the receipt PDF inline inside the receipt preview page. */
    public function receiptPdf(Payment $payment)
    {
        $payment->loadMissing(['customer', 'sale.customer', 'sale.branch']);
        $customer = $payment->customer ?? $payment->sale?->customer;

        $pdf = ReceiptPdfService::render(
            receiptNo: $payment->receipt_no,
            date: optional($payment->created_at)->format('d M Y H:i') ?? now()->format('d M Y H:i'),
            customerName: $customer?->name ?? 'Walk-in Customer',
            method: $payment->method,
            amount: (float) $payment->amount,
            reference: $payment->invoice_no,
        );

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="receipt-'.$payment->receipt_no.'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function receiptPreview(Payment $payment)
    {
        $payment->loadMissing(['customer', 'sale.customer', 'sale.branch']);

        return view('payments.receipt-preview', [
            'payment' => $payment,
            'branding' => DocumentBranding::get(),
        ]);
    }


    /**
     * Printer-friendly receipt for thermal and standard printers.
     */
    public function receiptPrint(Request $request, Payment $payment)
    {
        $payment->loadMissing(['customer', 'sale.customer', 'sale.branch', 'sale.orders']);

        $paper = strtolower((string) $request->query('paper', '80mm'));

        if (! in_array($paper, ['58mm', '80mm', 'a5', 'a4'], true)) {
            $paper = '80mm';
        }

        return view('payments.receipt-print', [
            'payment' => $payment,
            'branding' => DocumentBranding::get(),
            'paper' => $paper,
        ]);
    }

    public function receiptDownload(Payment $payment)
    {
        $payment->loadMissing(['customer', 'sale.customer', 'sale.branch']);
        $customer = $payment->customer ?? $payment->sale?->customer;

        $pdf = ReceiptPdfService::render(
            receiptNo: $payment->receipt_no,
            date: optional($payment->created_at)->format('d M Y H:i') ?? now()->format('d M Y H:i'),
            customerName: $customer?->name ?? 'Walk-in Customer',
            method: $payment->method,
            amount: (float) $payment->amount,
            reference: $payment->invoice_no,
        );

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="receipt-'.$payment->receipt_no.'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    public function index(Request $request)
    {
        $query = Payment::query()->with(['customer','supplier','sale','receivedBy']);
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['invoice_no', 'receipt_no', 'method']);
        $payments = $query->latest()->paginate(20);
        $stat1 = 'UGX '.number_format(Payment::sum('amount'), 0);
        $periodStats = $this->periodStats(Payment::class, 'created_at', 'amount');
        $customer_options = Customer::orderBy('name')->get();
        $supplier_options = Supplier::orderBy('name')->get();
        $sale_options = Sale::orderByDesc('sale_date')->orderByDesc('id')->get();
        $received_by_options = User::orderBy('name')->get();
        return view('payments.index', ['items' => $payments, 'customer_options' => $customer_options, 'supplier_options' => $supplier_options, 'sale_options' => $sale_options, 'received_by_options' => $received_by_options, 'stat1' => $stat1, 'periodStats' => $periodStats]);
    }

    public function exportCsv(Request $request)
    {
        $allColumns = ['transaction_type', 'invoice_no', 'receipt_no', 'customer_id', 'supplier_id', 'sale_id', 'amount', 'method', 'status', 'received_by', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = Payment::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['invoice_no', 'receipt_no', 'method']);
        $rows = $query->with(['customer', 'supplier', 'sale', 'receivedBy'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'customer_id') { $out[] = $row->customer?->name ?? $row->customer_id; continue; }
                if ($col === 'supplier_id') { $out[] = $row->supplier?->name ?? $row->supplier_id; continue; }
                if ($col === 'sale_id') { $out[] = $row->sale?->id ?? $row->sale_id; continue; }
                if ($col === 'received_by') { $out[] = $row->receivedBy?->name ?? $row->received_by; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('payments-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['transaction_type', 'invoice_no', 'receipt_no', 'customer_id', 'supplier_id', 'sale_id', 'amount', 'method', 'status', 'received_by', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = Payment::query();
        if ($request->filled('period') && $request->period !== 'all') {
            $query = $this->applyPeriod($query, $request->period, 'created_at');
        }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['invoice_no', 'receipt_no', 'method']);
        $rows = $query->with(['customer', 'supplier', 'sale', 'receivedBy'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'customer_id') { $out[] = $row->customer?->name ?? $row->customer_id; continue; }
                if ($col === 'supplier_id') { $out[] = $row->supplier?->name ?? $row->supplier_id; continue; }
                if ($col === 'sale_id') { $out[] = $row->sale?->id ?? $row->sale_id; continue; }
                if ($col === 'received_by') { $out[] = $row->receivedBy?->name ?? $row->received_by; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'Payments', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('payments-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaction_type' => 'required|in:sale,purchase',
            'invoice_no' => 'required',
            'receipt_no' => 'required',
            'customer_id' => 'nullable|exists:customers,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'sale_id' => 'nullable|exists:sales,id',
            'amount' => 'required|numeric',
            'method' => 'required',
            'status' => 'required|in:successful,failed',
            'received_by' => 'required|exists:users,id',
        ]);
        Payment::create($validated);
        return redirect()->route('payments.index')->with('status', 'Payment created successfully.');
    }

    public function show(Payment $payment)
    {
        return view('payments.show', ['payment' => $payment]);
    }

    public function update(Request $request, Payment $payment)
    {
        $validated = $request->validate([
            'transaction_type' => 'sometimes|required|in:sale,purchase',
            'invoice_no' => 'sometimes|required',
            'receipt_no' => 'sometimes|required',
            'customer_id' => 'nullable|exists:customers,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'sale_id' => 'nullable|exists:sales,id',
            'amount' => 'sometimes|required|numeric',
            'method' => 'sometimes|required',
            'status' => 'sometimes|required|in:successful,failed',
            'received_by' => 'sometimes|required|exists:users,id',
        ]);
        $payment->update($validated);
        return redirect()->route('payments.index')->with('status', 'Payment updated successfully.');
    }

    public function destroy(Payment $payment)
    {
        $payment->delete();
        return redirect()->route('payments.index')->with('status', 'Payment deleted.');
    }
}
