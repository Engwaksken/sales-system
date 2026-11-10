<?php

namespace App\Services\Accounting;

use App\Models\AccountingJournal;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Sale;

/**
 * Posts standard double-entry journal entries for sales and expenses.
 *
 * IMPORTANT CONTEXT: the original PHP codebase has a full chart_of_accounts /
 * accounting_journals / journal_entries schema (and three reporting SQL views built
 * on top of them), but no code anywhere in the uploaded files actually *writes* to
 * those tables from a sale or expense. This service fills that gap with a standard,
 * reasonable double-entry convention — it is new logic, not a port of something that
 * existed, and the account-code mapping below should be reviewed/adjusted for your
 * actual bookkeeping practice rather than trusted blindly.
 *
 * Sale posting (journal_type = SJ) — the whole invoice goes to receivables:
 *   Dr  Accounts Receivable (1100)              total_amount
 *   Dr  Discount Allowed (4900)                 discount        [if > 0]
 *   Cr  VAT Payable (2100)                      vat             [if > 0]
 *   Cr  Sales Revenue (4000)                    total_amount + discount - vat
 *
 * Payment posting (journal_type = CRJ) — one per payment/receipt, so part
 * payments, split tenders and credit settled later all clear receivables:
 *   Dr  Cash (branch) / Mobile Money (1010)     amount
 *   Cr  Accounts Receivable (1100)              amount
 *
 * Expense posting (journal_type = CPJ):
 *   Dr  <mapped expense account>                amount
 *   Cr  Cash (branch-specific)                  amount
 */
class JournalPostingService
{
    /** expense_categories.name (lowercased) => chart_of_accounts.account_code */
    protected array $expenseAccountMap = [
        'transportation' => '6300',
        'rent' => '6100',
        'utilities' => '6200',
        'salaries' => '6000',
        'supplies' => '6500',
        'marketing' => '6400',
    ];

    protected function cashAccountFor(?int $branchId): string
    {
        return $branchId === 2 ? '1001' : '1000';
    }

    public function postForSale(Sale $sale): AccountingJournal
    {
        $total = (float) $sale->total_amount;
        $vat = (float) $sale->vat;
        $discount = (float) $sale->discount;

        $debitAccount = '1100';

        $journal = AccountingJournal::create([
            'journal_no' => 'SJ-'.$sale->id.'-'.now()->format('YmdHis'),
            'journal_type' => 'SJ',
            'reference_no' => $sale->invoice_no,
            'reference_type' => 'sale',
            'reference_id' => $sale->id,
            'journal_date' => $sale->sale_date ?? now(),
            'description' => "Sale #{$sale->id}".($sale->invoice_no ? " (Invoice {$sale->invoice_no})" : ''),
            'branch_id' => $sale->branch_id,
            'is_posted' => true,
        ]);

        $journal->journal_entries()->create([
            'account_code' => $debitAccount,
            'description' => 'Amount receivable for sale',
            'debit' => $total,
            'credit' => 0,
        ]);

        if ($discount > 0) {
            $journal->journal_entries()->create([
                'account_code' => '4900',
                'description' => 'Discount allowed',
                'debit' => $discount,
                'credit' => 0,
            ]);
        }

        if ($vat > 0) {
            $journal->journal_entries()->create([
                'account_code' => '2100',
                'description' => 'VAT on sale',
                'debit' => 0,
                'credit' => $vat,
            ]);
        }

        $revenue = round($total + $discount - $vat, 2);
        $journal->journal_entries()->create([
            'account_code' => '4000',
            'description' => 'Sales revenue',
            'debit' => 0,
            'credit' => $revenue,
        ]);

        return $journal;
    }

    public function postForPayment(Payment $payment): ?AccountingJournal
    {
        $amount = round((float) $payment->amount, 2);
        if ($amount <= 0 || ! $payment->sale_id) {
            return null;
        }

        $sale = $payment->sale;
        $method = strtolower((string) $payment->method);
        $debitAccount = str_contains($method, 'mobile') ? '1010' : $this->cashAccountFor($sale?->branch_id);

        $journal = AccountingJournal::create([
            'journal_no' => 'CRJ-'.$payment->id.'-'.now()->format('YmdHis'),
            'journal_type' => 'CRJ',
            'reference_no' => $payment->receipt_no,
            'reference_type' => 'payment',
            'reference_id' => $payment->id,
            'journal_date' => $payment->created_at ?? now(),
            'description' => "Receipt {$payment->receipt_no} for invoice {$payment->invoice_no} ({$payment->method})",
            'branch_id' => $sale?->branch_id,
            'is_posted' => true,
        ]);

        $journal->journal_entries()->create([
            'account_code' => $debitAccount,
            'description' => "{$payment->method} received",
            'debit' => $amount,
            'credit' => 0,
        ]);

        $journal->journal_entries()->create([
            'account_code' => '1100',
            'description' => 'Customer payment against invoice '.$payment->invoice_no,
            'debit' => 0,
            'credit' => $amount,
        ]);

        return $journal;
    }

    /** Removes a payment's journal, e.g. when the payment is deleted or marked failed. */
    public function reverseForPayment(Payment $payment): void
    {
        AccountingJournal::query()
            ->where('reference_type', 'payment')
            ->where('reference_id', $payment->id)
            ->get()
            ->each(function (AccountingJournal $journal) {
                $journal->journal_entries()->delete();
                $journal->delete();
            });
    }

    public function postForExpense(Expense $expense): AccountingJournal
    {
        $accountCode = $this->expenseAccountMap[strtolower((string) $expense->category)] ?? '6500';

        $journal = AccountingJournal::create([
            'journal_no' => 'CPJ-'.$expense->id.'-'.now()->format('YmdHis'),
            'journal_type' => 'CPJ',
            'reference_no' => (string) $expense->id,
            'reference_type' => 'expense',
            'reference_id' => $expense->id,
            'journal_date' => $expense->expense_date ?? now(),
            'description' => $expense->title,
            'branch_id' => $expense->branch_id,
            'is_posted' => true,
        ]);

        $journal->journal_entries()->create([
            'account_code' => $accountCode,
            'description' => $expense->title,
            'debit' => (float) $expense->amount,
            'credit' => 0,
        ]);

        $journal->journal_entries()->create([
            'account_code' => $this->cashAccountFor($expense->branch_id),
            'description' => 'Cash paid for: '.$expense->title,
            'debit' => 0,
            'credit' => (float) $expense->amount,
        ]);

        return $journal;
    }
}
