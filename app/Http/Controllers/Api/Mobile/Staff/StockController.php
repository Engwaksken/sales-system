<?php

namespace App\Http\Controllers\Api\Mobile\Staff;

use App\Models\Product;
use App\Models\Stock;
use App\Models\StockIssue;
use App\Services\StockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** Stock levels, receiving stock, price edits, barcode lookup and stock issues (manage_stock). */
class StockController extends StaffController
{
    public const ISSUE_TYPES = ['damage' => 'Damages', 'gratis' => 'Gratis', 'staff_consumption' => 'Staff consumption'];

    public function index(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'manage_stock');

        $query = $this->scopeBranch(Stock::query(), $request)
            ->with(['product:id,name,sku,barcode', 'branch:id,name', 'supplier:id,name']);

        if ($search = trim((string) $request->query('q', ''))) {
            $query->whereHas('product', fn ($q) => $q->where(fn ($p) => $p->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('barcode', $search)));
        }
        if ($request->boolean('low')) {
            $query->where('quantity', '<=', 5);
        }

        $page = $query->latest('id')->paginate($this->perPage($request));

        return response()->json([
            'data' => collect($page->items())->map(fn (Stock $stock) => $this->payload($stock))->all(),
            'meta' => $this->meta($page),
        ]);
    }

    public function store(Request $request, StockService $stock): JsonResponse
    {
        $this->authorizePermission($request, 'manage_stock');

        $data = $request->validate([
            'product_id' => 'required|exists:products,id',
            'supplier_id' => 'nullable|exists:suppliers,id',
            'branch_ids' => 'nullable|array|min:1',
            'branch_ids.*' => 'integer|distinct|exists:branches,id',
            'quantity' => 'required|numeric|min:0.01',
            'unit_type' => 'nullable|string|max:50',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date',
            'stock_date' => 'nullable|date',
        ]);

        $branchIds = $this->isAdmin($request)
            ? array_map('intval', $data['branch_ids'] ?? [])
            : [$this->ownBranch($request)];
        abort_if($branchIds === [], 422, 'Choose at least one branch.');
        unset($data['branch_ids']);
        $data['total_stock'] = $data['quantity'];

        $result = $stock->receive($data, $branchIds);

        return response()->json([
            'message' => count($branchIds) === 1
                ? ($result['topped_up'] ? 'Stock topped up.' : 'Stock added.')
                : 'Stock saved to '.count($branchIds)." branches ({$result['added']} new, {$result['topped_up']} topped up).",
        ] + $result, 201);
    }

    public function update(Request $request, int $stock): JsonResponse
    {
        $this->authorizePermission($request, 'manage_stock');
        $record = $this->scopeBranch(Stock::query(), $request)->whereKey($stock)->firstOrFail();

        $record->update($request->validate([
            'supplier_id' => 'nullable|exists:suppliers,id',
            'quantity' => 'sometimes|required|numeric|min:0',
            'unit_type' => 'nullable|string|max:50',
            'cost_price' => 'nullable|numeric|min:0',
            'selling_price' => 'nullable|numeric|min:0',
            'expiry_date' => 'nullable|date',
        ]));

        return response()->json([
            'message' => 'Stock updated.',
            'data' => $this->payload($record->fresh(['product', 'branch', 'supplier'])),
        ]);
    }

    /** Product search for the receive-stock and stock-issue forms. */
    public function products(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'manage_stock');
        $search = trim((string) $request->query('q', ''));

        $page = Product::query()
            ->when($search !== '', fn ($q) => $q->where(fn ($p) => $p->where('name', 'like', "%{$search}%")
                ->orWhere('sku', 'like', "%{$search}%")
                ->orWhere('barcode', $search)))
            ->orderBy('name')
            ->paginate($this->perPage($request), ['id', 'name', 'sku', 'barcode', 'is_active']);

        return response()->json(['data' => $page->items(), 'meta' => $this->meta($page)]);
    }

    public function lookupBarcode(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'manage_stock');
        $barcode = $request->validate(['barcode' => 'required|string|max:100'])['barcode'];

        $product = Product::query()->where('barcode', $barcode)->first();
        abort_unless($product, 404, 'No product found for this barcode.');

        return response()->json(['data' => $product->only(['id', 'name', 'sku', 'barcode', 'is_active'])]);
    }

    public function issues(Request $request): JsonResponse
    {
        $this->authorizePermission($request, 'manage_stock');

        $page = $this->scopeBranch(StockIssue::query(), $request)
            ->with(['product:id,name', 'branch:id,name', 'user:id,name'])
            ->latest('id')
            ->paginate($this->perPage($request));

        return response()->json([
            'data' => collect($page->items())->map(fn (StockIssue $issue) => [
                'id' => $issue->id,
                'product_name' => $issue->product?->name,
                'branch_name' => $issue->branch?->name,
                'type' => $issue->type,
                'type_label' => self::ISSUE_TYPES[$issue->type] ?? $issue->type,
                'quantity' => (float) $issue->quantity,
                'reason' => $issue->reason,
                'recorded_by' => $issue->user?->name,
                'created_at' => optional($issue->created_at)?->toISOString(),
            ])->all(),
            'meta' => $this->meta($page),
        ]);
    }

    public function storeIssue(Request $request, StockService $stock): JsonResponse
    {
        $this->authorizePermission($request, 'manage_stock');

        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'branch_id' => ['nullable', 'exists:branches,id'],
            'type' => ['required', 'in:'.implode(',', array_keys(self::ISSUE_TYPES))],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);
        $data['branch_id'] = $this->writeBranch($request, $data['branch_id'] ?? null);

        $stock->issue($data, $request->user()->id);

        return response()->json(['message' => 'Stock issue recorded; no sale or cash entry was created.'], 201);
    }

    private function payload(Stock $stock): array
    {
        return [
            'id' => $stock->id,
            'product_id' => $stock->product_id,
            'product_name' => $stock->product?->name,
            'sku' => $stock->product?->sku,
            'barcode' => $stock->product?->barcode,
            'branch_id' => $stock->branch_id,
            'branch_name' => $stock->branch?->name,
            'supplier_id' => $stock->supplier_id,
            'supplier_name' => $stock->supplier?->name,
            'quantity' => (float) $stock->quantity,
            'unit_type' => $stock->unit_type,
            'cost_price' => $stock->cost_price === null ? null : $this->money($stock->cost_price),
            'selling_price' => $stock->selling_price === null ? null : $this->money($stock->selling_price),
            'expiry_date' => $stock->expiry_date ? \Illuminate\Support\Carbon::parse($stock->expiry_date)->toDateString() : null,
            'stock_date' => $stock->stock_date ? \Illuminate\Support\Carbon::parse($stock->stock_date)->toISOString() : null,
        ];
    }
}
