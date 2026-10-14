<?php

namespace App\Http\Controllers;

use App\Models\Sale;
use App\Models\OrderLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Stock;
use App\Models\Payment;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Support\ExportsCsv;
use App\Support\DocumentBranding;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    use ExportsCsv;

    /** Stream the invoice PDF inline inside the application's preview page. */
    public function invoicePdf(Sale $sale)
    {
        $sale->load(['customer', 'sale_items.product', 'branch', 'payments', 'orders']);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('sales.invoice-pdf', [
            'sale' => $sale,
            'branding' => DocumentBranding::get(),
        ])->render());
        $dompdf->setPaper('A4');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="invoice-'.$sale->invoice_no.'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    /** Professional in-app invoice preview with explicit download action. */
    public function invoicePreview(Sale $sale)
    {
        $sale->load(['customer', 'sale_items.product', 'branch', 'payments', 'orders']);

        return view('sales.invoice-preview', [
            'sale' => $sale,
            'branding' => DocumentBranding::get(),
        ]);
    }

    /** Download the final invoice only after the user has had a chance to preview it. */
    public function invoiceDownload(Sale $sale)
    {
        $sale->load(['customer', 'sale_items.product', 'branch', 'payments', 'orders']);

        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('sales.invoice-pdf', [
            'sale' => $sale,
            'branding' => DocumentBranding::get(),
        ])->render());
        $dompdf->setPaper('A4');
        $dompdf->render();

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="invoice-'.$sale->invoice_no.'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }


public function index(Request $request)
{
    $query = Sale::query()->with([
        'orders',
        'customer',
        'branch',
        'supplier',
        'sale_items.product',
        'payments',
    ]);

    $query = $this->applyRequestedDateFilter($query, $request, 'sale_date');

    if ($request->filled('branch_id')) {
        $query->where('branch_id', $request->integer('branch_id'));
    }

    if ($request->filled('supplier_id')) {
        $query->where('supplier_id', $request->integer('supplier_id'));
    }

    $query = $this->applySearch(
        $query,
        (string) $request->query('q', ''),
        ['invoice_no', 'payment_method']
    );

    $filteredStat = (clone $query)->count();

    $sales = $query
        ->latest()
        ->paginate(20)
        ->withQueryString();

    $stockData = Stock::with('product')
        ->where('quantity', '>', 0)
        ->get()
        ->map(fn ($s) => [
            'product_id' => $s->product_id,
            'branch_id' => $s->branch_id,
            'name' => $s->product->name ?? 'Unknown',
            'sku' => $s->product->sku ?? '',
            'price' => (float) $s->selling_price,
            'available' => (int) $s->quantity,
        ])->values();

    $stat1 = 'UGX '.number_format(Sale::sum('total_amount'), 0);
    $periodStats = $this->periodStats(Sale::class, 'sale_date', 'total_amount');
    $customer_options = Customer::orderBy('name')->get();
    $supplier_options = Supplier::orderBy('name')->get();
    $user_options = User::orderBy('name')->get();
    $branch_options = Branch::orderBy('name')->get();

    return view('sales.index', [
        'items' => $sales,
        'customer_options' => $customer_options,
        'supplier_options' => $supplier_options,
        'user_options' => $user_options,
        'branch_options' => $branch_options,
        'stat1' => $stat1,
        'periodStats' => $periodStats,
        'stockData' => $stockData,
        'branchStat' => Branch::count(),
        'supplierStat' => Supplier::count(),
        'filteredStat' => $filteredStat,
        'totalStat' => Sale::count(),
        'filteredStatLabel' => 'Filtered Sales',
        'totalStatLabel' => 'All Sales',
    ]);
}

    public function exportCsv(Request $request)
    {
        $allColumns = ['transaction_type', 'customer_id', 'supplier_id', 'user_id', 'invoice_no', 'total_amount', 'discount', 'vat', 'payment_method', 'sale_date', 'payment_status', 'partial_amount', 'balance_amount', 'branch_id', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = Sale::query();
        $query = $this->applyRequestedDateFilter($query, $request, 'sale_date');
        if ($request->filled('branch_id')) { $query->where('branch_id', $request->integer('branch_id')); }
        if ($request->filled('supplier_id')) { $query->where('supplier_id', $request->integer('supplier_id')); }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['invoice_no', 'payment_method']);
        $rows = $query->with(['customer', 'supplier', 'user', 'branch'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'customer_id') { $out[] = $row->customer?->name ?? $row->customer_id; continue; }
                if ($col === 'supplier_id') { $out[] = $row->supplier?->name ?? $row->supplier_id; continue; }
                if ($col === 'user_id') { $out[] = $row->user?->name ?? $row->user_id; continue; }
                if ($col === 'branch_id') { $out[] = $row->branch?->name ?? $row->branch_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        return $this->csvResponse('sales-'.now()->format('Ymd').'.csv', $headers, $data);
    }

    public function exportPdf(Request $request)
    {
        $allColumns = ['transaction_type', 'customer_id', 'supplier_id', 'user_id', 'invoice_no', 'total_amount', 'discount', 'vat', 'payment_method', 'sale_date', 'payment_status', 'partial_amount', 'balance_amount', 'branch_id', 'created_by', 'updated_by', 'created_at', 'updated_at'];
        $columns = $request->query('columns', $allColumns);
        $columns = array_values(array_intersect($allColumns, $columns));
        if (empty($columns)) { $columns = $allColumns; }
        $query = Sale::query();
        $query = $this->applyRequestedDateFilter($query, $request, 'sale_date');
        if ($request->filled('branch_id')) { $query->where('branch_id', $request->integer('branch_id')); }
        if ($request->filled('supplier_id')) { $query->where('supplier_id', $request->integer('supplier_id')); }
        $query = $this->applySearch($query, (string) $request->query('q', ''), ['invoice_no', 'payment_method']);
        $rows = $query->with(['customer', 'supplier', 'user', 'branch'])->get();
        $data = $rows->map(function ($row) use ($columns) {
            $out = [];
            foreach ($columns as $col) {
                if ($col === 'customer_id') { $out[] = $row->customer?->name ?? $row->customer_id; continue; }
                if ($col === 'supplier_id') { $out[] = $row->supplier?->name ?? $row->supplier_id; continue; }
                if ($col === 'user_id') { $out[] = $row->user?->name ?? $row->user_id; continue; }
                if ($col === 'branch_id') { $out[] = $row->branch?->name ?? $row->branch_id; continue; }
                $out[] = $row->{$col};
            }
            return $out;
        });
        $headers = array_map(fn ($c) => str($c)->replace('_id', '')->replace('_', ' ')->title()->toString(), $columns);
        $options = new \Dompdf\Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml(view('partials.export-pdf', ['title' => 'Sales', 'headers' => $headers, 'rows' => $data, 'generatedBy' => auth()->user()?->name ?? 'System'])->render());
        $dompdf->setPaper('A4', 'landscape');
        $dompdf->render();
        return $dompdf->stream('sales-'.now()->format('Ymd').'.pdf');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'transaction_type' => 'required|in:sale,purchase',
            'customer_id' => 'nullable|exists:customers,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'user_id' => 'nullable|exists:users,id',
            'invoice_no' => 'nullable|string|max:100',
            'total_amount' => 'required|numeric|min:0',
            'discount' => 'required|numeric|min:0',
            'vat' => 'required|numeric|min:0',
            'payment_method' => 'required|string|max:50',
            'sale_date' => 'required|date',
            'payment_status' => 'required|in:pending,partial,paid',
            'partial_amount' => 'nullable|numeric|min:0',
            'balance_amount' => 'nullable|numeric|min:0',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $sale = DB::transaction(function () use ($validated) {
            $total = (float) $validated['total_amount'];
            $status = $validated['payment_status'];

            $paidNow = match ($status) {
                'paid' => $total,
                'partial' => min(
                    max((float) ($validated['partial_amount'] ?? 0), 0),
                    $total
                ),
                default => 0.0,
            };

            if ($status === 'partial' && $paidNow <= 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'partial_amount' => 'Enter the amount received for a partial payment.',
                ]);
            }

            $balance = max($total - $paidNow, 0);

            if ($status === 'partial' && $balance <= 0) {
                $status = 'paid';
            }

            if (empty($validated['invoice_no'])) {
                $validated['invoice_no'] =
                    'INV-'.now()->format('Ymd').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 6));
            }

            $validated['payment_status'] = $status;
            $validated['partial_amount'] = $paidNow;
            $validated['balance_amount'] = $balance;
            $validated['user_id'] = $validated['user_id'] ?: auth()->id();

            $sale = Sale::create($validated);

            if ($paidNow > 0) {
                Payment::create([
                    'transaction_type' => 'sale',
                    'invoice_no' => $sale->invoice_no,
                    'receipt_no' => $this->newReceiptNumber(),
                    'customer_id' => $sale->customer_id,
                    'supplier_id' => $sale->supplier_id,
                    'sale_id' => $sale->id,
                    'amount' => $paidNow,
                    'method' => $sale->payment_method,
                    'status' => 'successful',
                    'received_by' => auth()->id(),
                ]);
            }

            return $sale;
        });

        return redirect()
            ->route('sales.index')
            ->with(
                'status',
                $sale->payment_status === 'pending'
                    ? 'Sale created successfully. No receipt was generated because no payment has been received yet.'
                    : 'Sale created successfully and receipt generated for the amount received.'
            );
    }

    public function show(Sale $sale)
    {
        return view('sales.show', ['sale' => $sale]);
    }

    public function update(Request $request, Sale $sale)
    {
        $validated = $request->validate([
            'transaction_type' => 'sometimes|required|in:sale,purchase',
            'customer_id' => 'nullable|exists:customers,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'user_id' => 'nullable|exists:users,id',
            'invoice_no' => 'nullable',
            'total_amount' => 'nullable|numeric',
            'discount' => 'sometimes|required|numeric',
            'vat' => 'sometimes|required|numeric',
            'payment_method' => 'sometimes|required',
            'sale_date' => 'sometimes|required|date',
            'payment_status' => 'sometimes|required',
            'partial_amount' => 'sometimes|required|numeric',
            'balance_amount' => 'nullable|numeric',
            'branch_id' => 'nullable|exists:branches,id',
        ]);
        $sale->update($validated);
        return redirect()->route('sales.index')->with('status', 'Sale updated successfully.');
    }


    /**
     * POS sale: pick real in-stock items (not raw field entry). Creates the Sale +
     * SaleItems, decrements stock transactionally, matching CheckoutController's pattern.
     */
    public function posStore(Request $request)
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'customer_id' => 'nullable|exists:customers,id',
            'payment_method' => 'required|string',
            'discount' => 'nullable|numeric|min:0',
            'vat' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
        ]);

        $discount = (float) ($data['discount'] ?? 0);
        $vat = (float) ($data['vat'] ?? 0);

        try {
            $sale = \DB::transaction(function () use ($data, $discount, $vat) {
                $lines = [];
                $subtotal = 0.0;

                foreach ($data['items'] as $item) {
                    $stock = Stock::where('product_id', $item['product_id'])
                        ->where('branch_id', $data['branch_id'])
                        ->lockForUpdate()
                        ->first();

                    if (! $stock || $stock->quantity < $item['quantity']) {
                        throw new \RuntimeException(
                            'Insufficient stock for "'.($stock?->product?->name ?? 'product #'.$item['product_id']).'". '.
                            'Available: '.($stock?->quantity ?? 0).', requested: '.$item['quantity'].'.'
                        );
                    }

                    $lineTotal = (float) $stock->selling_price * $item['quantity'];
                    $subtotal += $lineTotal;

                    $lines[] = [
                        'product_id' => $item['product_id'],
                        'quantity' => $item['quantity'],
                        'price' => $stock->selling_price,
                        'total' => $lineTotal,
                    ];

                    $stock->decrement('quantity', $item['quantity']);
                }

                $totalAmount = max($subtotal - $discount + $vat, 0);

                $sale = Sale::create([
                    'transaction_type' => 'sale',
                    'customer_id' => $data['customer_id'] ?? null,
                    'user_id' => auth()->id(),
                    'invoice_no' => 'INV-'.now()->format('Ymd').'-'.strtoupper(substr(bin2hex(random_bytes(3)), 0, 6)),
                    'total_amount' => $totalAmount,
                    'discount' => $discount,
                    'vat' => $vat,
                    'payment_method' => $data['payment_method'],
                    'sale_date' => now()->format('Y-m-d'),
                    'payment_status' => 'paid',
                    'partial_amount' => 0,
                    'balance_amount' => 0,
                    'branch_id' => $data['branch_id'],
                ]);

                foreach ($lines as $line) {
                    $sale->sale_items()->create([
                        'product_id' => $line['product_id'],
                        'quantity' => $line['quantity'],
                        'price' => $line['price'],
                        'vat_amount' => 0,
                        'total' => $line['total'],
                    ]);
                }

                Payment::create([
                    'transaction_type' => 'sale',
                    'invoice_no' => $sale->invoice_no,
                    'receipt_no' => $this->newReceiptNumber(),
                    'customer_id' => $sale->customer_id,
                    'sale_id' => $sale->id,
                    'amount' => $totalAmount,
                    'method' => $sale->payment_method,
                    'status' => 'successful',
                    'received_by' => auth()->id(),
                ]);

                return $sale;
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['pos' => $e->getMessage()]);
        }

        return redirect()->route('sales.index')->with('status', "Sale {$sale->invoice_no} recorded successfully.");
    }


    /** Records a new payment against a sale. Multiple payments (and therefore
     *  multiple receipts) per sale are fully supported — this just appends another
     *  Payment row; PaymentObserver handles generating + emailing that payment's receipt. */
    public function recordPayment(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'amount' => ['required', 'numeric', 'min:0.01'],
            'method' => ['required', 'string', 'max:50'],
        ]);

        $sale->loadMissing(['customer', 'orders']);
        $currentPaid = (float) $sale->payments()->where('status', 'successful')->sum('amount');
        $remaining = max((float) $sale->total_amount - $currentPaid, 0);
        $amount = (float) $data['amount'];

        if ($remaining <= 0) {
            return back()->withErrors(['payment' => 'This sale is already fully paid.']);
        }

        if ($amount > $remaining + 0.001) {
            return back()->withErrors(['payment' => 'Payment cannot exceed the outstanding balance of UGX '.number_format($remaining, 0).'.']);
        }

        $payment = DB::transaction(function () use ($sale, $amount, $data, $currentPaid) {
            $payment = Payment::create([
                'transaction_type' => 'sale',
                'invoice_no' => $sale->invoice_no,
                'receipt_no' => $this->newReceiptNumber(),
                'customer_id' => $sale->customer_id,
                'sale_id' => $sale->id,
                'amount' => $amount,
                'method' => $data['method'],
                'status' => 'successful',
                'received_by' => auth()->id(),
            ]);

            $newPaid = $currentPaid + $amount;
            $newBalance = max((float) $sale->total_amount - $newPaid, 0);
            $paymentStatus = $newBalance <= 0 ? 'paid' : 'partial';

            $sale->update([
                'payment_method' => $data['method'],
                'payment_status' => $paymentStatus,
                'partial_amount' => $newPaid,
                'balance_amount' => $newBalance,
            ]);

            foreach ($sale->orders as $order) {
                $order->update([
                    'amount_paid' => $newPaid,
                    'balance_amount' => $newBalance,
                    'payment_status' => $paymentStatus,
                    'paid_at' => $newBalance <= 0 ? now() : null,
                    'status' => $newBalance <= 0 ? 'completed' : $order->status,
                ]);

                OrderLog::create([
                    'order_id' => $order->id,
                    'action' => 'payment_recorded',
                    'description' => 'POS payment of UGX '.number_format($amount, 0).' recorded using '.$data['method'].'. Receipt '.$payment->receipt_no.'.'.($newBalance <= 0 ? ' Order completed.' : ' Outstanding balance UGX '.number_format($newBalance, 0).'.'),
                ]);
            }

            return $payment;
        });

        return redirect()->route('payments.receipt-preview', $payment->id)
            ->with('status', $sale->fresh()->payment_status === 'paid'
                ? "Payment recorded successfully. Sale fully paid and receipt {$payment->receipt_no} generated."
                : "Part payment recorded successfully and receipt {$payment->receipt_no} generated.");
    }

    private function newReceiptNumber(): string
    {
        do {
            $receiptNo = 'RCT-'.now()->format('Ymd').'-'.strtoupper(
                substr(bin2hex(random_bytes(4)), 0, 8)
            );
        } while (Payment::where('receipt_no', $receiptNo)->exists());

        return $receiptNo;
    }

    public function destroy(Sale $sale)
    {
        $sale->delete();
        return redirect()->route('sales.index')->with('status', 'Sale deleted.');
    }
}
