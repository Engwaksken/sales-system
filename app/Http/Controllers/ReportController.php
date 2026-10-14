<?php

namespace App\Http\Controllers;

use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\Customer;
use App\Models\Expense;
use App\Models\Product;
use App\Models\Sale;
use App\Models\Stock;
use App\Models\Supplier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function show(Request $request, string $type = 'overview')
    {
        [$from, $to] = $this->resolveDates($request);

        $branchId = $request->filled('branch_id')
            ? $request->integer('branch_id')
            : null;

        $supplierId = $request->filled('supplier_id')
            ? $request->integer('supplier_id')
            : null;

        $data = match ($type) {
            'sales' => $this->sales($from, $to, $branchId, $supplierId),
            'purchases' => $this->purchases($from, $to, $branchId, $supplierId),
            'expenses' => $this->expenses($from, $to, $branchId, $supplierId),
            'stock' => $this->stock($branchId, $supplierId),
            'expiry' => $this->expiry($branchId, $supplierId),
            'products' => $this->products($branchId, $supplierId),
            'customers' => $this->customers($branchId),
            'suppliers' => $this->suppliers($branchId, $supplierId),
            'profit' => $this->profit($from, $to, $branchId, $supplierId),
            'trial_balance' => $this->trialBalance(),
            'income_statement' => $this->incomeStatement($from, $to),
            'general_ledger' => $this->generalLedger($from, $to, $branchId, $request),
            default => $this->overview($from, $to, $branchId, $supplierId),
        };

        if (isset($data['rows']) && $data['rows'] instanceof \Illuminate\Support\Collection) {
            $data['rows'] = $this->paginateRows(
                $data['rows'],
                $request,
                $this->perPage($request)
            );
        }

        return view('reports.show', [
            'type' => $type,
            'from' => $from,
            'to' => $to,
            'branch_options' => Branch::query()->orderBy('name')->get(),
            'supplier_options' => Supplier::query()->orderBy('name')->get(),
            'reportTypes' => [
                'overview',
                'sales',
                'purchases',
                'expenses',
                'stock',
                'expiry',
                'products',
                'customers',
                'suppliers',
                'profit',
                'trial_balance',
                'income_statement',
                'general_ledger',
            ],
            'currency' => 'UGX',
        ] + $data);
    }


    private function paginateRows(
        Collection $rows,
        Request $request,
        int $perPage = 20
    ): LengthAwarePaginator {
        $page = max((int) $request->query('page', 1), 1);
        $total = $rows->count();

        $slice = $rows
            ->forPage($page, $perPage)
            ->values();

        return new LengthAwarePaginator(
            $slice,
            $total,
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );
    }

    private function resolveDates(Request $request): array
    {
        $from = $request->filled('from')
            ? Carbon::parse($request->query('from'))->startOfDay()
            : now()->startOfMonth()->startOfDay();

        $to = $request->filled('to')
            ? Carbon::parse($request->query('to'))->endOfDay()
            : now()->endOfDay();

        if ($to->lt($from)) {
            [$from, $to] = [$to->copy()->startOfDay(), $from->copy()->endOfDay()];
        }

        return [$from, $to];
    }

    private function salesQuery(
        Carbon $from,
        Carbon $to,
        ?int $branchId,
        ?int $supplierId
    ): Builder {
        return Sale::query()
            ->whereBetween('sale_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId));
    }

    private function stockQuery(?int $branchId, ?int $supplierId): Builder
    {
        return Stock::query()
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($supplierId, fn ($q) => $q->where('supplier_id', $supplierId));
    }

    private function expenseQuery(
        Carbon $from,
        Carbon $to,
        ?int $branchId,
        ?int $supplierId
    ): Builder {
        return Expense::query()
            ->whereBetween('expense_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when(
                $supplierId,
                fn ($q) => $q->where('supplier_id', $supplierId)
            );
    }

    private function sales(
        Carbon $from,
        Carbon $to,
        ?int $branchId,
        ?int $supplierId
    ): array {
        $sales = $this->salesQuery($from, $to, $branchId, $supplierId)
            ->where('transaction_type', 'sale')
            ->with(['customer', 'supplier', 'branch', 'user', 'sale_items.product'])
            ->latest('sale_date')
            ->get();

        $rows = $sales->map(fn ($sale) => [
            'invoice' => $sale->invoice_no ?: ('Sale #'.$sale->id),
            'date' => optional($sale->sale_date)->format('d M Y'),
            'customer' => $sale->customer?->name ?? 'Walk-in Customer',
            'supplier' => $sale->supplier?->name ?? '—',
            'branch' => $sale->branch?->name ?? '—',
            'products' => $sale->sale_items
                ->map(fn ($item) => $item->product?->name)
                ->filter()
                ->unique()
                ->join(', ') ?: '—',
            'payment_method' => $sale->payment_method ?: '—',
            'payment_status' => ucfirst((string) $sale->payment_status),
            'total_amount' => (float) $sale->total_amount,
            'paid' => (float) ($sale->partial_amount ?? 0),
            'balance' => (float) ($sale->balance_amount ?? 0),
        ]);

        return [
            'rows' => $rows,
            'headers' => [
                'Invoice', 'Date', 'Customer', 'Supplier', 'Branch',
                'Products', 'Payment Method', 'Payment Status',
                'Total Amount', 'Paid', 'Balance',
            ],
            'moneyColumns' => ['total_amount', 'paid', 'balance'],
        ];
    }

    private function purchases(
        Carbon $from,
        Carbon $to,
        ?int $branchId,
        ?int $supplierId
    ): array {
        $rows = $this->salesQuery($from, $to, $branchId, $supplierId)
            ->where('transaction_type', 'purchase')
            ->with(['supplier', 'branch', 'user'])
            ->latest('sale_date')
            ->get()
            ->map(fn ($sale) => [
                'reference' => $sale->invoice_no ?: ('Purchase #'.$sale->id),
                'date' => optional($sale->sale_date)->format('d M Y'),
                'supplier' => $sale->supplier?->name ?? '—',
                'branch' => $sale->branch?->name ?? '—',
                'payment_method' => $sale->payment_method ?: '—',
                'total_amount' => (float) $sale->total_amount,
            ]);

        return [
            'rows' => $rows,
            'headers' => ['Reference', 'Date', 'Supplier', 'Branch', 'Payment Method', 'Total Amount'],
            'moneyColumns' => ['total_amount'],
        ];
    }

    private function expenses(
        Carbon $from,
        Carbon $to,
        ?int $branchId,
        ?int $supplierId
    ): array {
        $rows = $this->expenseQuery($from, $to, $branchId, $supplierId)
            ->with(['branch', 'supplier'])
            ->latest('expense_date')
            ->get()
            ->map(fn ($expense) => [
                'title' => $expense->title,
                'date' => optional($expense->expense_date)->format('d M Y'),
                'category' => $expense->category ?: '—',
                'branch' => $expense->branch?->name ?? '—',
                'supplier' => $expense->supplier?->name ?? '—',
                'amount' => (float) $expense->amount,
                'description' => $expense->description ?: '—',
            ]);

        return [
            'rows' => $rows,
            'headers' => ['Title', 'Date', 'Category', 'Branch', 'Supplier', 'Amount', 'Description'],
            'moneyColumns' => ['amount'],
        ];
    }

    private function stock(?int $branchId, ?int $supplierId): array
    {
        $rows = $this->stockQuery($branchId, $supplierId)
            ->with(['product', 'branch', 'supplier'])
            ->orderBy('product_id')
            ->get()
            ->map(fn ($stock) => [
                'product' => $stock->product?->name ?? '—',
                'sku' => $stock->product?->sku ?? '—',
                'supplier' => $stock->supplier?->name ?? '—',
                'branch' => $stock->branch?->name ?? '—',
                'quantity' => (int) $stock->quantity,
                'unit' => $stock->unit_type ?: '—',
                'cost_price' => (float) $stock->cost_price,
                'selling_price' => (float) $stock->selling_price,
                'expiry_date' => optional($stock->expiry_date)->format('d M Y') ?: '—',
            ]);

        return [
            'rows' => $rows,
            'headers' => [
                'Product', 'SKU', 'Supplier', 'Branch', 'Quantity',
                'Unit', 'Cost Price', 'Selling Price', 'Expiry Date',
            ],
            'moneyColumns' => ['cost_price', 'selling_price'],
        ];
    }

    private function expiry(?int $branchId, ?int $supplierId): array
    {
        $rows = $this->stockQuery($branchId, $supplierId)
            ->with(['product', 'branch', 'supplier'])
            ->whereNotNull('expiry_date')
            ->orderBy('expiry_date')
            ->get()
            ->map(function ($stock) {
                $daysLeft = now()->startOfDay()->diffInDays($stock->expiry_date, false);

                return [
                    'product' => $stock->product?->name ?? '—',
                    'supplier' => $stock->supplier?->name ?? '—',
                    'branch' => $stock->branch?->name ?? '—',
                    'quantity' => (int) $stock->quantity,
                    'expiry_date' => optional($stock->expiry_date)->format('d M Y') ?: '—',
                    'status' => $daysLeft < 0
                        ? 'Expired'
                        : ($daysLeft <= 30 ? 'Expiring Soon' : 'OK'),
                ];
            });

        return [
            'rows' => $rows,
            'headers' => ['Product', 'Supplier', 'Branch', 'Quantity', 'Expiry Date', 'Status'],
        ];
    }

    private function products(?int $branchId, ?int $supplierId): array
    {
        $rows = Product::query()
            ->with(['branch', 'category'])
            ->withCount('sale_items')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when(
                $supplierId,
                fn ($q) => $q->whereHas(
                    'stock',
                    fn ($stock) => $stock->where('supplier_id', $supplierId)
                )
            )
            ->orderBy('name')
            ->get()
            ->map(fn ($product) => [
                'product' => $product->name,
                'sku' => $product->sku ?: '—',
                'category' => $product->category?->category_name ?? '—',
                'branch' => $product->branch?->name ?? '—',
                'sales_items' => (int) $product->sale_items_count,
                'active' => $product->is_active ? 'Yes' : 'No',
            ]);

        return [
            'rows' => $rows,
            'headers' => ['Product', 'SKU', 'Category', 'Branch', 'Sales Items', 'Active'],
        ];
    }

    private function customers(?int $branchId): array
    {
        $rows = Customer::query()
            ->with('branch')
            ->withCount('sales')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->orderBy('name')
            ->get()
            ->map(fn ($customer) => [
                'customer' => $customer->name,
                'phone' => $customer->phone ?: '—',
                'email' => $customer->email ?: '—',
                'branch' => $customer->branch?->name ?? '—',
                'sales' => (int) $customer->sales_count,
            ]);

        return [
            'rows' => $rows,
            'headers' => ['Customer', 'Phone', 'Email', 'Branch', 'Sales'],
        ];
    }

    private function suppliers(?int $branchId, ?int $supplierId): array
    {
        $rows = Supplier::query()
            ->with('branch')
            ->withCount('stock')
            ->when($branchId, fn ($q) => $q->where('branch_id', $branchId))
            ->when($supplierId, fn ($q) => $q->whereKey($supplierId))
            ->orderBy('name')
            ->get()
            ->map(fn ($supplier) => [
                'supplier' => $supplier->name,
                'phone' => $supplier->phone ?: '—',
                'email' => $supplier->email ?: '—',
                'branch' => $supplier->branch?->name ?? '—',
                'stock_records' => (int) $supplier->stock_count,
            ]);

        return [
            'rows' => $rows,
            'headers' => ['Supplier', 'Phone', 'Email', 'Branch', 'Stock Records'],
        ];
    }

    private function overview(
        Carbon $from,
        Carbon $to,
        ?int $branchId,
        ?int $supplierId
    ): array {
        $sales = $this->salesQuery($from, $to, $branchId, $supplierId)
            ->where('transaction_type', 'sale');

        $expenses = $this->expenseQuery($from, $to, $branchId, $supplierId);

        return [
            'rows' => collect([
                ['metric' => 'Total Sales', 'value' => (float) $sales->sum('total_amount')],
                ['metric' => 'Total Expenses', 'value' => (float) $expenses->sum('amount')],
                ['metric' => 'Customers', 'value' => Customer::query()->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->count()],
                ['metric' => 'Products', 'value' => Product::query()->when($branchId, fn ($q) => $q->where('branch_id', $branchId))->count()],
            ]),
            'headers' => ['Metric', 'Value'],
            'moneyColumns' => ['value'],
        ];
    }

    private function profit(
        Carbon $from,
        Carbon $to,
        ?int $branchId,
        ?int $supplierId
    ): array {
        $revenue = (float) $this->salesQuery($from, $to, $branchId, $supplierId)
            ->where('transaction_type', 'sale')
            ->sum('total_amount');

        $expenses = (float) $this->expenseQuery($from, $to, $branchId, $supplierId)
            ->sum('amount');

        return [
            'rows' => collect([
                ['metric' => 'Revenue', 'amount' => $revenue],
                ['metric' => 'Expenses', 'amount' => $expenses],
                ['metric' => 'Net Profit', 'amount' => $revenue - $expenses],
            ]),
            'headers' => ['Metric', 'Amount'],
            'moneyColumns' => ['amount'],
        ];
    }

    private function trialBalance(): array
    {
        $rows = ChartOfAccount::query()
            ->leftJoin(
                'journal_entries',
                'journal_entries.account_code',
                '=',
                'chart_of_accounts.account_code'
            )
            ->groupBy(
                'chart_of_accounts.account_code',
                'chart_of_accounts.account_name',
                'chart_of_accounts.account_type',
                'chart_of_accounts.normal_balance'
            )
            ->select(
                'chart_of_accounts.account_code',
                'chart_of_accounts.account_name',
                'chart_of_accounts.account_type',
                'chart_of_accounts.normal_balance'
            )
            ->selectRaw('COALESCE(SUM(journal_entries.debit), 0) as total_debit')
            ->selectRaw('COALESCE(SUM(journal_entries.credit), 0) as total_credit')
            ->orderBy('chart_of_accounts.account_code')
            ->get()
            ->map(fn ($row) => [
                'account_code' => $row->account_code,
                'account_name' => $row->account_name,
                'account_type' => $row->account_type,
                'normal_balance' => $row->normal_balance,
                'debit' => (float) $row->total_debit,
                'credit' => (float) $row->total_credit,
            ]);

        return [
            'rows' => $rows,
            'headers' => ['Account Code', 'Account Name', 'Account Type', 'Normal Balance', 'Debit', 'Credit'],
            'moneyColumns' => ['debit', 'credit'],
        ];
    }

    private function incomeStatement(Carbon $from, Carbon $to): array
    {
        $rows = DB::table('chart_of_accounts as coa')
            ->leftJoin('journal_entries as je', 'je.account_code', '=', 'coa.account_code')
            ->leftJoin('accounting_journals as aj', 'aj.id', '=', 'je.journal_id')
            ->whereIn('coa.account_type', ['Revenue', 'COGS', 'Expense'])
            ->where(function ($q) use ($from, $to) {
                $q->whereNull('aj.id')
                    ->orWhereBetween('aj.journal_date', [$from, $to]);
            })
            ->groupBy('coa.account_type', 'coa.account_code', 'coa.account_name')
            ->select(
                'coa.account_type as section',
                'coa.account_code',
                'coa.account_name'
            )
            ->selectRaw('COALESCE(SUM(je.credit - je.debit), 0) as net_amount')
            ->orderBy('coa.account_type')
            ->orderBy('coa.account_code')
            ->get()
            ->map(fn ($row) => [
                'section' => $row->section,
                'account_code' => $row->account_code,
                'account_name' => $row->account_name,
                'net_amount' => (float) $row->net_amount,
            ]);

        return [
            'rows' => $rows,
            'headers' => ['Section', 'Account Code', 'Account Name', 'Net Amount'],
            'moneyColumns' => ['net_amount'],
        ];
    }

    private function generalLedger(
        Carbon $from,
        Carbon $to,
        ?int $branchId,
        Request $request
    ): array {
        $accountCode = trim((string) $request->query('account_code', ''));
        $journalType = trim((string) $request->query('journal_type', ''));
        $search = trim((string) $request->query('q', ''));

        /*
         * Keep this report FLAT.
         * Do not call Eloquent ->toArray() on journal relations because nested
         * arrays are what caused the previous "Array to string conversion"/500.
         */
        $query = DB::table('journal_entries as je')
            ->join('accounting_journals as aj', 'aj.id', '=', 'je.journal_id')
            ->leftJoin('chart_of_accounts as coa', 'coa.account_code', '=', 'je.account_code')
            ->leftJoin('branches as b', 'b.id', '=', 'aj.branch_id')
            ->whereBetween('aj.journal_date', [$from, $to])
            ->when($branchId, fn ($q) => $q->where('aj.branch_id', $branchId))
            ->when($accountCode !== '', fn ($q) => $q->where('je.account_code', $accountCode))
            ->when($journalType !== '', fn ($q) => $q->where('aj.journal_type', $journalType))
            ->when($search !== '', function ($q) use ($search) {
                $like = '%'.$search.'%';

                $q->where(function ($sub) use ($like) {
                    $sub->where('aj.journal_no', 'like', $like)
                        ->orWhere('aj.reference_no', 'like', $like)
                        ->orWhere('je.account_code', 'like', $like)
                        ->orWhere('coa.account_name', 'like', $like)
                        ->orWhere('je.description', 'like', $like);
                });
            });

        $rawRows = (clone $query)
            ->select([
                'je.id',
                'aj.journal_date',
                'aj.journal_no',
                'aj.journal_type',
                'aj.reference_no',
                'je.account_code',
                'coa.account_name',
                'b.name as branch_name',
                'je.description',
                'je.debit',
                'je.credit',
            ])
            ->orderBy('aj.journal_date')
            ->orderBy('aj.id')
            ->orderBy('je.id')
            ->get();

        $openingBalance = (float) DB::table('journal_entries as je')
            ->join('accounting_journals as aj', 'aj.id', '=', 'je.journal_id')
            ->where('aj.journal_date', '<', $from)
            ->when($branchId, fn ($q) => $q->where('aj.branch_id', $branchId))
            ->when($accountCode !== '', fn ($q) => $q->where('je.account_code', $accountCode))
            ->selectRaw('COALESCE(SUM(je.debit - je.credit), 0) as opening')
            ->value('opening');

        $running = $openingBalance;

        $rows = $rawRows->map(function ($row) use (&$running) {
            $debit = (float) $row->debit;
            $credit = (float) $row->credit;
            $running += $debit - $credit;

            return [
                'date' => Carbon::parse($row->journal_date)->format('d M Y'),
                'journal' => $row->journal_no ?: '—',
                'journal_type' => str_replace('_', ' ', ucfirst((string) $row->journal_type)),
                'reference' => $row->reference_no ?: '—',
                'account_code' => $row->account_code,
                'account_name' => $row->account_name ?: '—',
                'branch' => $row->branch_name ?: '—',
                'description' => $row->description ?: '—',
                'debit' => $debit,
                'credit' => $credit,
                'balance' => $running,
            ];
        });

        return [
            'rows' => $rows,
            'headers' => [
                'Date', 'Journal', 'Journal Type', 'Reference',
                'Account Code', 'Account Name', 'Branch',
                'Description', 'Debit', 'Credit', 'Balance',
            ],
            'moneyColumns' => ['debit', 'credit', 'balance'],
            'ledgerSummary' => [
                'entries' => $rows->count(),
                'opening_balance' => $openingBalance,
                'total_debit' => (float) $rawRows->sum('debit'),
                'total_credit' => (float) $rawRows->sum('credit'),
                'closing_balance' => $running,
            ],
            'accountOptions' => ChartOfAccount::query()
                ->where('is_active', true)
                ->orderBy('account_code')
                ->get(['account_code', 'account_name']),
            'journalTypeOptions' => DB::table('accounting_journals')
                ->whereNotNull('journal_type')
                ->distinct()
                ->orderBy('journal_type')
                ->pluck('journal_type'),
            'selectedAccountCode' => $accountCode,
            'selectedJournalType' => $journalType,
            'ledgerSearch' => $search,
        ];
    }
}
