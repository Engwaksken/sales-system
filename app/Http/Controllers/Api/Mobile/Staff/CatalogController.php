<?php

namespace App\Http\Controllers\Api\Mobile\Staff;

use App\Support\PublicMedia;
use App\Models\Category;
use App\Models\Customer;
use App\Models\ExpenseCategory;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\UnitType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rule;

/**
 * Master data the website manages with simple forms: products, categories, customers,
 * suppliers and expense categories. Validation and permissions match the website.
 */
class CatalogController extends StaffController
{
    /** resource => [model, permission, search columns, has branch_id] */
    private const RESOURCES = [
        'categories' => [Category::class, 'manage_categories', ['category_name', 'description'], false],
        'customers' => [Customer::class, 'manage_customers', ['name', 'phone', 'email'], true],
        'suppliers' => [Supplier::class, 'manage_suppliers', ['name', 'contact_name', 'phone', 'email'], true],
        'expense-categories' => [ExpenseCategory::class, 'manage_expenses', ['name'], false],
        'products' => [Product::class, 'manage_products', ['name', 'sku', 'barcode'], false],
        'unit-types' => [UnitType::class, 'manage_stock', ['name', 'abbreviation'], false],
    ];

    public function index(Request $request, string $resource): JsonResponse
    {
        [$model, , $searchColumns, $hasBranch] = $this->definition($request, $resource);
        $query = $model::query();

        if ($hasBranch && ($branchId = $this->branchFilter($request))) {
            // Records without a branch are shared by every branch.
            $query->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
        }
        if ($search = trim((string) $request->query('q', ''))) {
            $query->where(function ($q) use ($searchColumns, $search) {
                foreach ($searchColumns as $column) {
                    $q->orWhere($column, 'like', "%{$search}%");
                }
            });
        }
        if ($resource === 'products') {
            $query->with('category:id,category_name')->withSum('stock', 'quantity');
            if ($request->filled('category_id')) {
                $query->where('category_id', $request->integer('category_id'));
            }
        }

        $orderColumn = $resource === 'categories' ? 'category_name' : 'name';
        if ($resource === 'unit-types') {
            $query->orderBy('category');
        }
        $page = $query->orderBy($orderColumn)->paginate($this->perPage($request));

        return response()->json([
            'data' => collect($page->items())->map(fn (Model $row) => $this->payload($resource, $row))->all(),
            'meta' => $this->meta($page),
        ]);
    }

    public function show(Request $request, string $resource, int $id): JsonResponse
    {
        $this->definition($request, $resource);

        return response()->json(['data' => $this->payload($resource, $this->find($request, $resource, $id))]);
    }

    public function store(Request $request, string $resource): JsonResponse
    {
        [$model] = $this->definition($request, $resource);
        $data = $this->validated($request, $resource);

        $record = DB::transaction(function () use ($model, $data, $request, $resource) {
            $record = $model::create($data);
            $this->afterSave($request, $resource, $record);

            return $record;
        });

        return response()->json([
            'message' => $this->label($resource).' created.',
            'data' => $this->payload($resource, $record->fresh()),
        ], 201);
    }

    public function update(Request $request, string $resource, int $id): JsonResponse
    {
        $this->definition($request, $resource);
        $record = $this->find($request, $resource, $id);
        $data = $this->validated($request, $resource, $record);

        DB::transaction(function () use ($record, $data, $request, $resource) {
            $record->update($data);
            $this->afterSave($request, $resource, $record);
        });

        return response()->json([
            'message' => $this->label($resource).' updated.',
            'data' => $this->payload($resource, $record->fresh()),
        ]);
    }

    public function destroy(Request $request, string $resource, int $id): JsonResponse
    {
        $this->definition($request, $resource);
        $record = $this->find($request, $resource, $id);

        try {
            $record->delete();
        } catch (QueryException) {
            abort(422, 'This '.strtolower($this->label($resource)).' is used by other records and cannot be deleted.');
        }

        return response()->json(['message' => $this->label($resource).' deleted.']);
    }

    /** @return array{0: class-string<Model>, 1: string, 2: array<int, string>, 3: bool} */
    private function definition(Request $request, string $resource): array
    {
        abort_unless(isset(self::RESOURCES[$resource]), 404);
        $this->authorizePermission($request, self::RESOURCES[$resource][1]);

        return self::RESOURCES[$resource];
    }

    private function find(Request $request, string $resource, int $id): Model
    {
        [$model, , , $hasBranch] = self::RESOURCES[$resource];
        $query = $model::query()->whereKey($id);

        if ($hasBranch && ($branchId = $this->branchFilter($request))) {
            $query->where(fn ($q) => $q->where('branch_id', $branchId)->orWhereNull('branch_id'));
        }

        return $query->firstOrFail();
    }

    private function validated(Request $request, string $resource, ?Model $record = null): array
    {
        $required = $record ? 'sometimes|required' : 'required';

        $data = $request->validate(match ($resource) {
            'categories' => [
                'category_name' => $required,
                'description' => 'nullable',
                'status' => $required.'|in:active,inactive',
            ],
            'customers' => [
                'branch_id' => 'nullable|exists:branches,id',
                'name' => 'required|string|max:150',
                'phone' => 'nullable|string|max:50',
                'email' => 'nullable|email|max:150',
                'address' => 'nullable|string|max:500',
            ],
            'suppliers' => [
                'branch_id' => 'nullable|exists:branches,id',
                'name' => $required,
                'contact_name' => $required,
                'email' => 'nullable',
                'phone' => 'nullable',
                'address' => 'nullable',
                'status' => $required.'|in:active,inactive',
            ],
            'expense-categories' => [
                'name' => $required,
                'status' => 'boolean',
            ],
            'unit-types' => [
                'name' => $required.'|string|max:60',
                'abbreviation' => [$record ? 'sometimes' : 'required', 'string', 'max:20', Rule::unique('unit_types', 'abbreviation')->ignore($record?->getKey())],
                'category' => ['nullable', Rule::in(UnitType::CATEGORIES)],
                'is_active' => 'boolean',
            ],
            'products' => [
                'name' => $required,
                'sku' => 'nullable',
                'barcode' => ['nullable', 'string', 'max:100', Rule::unique('products', 'barcode')->ignore($record?->getKey())],
                'category_id' => 'nullable|exists:categories,id',
                'description' => 'nullable',
                'is_active' => 'boolean',
                'image' => 'nullable|image|max:4096',
            ],
        });

        if (self::RESOURCES[$resource][3]) {
            // Staff outside admin always file records under their own branch.
            $data['branch_id'] = $this->isAdmin($request)
                ? ($data['branch_id'] ?? $record?->branch_id)
                : $this->ownBranch($request);
        }

        if ($resource === 'products') {
            if ($request->hasFile('image')) {
                $data['image_url'] = $request->file('image')->store('products', 'public');
            }
            unset($data['image']);
            if (! $record && ! $this->isAdmin($request)) {
                $data['branch_id'] = $this->ownBranch($request);
            }
        }

        return $data;
    }

    private function afterSave(Request $request, string $resource, Model $record): void
    {
        // New products are listed at the branch that created them, like the website's branch checklist.
        if ($resource === 'products' && $record->wasRecentlyCreated && $record->branch_id
            && Schema::hasTable('product_branches')) {
            $record->product_branches()->firstOrCreate(['branch_id' => $record->branch_id]);
        }
    }

    private function payload(string $resource, Model $row): array
    {
        return match ($resource) {
            'categories' => $row->only(['id', 'category_name', 'description', 'status']) + ['name' => $row->category_name],
            'customers' => $row->only(['id', 'name', 'phone', 'email', 'address', 'branch_id']),
            'suppliers' => $row->only(['id', 'name', 'contact_name', 'phone', 'email', 'address', 'status', 'branch_id']),
            'expense-categories' => ['id' => $row->id, 'name' => $row->name, 'status' => (bool) $row->status],
            'unit-types' => $row->only(['id', 'name', 'abbreviation', 'category']) + [
                'is_active' => (bool) $row->is_active,
                'status' => $row->is_active ? 'active' : 'inactive',
            ],
            'products' => $row->only(['id', 'name', 'sku', 'barcode', 'category_id', 'description']) + [
                'is_active' => (bool) $row->is_active,
                'category_name' => $row->category?->category_name,
                'image_url' => $this->imageUrl($row->image_url),
                'stock_quantity' => (float) ($row->stock_sum_quantity ?? $row->stock()->sum('quantity')),
            ],
        };
    }

    private function imageUrl(?string $path): ?string
    {
        return PublicMedia::url($path);
    }

    private function label(string $resource): string
    {
        return match ($resource) {
            'categories' => 'Category',
            'customers' => 'Customer',
            'suppliers' => 'Supplier',
            'expense-categories' => 'Expense category',
            'unit-types' => 'Unit',
            'products' => 'Product',
        };
    }
}
