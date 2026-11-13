<?php

namespace App\Http\Controllers;

use App\Mail\DeliveryNoteMail;
use App\Models\Sale;
use App\Models\OrderLog;
use App\Models\Branch;
use App\Models\Customer;
use App\Models\Supplier;
use App\Models\User;
use App\Models\Stock;
use App\Models\Payment;
use App\Services\SalePaymentService;
use Dompdf\Dompdf;
use Dompdf\Options;
use App\Support\ExportsCsv;
use App\Support\DocumentBranding;
use App\Support\DocumentNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

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

    /** Browser print view sized for 80mm thermal (XP-80C), A5 or A4 paper. */
    public function invoicePrint(Request $request, Sale $sale)
    {
        $paper = strtolower((string) $request->query('paper', 'a4'));
        if (! in_array($paper, ['80mm', 'a5', 'a4'], true)) {
            $paper = 'a4';
        }

        $sale->load(['customer', 'sale_items.product', 'branch', 'payments', 'orders']);

        return view('sales.invoice-print', [
            'sale' => $sale,
            'paper' => $paper,
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

    /** Stream the delivery note PDF inline inside the preview page. */
    public function deliveryNotePdf(Sale $sale)
    {
        return $this->deliveryNoteResponse($sale, 'inline');
    }

    /** In-app delivery note preview with download and email actions. */
    public function deliveryNotePreview(Sale $sale)
    {
        $sale->load(['customer', 'sale_items.product', 'branch', 'orders']);

        return view('sales.delivery-note-preview', [
            'sale' => $sale,
            'branding' => DocumentBranding::get(),
        ]);
    }

    public function deliveryNoteDownload(Sale $sale)
    {
        return $this->deliveryNoteResponse($sale, 'attachment');
    }

    /** Emails the branded delivery note PDF to the customer, or any address the user enters. */
    public function deliveryNoteEmail(Request $request, Sale $sale)
    {
        $data = $request->validate([
            'email' => ['required', 'email', 'max:255'],
            'message' => ['nullable', 'string', 'max:1000'],
        ]);

        $sale->load(['customer', 'sale_items.product', 'branch', 'orders']);

        try {
            Mail::to($data['email'])->send(new DeliveryNoteMail(
                $sale,
                $this->renderDeliveryNote($sale),
                $sale->delivery_note_no.'.pdf',
                $data['message'] ?? null,
            ));
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors([
                'email' => 'The delivery note could not be sent. Check the email settings and try again.',
            ]);
        }

        return redirect()->route('sales.delivery-note-preview', $sale->id)
            ->with('status', "Delivery note {$sale->delivery_note_no} sent to {$data['email']}.");
    }

    private function deliveryNoteResponse(Sale $sale, string $disposition)
    {
        $sale->load(['customer', 'sale_items.product', 'branch', 'orders']);

        return response($this->renderDeliveryNote($sale), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition.'; filename="'.$sale->delivery_note_no.'.pdf"',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function renderDeliveryNote(Sale $sale): string
    {
        $options = new Options();
        $options->set('isRemoteEnabled', true);
        $dompdf = new Dompdf($options);
        $dompdf->loadHtml(view('sales.delivery-note-pdf', [
            'sale' => $sale,
            'branding' => DocumentBranding::get(),
        ])->render());
        $dompdf->setPaper('A4');
        $dompdf->render();

        return $dompdf->output();
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

    if ($request->filled('customer_id')) {
        $query->where('customer_id', $request->integer('customer_id'));
    }

    $this->applyProductAndMethodFilters($query, $request);

    if (in_array($request->query('payment_status'), ['paid', 'partial', 'pending'], true)) {
        $query->where('payment_status', $request->query('payment_status'));
    } elseif ($request->query('payment_status') === 'outstanding') {
        $query->where('balance_amount', '>', 0);
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
            'price' => $s->selling_price === null ? null : (float) $s->selling_price,
            'available' => (int) $s->quantity,
        ])->values();

    $stat1 = 'UGX '.number_format(Sale::sum('total_amount'), 0);
    $periodStats = $this->periodStats(Sale::class, 'sale_date', 'total_amount');
    $customer_options = Customer::orderBy('name')
        ->withSum(['sales as outstanding_balance' => fn ($q) => $q->where('transaction_type', 'sale')], 'balance_amount')
        ->get();
    $supplier_options = Supplier::orderBy('name')->get();
    $user_options = User::orderBy('name')->get();
    $branch_options = Branch::orderBy('name')->get();
    $defaultPosBranchId = $branch_options->first(fn ($branch) => strtolower(trim($branch->name)) === 'main branch')?->id;
    $receivables = (float) Sale::where('transaction_type', 'sale')->sum('balance_amount');
    $collected = (float) Payment::where('transaction_type', 'sale')->where('status', 'successful')->sum('amount');
    $product_options = \App\Models\Product::orderBy('name')->get(['id', 'name']);

    return view('sales.index', [
        'items' => $sales,
        'customer_options' => $customer_options,
        'supplier_options' => $supplier_options,
        'user_options' => $user_options,
        'branch_options' => $branch_options,
        'defaultPosBranchId' => $defaultPosBranchId,
        'stat1' => $stat1,
        'periodStats' => $periodStats,
        'stockData' => $stockData,
        'branchStat' => Branch::count(),
        'supplierStat' => Supplier::count(),
        'filteredStat' => $filteredStat,
        'totalStat' => Sale::count(),
        'receivables' => $receivables,
        'collected' => $collected,
        'product_options' => $product_options,
        'paymentMethods' => SalePaymentService::METHODS,
        'filteredStatLabel' => 'Filtered Sales',
        'totalStatLabel' => 'All Sales',
    ]);
}

    /**
     * Sales containing a product, and sales paid with a method. A method matches the
     * sale's own label (Cash, Credit, Split (...)) or any of its payments, so a
     * split cash + mobile money sale shows under both.
     */
    private function applyProductAndMethodFilters($query, Request $request): void
    {
        if ($request->filled('product_id')) {
            $productId = $request->integer('product_id');
            $query->whereHas('sale_items', fn ($q) => $q->where('product_id', $productId));
        }

        $method = trim((string) $request->query('payment_method', ''));
        if ($method === 'Credit') {
            $query->where('balance_amount', '>', 0);
        } elseif ($method !== '') {
            $query->where(fn ($q) => $q
                ->where('payment_method', $method)
                ->orWhereHas('payments', fn ($p) => $p->where('status', 'successful')->where('method', $method)));
        }
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
        $this->applyProductAndMethodFilters($query, $request);
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
        $this->applyProductAndMethodFilters($query, $request);
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

    public function store(Request $request, SalePaymentService $payments)
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
            'sale_date' => 'required|date',
            'payments' => 'nullable|array|max:10',
            'payments.*.method' => ['required_with:payments.*.amount', 'nullable', 'string', 'max:50'],
            'payments.*.amount' => 'nullable|numeric|min:0',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        $tenders = $payments->normalise($validated['payments'] ?? []);
        $payments->assertTendersFit((float) $validated['total_amount'], $tenders, $validated['customer_id'] ?? null);
        unset($validated['payments']);

        [$sale, $receipts] = DB::transaction(function () use ($validated, $tenders, $payments) {
            $sale = Sale::create(array_merge($validated, [
                'invoice_no' => ($validated['invoice_no'] ?? '') ?: DocumentNumber::invoice(),
                'user_id' => ($validated['user_id'] ?? null) ?: auth()->id(),
            ], $payments->openingState((float) $validated['total_amount'], $tenders)));

            return [$sale, $payments->record($sale, $tenders)];
        });

        return $this->afterSaleRecorded($sale->fresh(), $receipts);
    }

    public function show(Sale $sale)
    {
        return view('sales.show', ['sale' => $sale]);
    }

    public function update(Request $request, Sale $sale, SalePaymentService $payments)
    {
        // Paid / balance / status are derived from the sale's payments, never typed in.
        $validated = $request->validate([
            'transaction_type' => 'sometimes|required|in:sale,purchase',
            'customer_id' => 'nullable|exists:customers,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'user_id' => 'nullable|exists:users,id',
            'invoice_no' => 'nullable|string|max:100',
            'total_amount' => 'nullable|numeric|min:0',
            'discount' => 'sometimes|required|numeric|min:0',
            'vat' => 'sometimes|required|numeric|min:0',
            'sale_date' => 'sometimes|required|date',
            'branch_id' => 'nullable|exists:branches,id',
        ]);

        if (array_key_exists('total_amount', $validated) && $validated['total_amount'] === null) {
            unset($validated['total_amount']);
        }

        $paid = (float) $sale->payments()->where('status', 'successful')->sum('amount');
        $total = (float) ($validated['total_amount'] ?? $sale->total_amount);
        $customerId = array_key_exists('customer_id', $validated) ? $validated['customer_id'] : $sale->customer_id;

        if ($total + 0.005 < $paid) {
            return back()->withInput()->withErrors(['total_amount' => 'The total cannot be less than the UGX '.number_format($paid, 0).' already received.']);
        }
        if ($paid + 0.005 < $total && ! $customerId) {
            return back()->withInput()->withErrors(['customer_id' => 'A sale with an outstanding balance must belong to a registered customer.']);
        }

        DB::transaction(function () use ($sale, $validated, $payments) {
            $sale->update($validated);
            $payments->sync($sale);
        });

        return redirect()->route('sales.index')->with('status', 'Sale updated successfully.');
    }

    /**
     * POS sale: pick real in-stock items, then settle it with any mix of tenders.
     * Paying less than the total (part payment, or nothing = on credit) is allowed
     * only for registered customers. Every tender becomes its own receipt.
     */
    public function posStore(Request $request, SalePaymentService $payments)
    {
        $data = $request->validate([
            'branch_id' => 'required|exists:branches,id',
            'customer_id' => 'nullable|exists:customers,id',
            'discount' => 'nullable|numeric|min:0',
            'vat' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payments' => 'nullable|array|max:10',
            'payments.*.method' => ['required_with:payments.*.amount', 'nullable', 'string', 'max:50'],
            'payments.*.amount' => 'nullable|numeric|min:0',
            // Older clients send a single method and expect the sale to be paid in full.
            'payment_method' => 'nullable|string|max:50',
        ]);

        $discount = (float) ($data['discount'] ?? 0);
        $vat = (float) ($data['vat'] ?? 0);
        $legacyFullPayment = ! $request->has('payments') && ! empty($data['payment_method']);
        $tenders = $payments->normalise($data['payments'] ?? []);
        $customerId = $data['customer_id'] ?? null;

        try {
            [$sale, $receipts] = DB::transaction(function () use ($data, $discount, $vat, $legacyFullPayment, $tenders, $customerId, $payments) {
                $lines = [];
                $subtotal = 0.0;

                foreach ($data['items'] as $item) {
                    $stock = Stock::with('product')
                        ->where('product_id', $item['product_id'])
                        ->where('branch_id', $data['branch_id'])
                        ->lockForUpdate()
                        ->first();

                    if (! $stock || $stock->quantity < $item['quantity']) {
                        throw ValidationException::withMessages(['pos' => 'Insufficient stock for "'.($stock?->product?->name ?? 'product #'.$item['product_id']).'". '
                            .'Available: '.($stock?->quantity ?? 0).', requested: '.$item['quantity'].'.']);
                    }

                    if ($stock->selling_price === null) {
                        throw ValidationException::withMessages(['pos' => '"'.($stock->product?->name ?? 'Product #'.$item['product_id']).'" has no selling price yet. Set one on the Stock page before selling it.']);
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

                $totalAmount = round(max($subtotal - $discount + $vat, 0), 2);

                if ($legacyFullPayment && $totalAmount > 0) {
                    $tenders = [['method' => $data['payment_method'], 'amount' => $totalAmount]];
                }

                // Checked against the server-side total, not what the browser displayed.
                $payments->assertTendersFit($totalAmount, $tenders, $customerId);

                $sale = Sale::create([
                    'transaction_type' => 'sale',
                    'customer_id' => $customerId,
                    'user_id' => auth()->id(),
                    'invoice_no' => DocumentNumber::invoice(),
                    'total_amount' => $totalAmount,
                    'discount' => $discount,
                    'vat' => $vat,
                    'sale_date' => now()->format('Y-m-d'),
                    'branch_id' => $data['branch_id'],
                ] + $payments->openingState($totalAmount, $tenders));

                foreach ($lines as $line) {
                    $sale->sale_items()->create([
                        'product_id' => $line['product_id'],
                        'quantity' => $line['quantity'],
                        'price' => $line['price'],
                        'vat_amount' => 0,
                        'total' => $line['total'],
                    ]);
                }

                return [$sale, $payments->record($sale, $tenders)];
            });
        } catch (ValidationException $e) {
            throw $e;
        } catch (\Throwable $e) {
            report($e);

            return back()->withInput()->withErrors(['pos' => $e->getMessage()]);
        }

        return $this->afterSaleRecorded($sale->fresh(), $receipts);
    }

    /**
     * Records one or more payments against an existing sale (e.g. a customer paying
     * off credit in cash + mobile money). Each tender gets its own receipt;
     * PaymentObserver emails it and posts it to the ledger.
     */
    public function recordPayment(Request $request, Sale $sale, SalePaymentService $payments)
    {
        $data = $request->validate([
            // Either a list of tenders, or the single amount/method pair older forms send.
            'payments' => 'required_without:amount|array|max:10',
            'payments.*.method' => ['required_with:payments.*.amount', 'nullable', 'string', 'max:50'],
            'payments.*.amount' => 'nullable|numeric|min:0',
            'amount' => 'required_without:payments|nullable|numeric|min:0.01',
            'method' => 'required_with:amount|nullable|string|max:50',
        ]);

        $tenders = $payments->normalise($data['payments'] ?? [['method' => $data['method'] ?? '', 'amount' => $data['amount'] ?? 0]]);

        if ($tenders === []) {
            return back()->withErrors(['payment' => 'Enter at least one payment amount.']);
        }

        try {
            $receipts = DB::transaction(function () use ($sale, $tenders, $payments) {
                $locked = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();
                $alreadyPaid = (float) $locked->payments()->where('status', 'successful')->sum('amount');

                if ((float) $locked->total_amount - $alreadyPaid <= 0.005) {
                    throw ValidationException::withMessages(['payment' => 'This sale is already fully paid.']);
                }

                // Paying towards an existing balance is always allowed (walk-in sales
                // from before this rule included); only overpaying is rejected.
                $payments->assertTendersFit((float) $locked->total_amount, $tenders, $locked->customer_id ?: -1, $alreadyPaid);

                $receipts = $payments->record($locked, $tenders);
                $paidNow = $receipts->sum(fn ($p) => (float) $p->amount);
                $locked->refresh();

                foreach ($locked->orders as $order) {
                    OrderLog::create([
                        'order_id' => $order->id,
                        'action' => 'payment_recorded',
                        'description' => 'POS payment of UGX '.number_format($paidNow, 0).' recorded ('.$receipts->pluck('method')->unique()->join(', ').'). Receipt '.$receipts->pluck('receipt_no')->join(', ').'.'
                            .((float) $locked->balance_amount <= 0 ? ' Order completed.' : ' Outstanding balance UGX '.number_format((float) $locked->balance_amount, 0).'.'),
                    ]);
                }

                return $receipts;
            });
        } catch (ValidationException $e) {
            return back()->withErrors(['payment' => collect($e->errors())->flatten()->first()]);
        }

        $sale->refresh();
        $numbers = $receipts->pluck('receipt_no')->join(', ');
        $message = $sale->payment_status === 'paid'
            ? "Payment recorded. Sale fully paid. Receipt(s) {$numbers} generated."
            : "Part payment recorded. Receipt(s) {$numbers} generated. Balance UGX ".number_format((float) $sale->balance_amount, 0).'.';

        return $receipts->count() === 1
            ? redirect()->route('payments.receipt-preview', $receipts->first()->id)->with('status', $message)
            : redirect()->route('sales.index', ['q' => $sale->invoice_no])->with('status', $message);
    }

    /** Where to land after a new sale: its receipt when there is exactly one, otherwise the sale list. */
    private function afterSaleRecorded(Sale $sale, Collection $receipts)
    {
        $balance = (float) $sale->balance_amount;
        $numbers = $receipts->pluck('receipt_no')->join(', ');

        $message = match (true) {
            $receipts->isEmpty() => "Sale {$sale->invoice_no} recorded on credit. UGX ".number_format($balance, 0)." added to the customer's balance.",
            $balance > 0 => "Sale {$sale->invoice_no} recorded with a part payment. Receipt(s) {$numbers} generated; UGX ".number_format($balance, 0).' left on credit.',
            default => "Sale {$sale->invoice_no} recorded and fully paid. Receipt(s) {$numbers} generated.",
        };

        if ($receipts->count() === 1) {
            return redirect()->route('payments.receipt-preview', $receipts->first()->id)->with('status', $message);
        }

        return redirect()->route('sales.index')->with('status', $message);
    }

    public function destroy(Sale $sale)
    {
        $sale->delete();
        return redirect()->route('sales.index')->with('status', 'Sale deleted.');
    }
}
