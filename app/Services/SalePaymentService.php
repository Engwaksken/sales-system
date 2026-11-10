<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Payment;
use App\Models\Sale;
use App\Support\DocumentNumber;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * One place for the money rules of a sale:
 *  - a sale can be paid with several tenders (cash + mobile money, ...); every tender
 *    is its own Payment row and therefore its own receipt;
 *  - anything less than the full total (part payment or nothing at all, i.e. credit)
 *    is only allowed when the sale belongs to a registered customer;
 *  - the sale's paid / balance / status columns are always derived from its
 *    successful payments, so editing or deleting a payment keeps them right.
 */
class SalePaymentService
{
    public const METHODS = ['Cash', 'Mobile Money', 'Card', 'Bank Transfer', 'Cheque'];

    /** Tolerance for decimal rounding between the browser and the database. */
    private const EPSILON = 0.005;

    /**
     * Normalises submitted tenders, dropping empty rows.
     *
     * @param  array<int, array{method?: mixed, amount?: mixed}>  $tenders
     * @return array<int, array{method: string, amount: float}>
     */
    public function normalise(array $tenders): array
    {
        return collect($tenders)
            ->map(fn ($t) => [
                'method' => trim((string) ($t['method'] ?? '')),
                'amount' => round((float) ($t['amount'] ?? 0), 2),
            ])
            ->filter(fn ($t) => $t['amount'] > 0)
            ->values()
            ->all();
    }

    /**
     * Throws a validation error when the tenders don't fit the sale.
     *
     * @param  array<int, array{method: string, amount: float}>  $tenders
     */
    public function assertTendersFit(float $total, array $tenders, ?int $customerId, float $alreadyPaid = 0.0): void
    {
        $paying = array_sum(array_column($tenders, 'amount'));
        $outstanding = max(round($total - $alreadyPaid, 2), 0);

        if ($paying > $outstanding + self::EPSILON) {
            throw ValidationException::withMessages([
                'payments' => 'Payments total UGX '.number_format($paying, 0).' but only UGX '.number_format($outstanding, 0).' is due.',
            ]);
        }

        if ($paying + self::EPSILON < $outstanding && ! $customerId) {
            throw ValidationException::withMessages([
                'customer_id' => 'Part payments and credit sales are only allowed for registered customers. Select a customer or collect the full amount.',
            ]);
        }
    }

    /**
     * Columns a new sale starts with, so the invoice emailed and journal posted on
     * creation already reflect what is being paid at the till.
     *
     * @param  array<int, array{method: string, amount: float}>  $tenders
     */
    public function openingState(float $total, array $tenders): array
    {
        $paid = round(array_sum(array_column($tenders, 'amount')), 2);
        $balance = max(round($total - $paid, 2), 0);
        $methods = array_values(array_unique(array_filter(array_column($tenders, 'method'))));

        return [
            'payment_method' => match (count($methods)) {
                0 => 'Credit',
                1 => $methods[0],
                default => mb_substr('Split ('.implode(' + ', $methods).')', 0, 50),
            },
            'payment_status' => $balance <= self::EPSILON ? 'paid' : ($paid > 0 ? 'partial' : 'pending'),
            'partial_amount' => $paid,
            'balance_amount' => $balance,
        ];
    }

    /**
     * Creates one Payment (and receipt) per tender, then re-derives the sale's balances.
     *
     * @param  array<int, array{method: string, amount: float}>  $tenders
     * @return Collection<int, Payment>
     */
    public function record(Sale $sale, array $tenders): Collection
    {
        $payments = collect($tenders)->map(fn ($tender) => Payment::create([
            'transaction_type' => 'sale',
            'invoice_no' => (string) $sale->invoice_no,
            'receipt_no' => DocumentNumber::receipt(),
            'customer_id' => $sale->customer_id,
            'supplier_id' => $sale->supplier_id,
            'sale_id' => $sale->id,
            'amount' => $tender['amount'],
            'method' => $tender['method'],
            'status' => 'successful',
            'received_by' => auth()->id(),
        ]));

        $this->sync($sale);

        return $payments;
    }

    /** Recomputes paid / balance / status on the sale and any linked online orders. */
    public function sync(Sale $sale): Sale
    {
        $paid = round((float) $sale->payments()->where('status', 'successful')->sum('amount'), 2);
        $total = round((float) $sale->total_amount, 2);
        $balance = max(round($total - $paid, 2), 0);

        $status = match (true) {
            $balance <= self::EPSILON => 'paid',
            $paid > 0 => 'partial',
            default => 'pending',
        };

        $sale->forceFill([
            'partial_amount' => $paid,
            'balance_amount' => $balance,
            'payment_status' => $status,
            'payment_method' => $this->methodLabel($sale),
        ])->saveQuietly();

        foreach ($sale->orders()->get() as $order) {
            $order->update([
                'amount_paid' => $paid,
                'balance_amount' => $balance,
                'payment_status' => $status,
                'paid_at' => $status === 'paid' ? ($order->paid_at ?? now()) : null,
                'status' => $status === 'paid' ? 'completed' : $order->status,
            ]);
        }

        return $sale;
    }

    /** "Cash", "Split (Cash + Mobile Money)" or "Credit" when nothing has been paid. */
    public function methodLabel(Sale $sale): string
    {
        $methods = $sale->payments()->where('status', 'successful')->orderBy('id')->pluck('method')
            ->map(fn ($m) => trim((string) $m))->filter()->unique()->values();

        return match ($methods->count()) {
            0 => 'Credit',
            1 => $methods->first(),
            default => mb_substr('Split ('.$methods->join(' + ').')', 0, 50),
        };
    }

    /** What a registered customer still owes across all their sales. */
    public static function customerOutstanding(Customer|int|null $customer): float
    {
        $id = $customer instanceof Customer ? $customer->id : $customer;
        if (! $id) {
            return 0.0;
        }

        return round((float) Sale::query()
            ->where('customer_id', $id)
            ->where('transaction_type', 'sale')
            ->sum('balance_amount'), 2);
    }
}
