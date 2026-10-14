@extends('layouts.app')

@section('title', 'Report: '.str($type)->replace('_', ' ')->title())

@section('content')
<div class="mb-5 flex flex-col gap-4 xl:flex-row xl:items-end xl:justify-between">
    <div>
        <h1 class="text-xl font-black text-slate-800">
            <i class="fas fa-chart-column mr-2 text-indigo-600"></i>
            {{ str($type)->replace('_', ' ')->title() }} Report
        </h1>
        <p class="mt-1 text-sm text-slate-500">
            Reporting period:
            {{ $from->format('d M Y') }} – {{ $to->format('d M Y') }}
        </p>
    </div>

    <form method="GET" action="{{ route('reports.show', $type) }}"
          class="grid gap-2 rounded-xl border border-slate-200 bg-white p-3 shadow-sm sm:grid-cols-2 xl:grid-cols-4">
        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500">From</label>
            <input type="date" name="from" value="{{ $from->format('Y-m-d') }}"
                   class="w-full rounded-lg border-slate-300 text-sm">
        </div>

        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500">To</label>
            <input type="date" name="to" value="{{ $to->format('Y-m-d') }}"
                   class="w-full rounded-lg border-slate-300 text-sm">
        </div>

        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500">
                <i class="fas fa-building mr-1"></i> Branch Name
            </label>
            <select name="branch_id" class="w-full rounded-lg border-slate-300 text-sm">
                <option value="">All Branches</option>
                @foreach($branch_options as $branch)
                    <option value="{{ $branch->id }}" @selected((string)request('branch_id') === (string)$branch->id)>
                        {{ $branch->name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500">
                <i class="fas fa-truck mr-1"></i> Supplier Name
            </label>
            <select name="supplier_id" class="w-full rounded-lg border-slate-300 text-sm">
                <option value="">All Suppliers</option>
                @foreach($supplier_options as $supplier)
                    <option value="{{ $supplier->id }}" @selected((string)request('supplier_id') === (string)$supplier->id)>
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>
        </div>

        @if($type === 'general_ledger')
            <input type="hidden" name="account_code" value="{{ $selectedAccountCode ?? '' }}">
            <input type="hidden" name="journal_type" value="{{ $selectedJournalType ?? '' }}">
            <input type="hidden" name="q" value="{{ $ledgerSearch ?? '' }}">
        @endif

        <div class="sm:col-span-2 xl:col-span-4 flex justify-end gap-2">
            <a href="{{ route('reports.show', $type) }}"
               class="rounded-lg border border-slate-300 px-4 py-2 text-sm font-bold text-slate-600">
                <i class="fas fa-rotate-left mr-1"></i> Clear
            </a>
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white">
                <i class="fas fa-filter mr-1"></i> Apply
            </button>
        </div>
    </form>
</div>

<div class="mb-5 flex flex-wrap gap-2">
    @foreach($reportTypes as $reportType)
        <a href="{{ route('reports.show', $reportType).'?'.http_build_query(request()->only(['from','to','branch_id','supplier_id','per_page'])) }}"
           class="rounded-full px-3 py-1.5 text-xs font-bold
                {{ $type === $reportType
                    ? 'bg-indigo-600 text-white'
                    : 'border border-slate-200 bg-white text-slate-600 hover:border-indigo-300' }}">
            <i class="fas
                {{ $reportType === 'general_ledger' ? 'fa-book-open' :
                   ($reportType === 'stock' ? 'fa-boxes-stacked' :
                   ($reportType === 'sales' ? 'fa-cash-register' : 'fa-chart-simple')) }}
                mr-1"></i>
            {{ str($reportType)->replace('_', ' ')->title() }}
        </a>
    @endforeach
</div>

@if($type === 'general_ledger')
    <div class="mb-5 grid grid-cols-2 gap-3 lg:grid-cols-5">
        @foreach([
            ['Ledger Entries', number_format($ledgerSummary['entries'] ?? 0), 'fa-list-ol'],
            ['Opening Balance', $currency.' '.number_format((float)($ledgerSummary['opening_balance'] ?? 0), 2), 'fa-arrow-right-to-bracket'],
            ['Total Debits', $currency.' '.number_format((float)($ledgerSummary['total_debit'] ?? 0), 2), 'fa-arrow-trend-up'],
            ['Total Credits', $currency.' '.number_format((float)($ledgerSummary['total_credit'] ?? 0), 2), 'fa-arrow-trend-down'],
            ['Closing Balance', $currency.' '.number_format((float)($ledgerSummary['closing_balance'] ?? 0), 2), 'fa-scale-balanced'],
        ] as [$label, $value, $icon])
            <div class="rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
                <div class="flex items-start justify-between gap-2">
                    <div>
                        <div class="text-[10px] font-black uppercase tracking-wide text-slate-400">{{ $label }}</div>
                        <div class="mt-2 text-lg font-black text-slate-800">{{ $value }}</div>
                    </div>
                    <i class="fas {{ $icon }} text-indigo-500"></i>
                </div>
            </div>
        @endforeach
    </div>

    <form method="GET" action="{{ route('reports.show', 'general_ledger') }}"
          class="mb-5 grid gap-3 rounded-xl border border-slate-200 bg-white p-4 shadow-sm md:grid-cols-3">
        <input type="hidden" name="from" value="{{ $from->format('Y-m-d') }}">
        <input type="hidden" name="to" value="{{ $to->format('Y-m-d') }}">
        <input type="hidden" name="branch_id" value="{{ request('branch_id') }}">
        <input type="hidden" name="supplier_id" value="{{ request('supplier_id') }}">

        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500">
                Search Ledger
            </label>
            <input type="search" name="q" value="{{ $ledgerSearch ?? '' }}"
                   placeholder="Journal, reference, account..."
                   class="w-full rounded-lg border-slate-300 text-sm">
        </div>

        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500">
                Account Name
            </label>
            <select name="account_code" class="w-full rounded-lg border-slate-300 text-sm">
                <option value="">All Accounts</option>
                @foreach($accountOptions as $account)
                    <option value="{{ $account->account_code }}"
                            @selected(($selectedAccountCode ?? '') === $account->account_code)>
                        {{ $account->account_code }} — {{ $account->account_name }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-xs font-bold text-slate-500">
                Journal Type
            </label>
            <select name="journal_type" class="w-full rounded-lg border-slate-300 text-sm">
                <option value="">All Journal Types</option>
                @foreach($journalTypeOptions as $journalType)
                    <option value="{{ $journalType }}"
                            @selected(($selectedJournalType ?? '') === $journalType)>
                        {{ str($journalType)->replace('_', ' ')->title() }}
                    </option>
                @endforeach
            </select>
        </div>

        <div class="md:col-span-3 flex justify-end">
            <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-bold text-white">
                <i class="fas fa-filter mr-1"></i> Filter Ledger
            </button>
        </div>
    </form>
@endif

<div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
    <div class="overflow-x-auto">
        <table class="min-w-full text-sm">
            <thead class="border-b border-slate-200 bg-slate-50">
                <tr>
                    @foreach($headers ?? [] as $header)
                        <th class="whitespace-nowrap px-4 py-3 text-left text-xs font-black uppercase tracking-wide text-slate-500">
                            {{ $header }}
                        </th>
                    @endforeach
                </tr>
            </thead>

            <tbody class="divide-y divide-slate-100">
                @forelse($rows as $row)
                    <tr class="hover:bg-slate-50/70">
                        @foreach($row as $key => $value)
                            <td class="px-4 py-3 align-top text-slate-700">
                                @if(in_array($key, $moneyColumns ?? [], true))
                                    <span class="whitespace-nowrap font-semibold">
                                        {{ $currency }} {{ number_format((float)$value, 2) }}
                                    </span>
                                @elseif(is_bool($value))
                                    {{ $value ? 'Yes' : 'No' }}
                                @elseif(is_scalar($value) || $value === null)
                                    {{ $value ?? '—' }}
                                @else
                                    {{-- Absolute safety net: never print arrays/objects directly. --}}
                                    {{ json_encode($value, JSON_UNESCAPED_UNICODE) }}
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @empty
                    <tr>
                        <td colspan="{{ max(count($headers ?? []), 1) }}"
                            class="px-4 py-12 text-center text-slate-400">
                            <i class="fas fa-folder-open mb-2 block text-3xl"></i>
                            No data found for the selected filters.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>
</div>


@if($rows instanceof \Illuminate\Contracts\Pagination\Paginator)
    <div class="mt-4 flex flex-col gap-3 rounded-xl border border-slate-200 bg-white px-4 py-3 sm:flex-row sm:items-center sm:justify-between">
        <div class="flex flex-wrap items-center gap-3 text-xs text-slate-500">
            <span>
                Showing
                <strong class="text-slate-700">{{ number_format($rows->firstItem() ?? 0) }}</strong>
                –
                <strong class="text-slate-700">{{ number_format($rows->lastItem() ?? 0) }}</strong>
                of
                <strong class="text-slate-700">{{ number_format($rows->total()) }}</strong>
            </span>

            <form method="GET" action="{{ route('reports.show', $type) }}" class="flex items-center gap-2">
                @foreach(request()->except(['page', 'per_page']) as $key => $value)
                    @if(is_array($value))
                        @foreach($value as $subKey => $subValue)
                            <input type="hidden" name="{{ $key }}[{{ $subKey }}]" value="{{ $subValue }}">
                        @endforeach
                    @else
                        <input type="hidden" name="{{ $key }}" value="{{ $value }}">
                    @endif
                @endforeach

                <label class="font-bold text-slate-500">Rows</label>
                <select name="per_page"
                        onchange="this.form.submit()"
                        class="rounded-lg border-slate-300 bg-white px-2 py-1 text-xs">
                    @foreach([10,20,50,100] as $size)
                        <option value="{{ $size }}"
                            @selected((int)request('per_page', $rows->perPage()) === $size)>
                            {{ $size }}
                        </option>
                    @endforeach
                </select>
            </form>
        </div>

        <div>
            {{ $rows->onEachSide(1)->links() }}
        </div>
    </div>
@endif

@endsection
