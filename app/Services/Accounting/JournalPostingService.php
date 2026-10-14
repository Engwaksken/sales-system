<?php

namespace App\Services\Accounting;

use App\Models\AccountingJournal;
use App\Models\Expense;
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
 * Sale posting (journal_type = SJ):
 *   Dr  Cash/Mobile Money/Accounts Receivable   total_amount
 *   Dr  Discount Allowed (4900)                 discount        [if > 0]
 *   Cr  VAT Payable (2100)                      vat             [if > 0]
 *   Cr  Sales Revenue (4000)                    total_amount + discount - vat
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

        $method = strtolower((string) $sale->payment_method);
        $debitAccount = match (true) {
            str_contains($method, 'mobile') => '1010',
            $sale->payment_status === 'pending' || $sale->payment_status === 'partial' => '1100',
            default => $this->cashAccountFor($sale->branch_id),
        };

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
            'description' => 'Amount received/receivable for sale',
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
