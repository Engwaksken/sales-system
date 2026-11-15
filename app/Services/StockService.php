<?php

namespace App\Services;

use App\Models\Stock;
use App\Models\StockIssue;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/** Receiving stock and taking it out without a sale; shared by the website and the mobile app. */
class StockService
{
    /**
     * Adds stock for a product to each branch. One row per product, branch and supplier:
     * sales read the first row, so an existing row is topped up instead of duplicated.
     *
     * @param  array<string, mixed>  $data  validated stock fields (no branch keys)
     * @param  array<int, int>  $branchIds
     * @return array{added: int, topped_up: int}
     */
    public function receive(array $data, array $branchIds): array
    {
        // When the stock was received; defaults to right now (app time zone: Kampala).
        $data['stock_date'] = filled($data['stock_date'] ?? null)
            ? Carbon::parse($data['stock_date'])
            : now();

        $added = $toppedUp = 0;

        // A plain closure, not fn(): the counters must be shared by reference.
        ActivityLogger::during('stock_received', function () use ($data, $branchIds, &$added, &$toppedUp) {
            DB::transaction(function () use ($data, $branchIds, &$added, &$toppedUp): void {
            foreach ($branchIds as $branchId) {
                $stock = Stock::query()
                    ->where('product_id', $data['product_id'])
                    ->where('branch_id', $branchId)
                    ->when($data['supplier_id'] ?? null, fn ($q, $id) => $q->where('supplier_id', $id), fn ($q) => $q->whereNull('supplier_id'))
                    ->lockForUpdate()
                    ->first();

                if ($stock) {
                    $stock->fill(array_filter([
                        'unit_type' => $data['unit_type'] ?? null,
                        'cost_price' => $data['cost_price'] ?? null,
                        'selling_price' => $data['selling_price'] ?? null,
                        'expiry_date' => $data['expiry_date'] ?? null,
                        'stock_date' => $data['stock_date'],
                    ], fn ($value) => $value !== null));
                    $stock->quantity = (float) $stock->quantity + (float) $data['quantity'];
                    $stock->total_stock = (float) $stock->total_stock + (float) $data['total_stock'];
                    $stock->save();
                    $toppedUp++;
                } else {
                    Stock::create($data + ['branch_id' => $branchId]);
                    $added++;
                }
            }
            });
        });

        return ['added' => $added, 'topped_up' => $toppedUp];
    }

    /**
     * Takes stock out for damages, gratis or staff consumption. No sale or cash entry is made.
     *
     * @param  array{product_id: int, branch_id: int, type: string, quantity: int, reason?: ?string}  $data
     */
    public function issue(array $data, int $userId): StockIssue
    {
        return ActivityLogger::during('stock_issue', fn () => DB::transaction(function () use ($data, $userId) {
            $stock = Stock::query()->where('product_id', $data['product_id'])
                ->where('branch_id', $data['branch_id'])->orderBy('id')->lockForUpdate()->first();

            if (! $stock || $stock->quantity < $data['quantity']) {
                throw ValidationException::withMessages([
                    'quantity' => 'Insufficient stock for this product at the selected branch.',
                ]);
            }

            $stock->decrement('quantity', $data['quantity']);

            return StockIssue::create($data + ['user_id' => $userId]);
        }));
    }
}
