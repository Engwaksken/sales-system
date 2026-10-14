<?php

namespace App\Http\Controllers\Api\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class ExpenseController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Expense::query();

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
        return response()->json(['data' => $this->payload($expense)]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validateExpense($request);
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
        $data = $this->filterColumns($this->validateExpense($request));

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
        $expense->delete();

        return response()->json(['message' => 'Expense deleted successfully.']);
    }

    private function validateExpense(Request $request): array
    {
        return $request->validate([
            'expense_date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string', 'max:2000'],
            'payee' => ['nullable', 'string', 'max:255'],
            'vendor' => ['nullable', 'string', 'max:255'],
            'reference_no' => ['nullable', 'string', 'max:255'],
            'receipt_no' => ['nullable', 'string', 'max:255'],
            'payment_method' => ['nullable', 'string', 'max:100'],
            'category' => ['nullable', 'string', 'max:150'],
            'category_id' => ['nullable', 'integer'],
            'branch_id' => ['nullable', 'integer'],
            'receipt_image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:10240'],
        ]);
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
        $data = $expense->toArray();

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
