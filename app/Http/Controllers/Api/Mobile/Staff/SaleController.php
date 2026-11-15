<?php

namespace App\Http\Controllers\Api\Mobile\Staff;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Stock;
use App\Services\PosSaleService;
use App\Services\SalePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Sales list, sale detail, the POS counter and payments against a sale (manage_sales). */
class SaleController extends StaffController
{
    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'manage_sales');

        $query = $this->scopeBranch(Sale::query(), $request)
            ->with(['customer:id,name', 'branch:id,name', 'sale_items.product:id,name']);

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('invoice_no', 'like', "%{$search}%")
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%"))
                    ->orWhereHas('sale_items.product', fn ($p) => $p->where('name', 'like', "%{$search}%"));
            });
        }
        if ($status = $request->query('status')) {
            $query->where('payment_status', $status);
        }
        if ($request->filled('from')) {
            $query->whereDate('sale_date', '>=', $request->query('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('sale_date', '<=', $request->query('to'));
        }

        $page = $query->latest('id')->paginate($this->perPage($request));

        return response()->json([
            'data' => collect($page->items())->map(fn (Sale $sale) => $this->summary($sale))->all(),
            'meta' => $this->meta($page),
        ]);
    }

    public function show(Request $request, int $sale): JsonResponse
    {
        $this->authorizePermission($request, 'manage_sales');

        return response()->json(['data' => $this->detail($this->find($request, $sale))]);
    }

    /** Products that can be sold at a branch right now, with the price and quantity the POS will use. */
    public function posProducts(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'manage_sales');
        $branchId = $this->writeBranch($request, $request->query('branch_id'));
        $search = trim((string) $request->query('q', ''));

        // The POS sells from the first stock row of a product at the branch, so list that row.
        $rows = Stock::query()
            ->with('product:id,name,sku,barcode,image_url,is_active')
            ->where('branch_id', $branchId)
            ->whereHas('product', function ($q) use ($search) {
                $q->where('is_active', true);
                if ($search !== '') {
                    $q->where(fn ($p) => $p->where('name', 'like', "%{$search}%")
                        ->orWhere('sku', 'like', "%{$search}%")
                        ->orWhere('barcode', $search));
                }
            })
            ->orderBy('id')
            ->get()
            ->unique('product_id')
            ->filter(fn (Stock $stock) => $stock->quantity > 0 && $stock->selling_price !== null)
            ->sortBy(fn (Stock $stock) => strtolower((string) $stock->product?->name))
            ->values()
            ->map(fn (Stock $stock) => [
                'product_id' => $stock->product_id,
                'name' => $stock->product?->name,
                'sku' => $stock->product?->sku,
                'barcode' => $stock->product?->barcode,
                'available' => (float) $stock->quantity,
                'price' => $this->money($stock->selling_price),
                'unit_type' => $stock->unit_type,
            ]);

        return response()->json(['data' => $rows, 'branch_id' => $branchId]);
    }

    /** Customer search for the POS (the website's POS lists customers to anyone who can sell). */
    public function posCustomers(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'manage_sales');
        $search = trim((string) $request->query('q', ''));
        $branchId = $this->branchFilter($request);

        $page = Customer::query()
            ->when($branchId, fn ($q) => $q->where(fn ($b) => $b->where('branch_id', $branchId)->orWhereNull('branch_id')))
            ->when($search !== '', fn ($q) => $q->where(fn ($c) => $c->where('name', 'like', "%{$search}%")
                ->orWhere('phone', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%")))
            ->orderBy('name')
            ->paginate($this->perPage($request), ['id', 'name', 'phone', 'email']);

        return response()->json(['data' => $page->items(), 'meta' => $this->meta($page)]);
    }

    public function store(Request $request, PosSaleService $pos): JsonResponse
    {
        $this->authorizePermission($request, 'manage_sales');

        $data = $request->validate([
            'branch_id' => 'nullable|exists:branches,id',
            'customer_id' => 'nullable|exists:customers,id',
            'discount' => 'nullable|numeric|min:0',
            'vat' => 'nullable|numeric|min:0',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|integer|exists:products,id',
            'items.*.quantity' => 'required|integer|min:1',
            'payments' => 'nullable|array|max:10',
            'payments.*.method' => ['required_with:payments.*.amount', 'nullable', 'string', 'max:50'],
            'payments.*.amount' => 'nullable|numeric|min:0',
        ]);
        $data['branch_id'] = $this->writeBranch($request, $data['branch_id'] ?? null);

        [$sale, $receipts] = $pos->create($data, $request->user()->id);
        $sale = $sale->fresh();
        $balance = (float) $sale->balance_amount;

        $message = match (true) {
            $receipts->isEmpty() => "Sale {$sale->invoice_no} recorded on credit.",
            $balance > 0 => "Sale {$sale->invoice_no} recorded with a part payment. UGX ".number_format($balance, 0).' left on credit.',
            default => "Sale {$sale->invoice_no} recorded and fully paid.",
        };

        return response()->json(['message' => $message, 'data' => $this->detail($sale)], 201);
    }

    public function recordPayment(Request $request, int $sale, SalePaymentService $payments, PosSaleService $pos): JsonResponse
    {
        $this->authorizePermission($request, 'manage_sales');
        $record = $this->find($request, $sale);

        $data = $request->validate([
            'payments' => 'required|array|min:1|max:10',
            'payments.*.method' => ['required_with:payments.*.amount', 'nullable', 'string', 'max:50'],
            'payments.*.amount' => 'nullable|numeric|min:0',
        ]);

        $receipts = $pos->recordPayment($record, $payments->normalise($data['payments']));
        $record->refresh();

        return response()->json([
            'message' => $record->payment_status === 'paid'
                ? 'Payment recorded. Sale fully paid.'
                : 'Part payment recorded. Balance UGX '.number_format((float) $record->balance_amount, 0).'.',
            'receipts' => $receipts->pluck('receipt_no')->values(),
            'data' => $this->detail($record),
        ]);
    }

    /** Payment receipts across sales (manage_sales), newest first. */
    public function payments(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'manage_sales');

        $query = Payment::query()->with(['sale:id,invoice_no,branch_id', 'customer:id,name'])
            ->whereHas('sale', fn ($q) => $this->scopeBranch($q, $request));

        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(fn ($q) => $q->where('receipt_no', 'like', "%{$search}%")
                ->orWhere('invoice_no', 'like', "%{$search}%")
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', "%{$search}%")));
        }

        $page = $query->latest('id')->paginate($this->perPage($request));

        return response()->json([
            'data' => collect($page->items())->map(fn (Payment $p) => $this->paymentPayload($p) + [
                'sale_id' => $p->sale_id,
                'invoice_no' => $p->sale?->invoice_no ?? $p->invoice_no,
                'customer_name' => $p->customer?->name,
            ])->all(),
            'meta' => $this->meta($page),
        ]);
    }

    private function find(Request $request, int $id): Sale
    {
        return $this->scopeBranch(Sale::query(), $request)->whereKey($id)->firstOrFail();
    }

    private function summary(Sale $sale): array
    {
        return [
            'id' => $sale->id,
            'invoice_no' => $sale->invoice_no,
            'sale_date' => $sale->sale_date ? (string) \Illuminate\Support\Carbon::parse($sale->sale_date)->toDateString() : null,
            'customer_name' => $sale->customer?->name ?? 'Walk-in customer',
            'branch_name' => $sale->branch?->name,
            'product_names' => $sale->sale_items->map(fn ($item) => $item->product?->name)->filter()->unique()->implode(', '),
            'total_amount' => $this->money($sale->total_amount),
            'paid_amount' => $this->money($sale->partial_amount),
            'balance_amount' => $this->money($sale->balance_amount),
            'payment_status' => $sale->payment_status,
            'payment_method' => $sale->payment_method,
        ];
    }

    private function detail(Sale $sale): array
    {
        $sale->load(['customer', 'branch', 'user:id,name', 'sale_items.product:id,name,sku', 'payments' => fn ($q) => $q->orderBy('id')]);

        return $this->summary($sale) + [
            'customer_id' => $sale->customer_id,
            'customer_phone' => $sale->customer?->phone,
            'branch_id' => $sale->branch_id,
            'sold_by' => $sale->user?->name,
            'discount' => $this->money($sale->discount),
            'vat' => $this->money($sale->vat),
            'items' => $sale->sale_items->map(fn ($item) => [
                'product_id' => $item->product_id,
                'name' => $item->product?->name ?? 'Product #'.$item->product_id,
                'sku' => $item->product?->sku,
                'quantity' => (float) $item->quantity,
                'price' => $this->money($item->price),
                'total' => $this->money($item->total),
            ])->values(),
            'payments' => $sale->payments->map(fn (Payment $p) => $this->paymentPayload($p))->values(),
        ];
    }

    private function paymentPayload(Payment $payment): array
    {
        return [
            'id' => $payment->id,
            'receipt_no' => $payment->receipt_no,
            'method' => $payment->method,
            'amount' => $this->money($payment->amount),
            'status' => $payment->status,
            'created_at' => optional($payment->created_at)?->toISOString(),
        ];
    }
}
