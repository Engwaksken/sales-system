<?php

namespace App\Services;

use App\Models\OrderLog;
use App\Models\Sale;
use App\Models\Stock;
use App\Support\DocumentNumber;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Counter sales and later payments against them. Shared by the website POS and
 * the mobile staff app so both follow the same stock and money rules.
 */
class PosSaleService
{
    public function __construct(private SalePaymentService $payments)
    {
    }

    /**
     * Sells the given items from one branch's stock and records the tenders.
     *
     * @param  array{branch_id: int, customer_id?: ?int, discount?: mixed, vat?: mixed,
     *               items: array<int, array{product_id: int, quantity: int}>,
     *               payments?: array, payment_method?: ?string}  $data
     * @param  bool  $legacyFullPayment  older clients send one method and expect full payment
     * @return array{0: Sale, 1: Collection} the sale and its receipts
     */
    public function create(array $data, ?int $userId, bool $legacyFullPayment = false): array
    {
        $discount = (float) ($data['discount'] ?? 0);
        $vat = (float) ($data['vat'] ?? 0);
        $tenders = $this->payments->normalise($data['payments'] ?? []);
        $customerId = $data['customer_id'] ?? null;

        return ActivityLogger::during('pos_sale', fn () => DB::transaction(function () use ($data, $discount, $vat, $legacyFullPayment, $tenders, $customerId, $userId) {
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

            // Checked against the server-side total, not what the client displayed.
            $this->payments->assertTendersFit($totalAmount, $tenders, $customerId);

            $sale = Sale::create([
                'transaction_type' => 'sale',
                'customer_id' => $customerId,
                'user_id' => $userId,
                'invoice_no' => DocumentNumber::invoice(),
                'total_amount' => $totalAmount,
                'discount' => $discount,
                'vat' => $vat,
                'sale_date' => now()->format('Y-m-d'),
                'branch_id' => $data['branch_id'],
            ] + $this->payments->openingState($totalAmount, $tenders));

            foreach ($lines as $line) {
                $sale->sale_items()->create([
                    'product_id' => $line['product_id'],
                    'quantity' => $line['quantity'],
                    'price' => $line['price'],
                    'vat_amount' => 0,
                    'total' => $line['total'],
                ]);
            }

            return [$sale, $this->payments->record($sale, $tenders)];
        }));
    }

    /**
     * Records one or more tenders against an existing sale (e.g. paying off credit).
     * Each tender gets its own receipt; overpaying or paying a settled sale is refused.
     *
     * @param  array<int, array{method: string, amount: float}>  $tenders  already normalised
     */
    public function recordPayment(Sale $sale, array $tenders): Collection
    {
        if ($tenders === []) {
            throw ValidationException::withMessages(['payment' => 'Enter at least one payment amount.']);
        }

        return ActivityLogger::during('sale_payment', fn () => DB::transaction(function () use ($sale, $tenders) {
            $locked = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();
            $alreadyPaid = (float) $locked->payments()->where('status', 'successful')->sum('amount');

            if ((float) $locked->total_amount - $alreadyPaid <= 0.005) {
                throw ValidationException::withMessages(['payment' => 'This sale is already fully paid.']);
            }

            // Paying towards an existing balance is always allowed (walk-in sales
            // from before this rule included); only overpaying is rejected.
            $this->payments->assertTendersFit((float) $locked->total_amount, $tenders, $locked->customer_id ?: -1, $alreadyPaid);

            $receipts = $this->payments->record($locked, $tenders);
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
        }));
    }
}
