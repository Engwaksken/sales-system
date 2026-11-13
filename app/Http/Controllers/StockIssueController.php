<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\Product;
use App\Models\Stock;
use App\Models\StockIssue;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StockIssueController extends Controller
{
    public function index()
    {
        return view('stock-issues.index', [
            'issues' => StockIssue::with(['product', 'branch', 'user'])->latest()->paginate(25),
            'products' => Product::orderBy('name')->get(),
            'branches' => Branch::orderBy('name')->get(),
            'types' => ['damage' => 'Damages', 'gratis' => 'Gratis', 'staff_consumption' => 'Staff consumption'],
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'branch_id' => ['required', 'exists:branches,id'],
            'type' => ['required', 'in:damage,gratis,staff_consumption'],
            'quantity' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:1000'],
        ]);

        DB::transaction(function () use ($data, $request) {
            $stock = Stock::query()->where('product_id', $data['product_id'])
                ->where('branch_id', $data['branch_id'])->orderBy('id')->lockForUpdate()->first();

            if (! $stock || $stock->quantity < $data['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => 'Insufficient stock for this product at the selected branch.',
                ]);
            }

            $stock->decrement('quantity', $data['quantity']);
            StockIssue::create($data + ['user_id' => $request->user()->id]);
        });

        return redirect()->route('stock-issues.index')->with('status', 'Stock issue recorded; no sale or cash entry was created.');
    }
}
