<?php

namespace App\Observers;

use App\Models\Expense;
use App\Services\Accounting\JournalPostingService;
use Throwable;

class ExpenseObserver
{
    public function created(Expense $expense): void
    {
        try {
            app(JournalPostingService::class)->postForExpense($expense);
        } catch (Throwable $e) {
            report($e);
        }
    }
}
