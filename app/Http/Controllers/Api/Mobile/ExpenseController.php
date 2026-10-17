<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\RolePermission;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Expense::query();
        $this->authorizeExpenses($request);
        $this->scopeBranch($query, $request);

        if ($request->filled('q')) {
            $search = trim((string) $request->q);

            $query->where(function ($q) use ($search) {
                foreach (['description', 'payee', 'vendor', 'reference_no', 'receipt_no'] as $column) {
                    if (Schema::hasColumn('expenses', $column)) {
                        $q->orWhere($column, 'like', '%'.$search.'%');
                    }
                }
            });
        }

        $items = $query->latest()->paginate(20);

        return response()->json([
            'data' => collect($items->items())->map(fn ($expense) => $this->payload($expense))->all(),
            'meta' => [
                'current_page' => $items->currentPage(),
                'last_page' => $items->lastPage(),
                'total' => $items->total(),
            ],
        ]);
    }

    public function show(Expense $expense): JsonResponse
    {
        $this->authorizeExpenses(request());
        $expense = $this->scopeBranch(Expense::query(), request())->whereKey($expense->getKey())->firstOrFail();
        return response()->json(['data' => $this->payload($expense)]);
    }

    public function store(Request $request): JsonResponse
    {
        $this->authorizeExpenses($request);
        $data = $this->validateExpense($request);
        $this->enforceExpenseBranch($data, $request);
        $data = $this->filterColumns($data);

        if ($request->hasFile('receipt_image')) {
            $path = $request->file('receipt_image')->store('expense-receipts', 'public');

            foreach (['receipt_image', 'receipt_path', 'attachment', 'file_path'] as $column) {
                if (Schema::hasColumn('expenses', $column)) {
                    $data[$column] = $path;
                    break;
                }
            }
        }

        if (Schema::hasColumn('expenses', 'created_by')) {
            $data['created_by'] = $request->user()->id;
        }

        $expense = Expense::create($data);

        return response()->json([
            'message' => 'Expense saved successfully.',
            'data' => $this->payload($expense),
        ], 201);
    }

    public function update(Request $request, Expense $expense): JsonResponse
    {
        $this->authorizeExpenses($request);
        $expense = $this->scopeBranch(Expense::query(), $request)->whereKey($expense->getKey())->firstOrFail();
        $data = $this->filterColumns($this->validateExpense($request));
        $this->enforceExpenseBranch($data, $request);

        if ($request->hasFile('receipt_image')) {
            $path = $request->file('receipt_image')->store('expense-receipts', 'public');

            foreach (['receipt_image', 'receipt_path', 'attachment', 'file_path'] as $column) {
                if (Schema::hasColumn('expenses', $column)) {
                    $data[$column] = $path;
                    break;
                }
            }
        }

        if (Schema::hasColumn('expenses', 'updated_by')) {
            $data['updated_by'] = $request->user()->id;
        }

        $expense->update($data);

        return response()->json([
            'message' => 'Expense updated successfully.',
            'data' => $this->payload($expense->fresh()),
        ]);
    }

    public function destroy(Expense $expense): JsonResponse
    {
        $this->authorizeExpenses(request());
        $expense = $this->scopeBranch(Expense::query(), request())->whereKey($expense->getKey())->firstOrFail();
        $expense->delete();

        return response()->json(['message' => 'Expense deleted successfully.']);
    }

    private function validateExpense(Request $request): array
    {
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'expense_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'payee' => ['nullable', 'string', 'max:255'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'receipt_no' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'category' => ['required', 'string', 'max:150'],
            'category_id' => ['nullable', 'integer', Rule::exists('expense_categories', 'id')],
            'supplier_id' => ['nullable', 'integer', Rule::exists('suppliers', 'id')],
            'branch_id' => ['nullable', 'integer', Rule::exists('branches', 'id')],
            'receipt_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);
        $user = $request->user();
        if ($user->role !== 'admin') {
            abort_unless($user->branch_id, 403);
            foreach (['category_id' => 'expense_categories', 'supplier_id' => 'suppliers'] as $field => $table) {
                if (!empty($data[$field]) && Schema::hasTable($table) && Schema::hasColumn($table, 'branch_id')) {
                    abort_unless(DB::table($table)->where('id', $data[$field])->where('branch_id', $user->branch_id)->exists(), 422);
                }
            }
        }
        return $data;
    }

    private function authorizeExpenses(Request $request): void
    {
        $user = $request->user();
        abort_unless($user, 401);
        abort_if(in_array($user->role, ['customer', 'cashier'], true), 403);
        if ($user->role !== 'admin') {
            abort_unless(RolePermission::where('role', $user->role)->where('permission_key', 'manage_expenses')->exists(), 403);
        }
    }

    private function scopeBranch($query, Request $request)
    {
        $user = $request->user();
        if ($user->role === 'admin') return $query;
        abort_unless(Schema::hasColumn('expenses', 'branch_id') && Schema::hasTable('branches') && Schema::hasColumn('branches', 'id'), 403);
        abort_unless($user->branch_id, 403);
        return $query->where('branch_id', $user->branch_id);
    }

    private function enforceExpenseBranch(array &$data, Request $request): void
    {
        $user = $request->user();
        if ($user->role === 'admin') return;

        abort_unless(Schema::hasColumn('expenses', 'branch_id') && Schema::hasTable('branches') && Schema::hasColumn('branches', 'id'), 403);
        abort_unless($user->branch_id, 403);
        if (array_key_exists('branch_id', $data) && $data['branch_id'] !== null) {
            abort_unless((int) $data['branch_id'] === (int) $user->branch_id, 403);
        }
        $data['branch_id'] = $user->branch_id;
    }

    private function filterColumns(array $data): array
    {
        $columns = Schema::getColumnListing('expenses');

        return collect($data)
            ->except('receipt_image')
            ->filter(fn ($value, $key) => in_array($key, $columns, true))
            ->all();
    }

    private function payload(Expense $expense): array
    {
        $data = array_intersect_key($expense->toArray(), array_flip([
            'id', 'title', 'amount', 'expense_date', 'category', 'description', 'branch_id', 'created_at', 'updated_at',
        ]));

        foreach (['receipt_image', 'receipt_path', 'attachment', 'file_path'] as $column) {
            $path = $expense->{$column} ?? null;

            if ($path) {
                $data['receipt_url'] = str_starts_with($path, 'http')
                    ? $path
                    : Storage::disk('public')->url($path);
                break;
            }
        }

        return $data;
    }
}
