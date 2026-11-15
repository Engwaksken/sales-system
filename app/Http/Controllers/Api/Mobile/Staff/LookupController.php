<?php

namespace App\Http\Controllers\Api\Mobile\Staff;

use App\Models\Branch;
use App\Models\Expense;
use App\Models\Category;
use App\Models\ExpenseCategory;
use App\Models\Order;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\UnitType;
use App\Services\SalePaymentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;

/** Pick-list values the staff forms need, fetched once after sign-in. */
class LookupController extends StaffController
{
    public function __invoke(Request $request): JsonResponse
    {
        abort_if(($request->user()->role ?: 'customer') === 'customer', 403);
        $admin = $this->isAdmin($request);
        $own = $request->user()->branch_id;

        return response()->json([
            'is_admin' => $admin,
            'branch_id' => $own,
            'branches' => Branch::query()->orderBy('name')
                ->when(! $admin, fn ($q) => $q->whereKey($own ?: 0))
                ->get(['id', 'name']),
            'categories' => Schema::hasTable('categories')
                ? Category::query()->orderBy('category_name')->get(['id', 'category_name as name', 'status'])
                : [],
            'suppliers' => Schema::hasTable('suppliers')
                ? Supplier::query()->orderBy('name')->get(['id', 'name', 'status'])
                : [],
            'products' => Schema::hasTable('products')
                ? Product::query()->when(! $admin, fn ($q) => $q->where(fn ($branch) => $branch->where('branch_id', $own ?: 0)->orWhereNull('branch_id')))->orderBy('name')->get(['id', 'name'])
                : [],
            'expense_categories' => Schema::hasTable('expense_categories')
                ? ExpenseCategory::query()->orderBy('name')->get(['id', 'name'])
                : [],
            // Names for the expense form: active categories plus any already used.
            'expense_category_names' => Schema::hasTable('expense_categories') ? Expense::categoryOptions() : [],
            // Units for stock forms: [{value: 'pcs', label: 'Pieces (pcs)', category}]
            'unit_types' => UnitType::options(),
            'payment_methods' => SalePaymentService::METHODS,
            'order_stages' => collect(Order::STAGES)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
            'stock_issue_types' => collect(StockController::ISSUE_TYPES)->map(fn ($label, $key) => ['key' => $key, 'label' => $label])->values(),
        ]);
    }
}
