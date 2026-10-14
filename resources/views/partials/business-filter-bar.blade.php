@php
    $showSearch = $showSearch ?? true;
    $searchPlaceholder = $searchPlaceholder ?? 'Search…';
    $clearRoute = $clearRoute ?? url()->current();
@endphp

<form method="GET" class="mb-5 rounded-xl border border-slate-200 bg-white p-4 shadow-sm">
    <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-7">
        @if($showSearch)
            <div class="xl:col-span-2">
                <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                    <i class="fa-solid fa-magnifying-glass mr-1"></i> Search
                </label>
                <input
                    type="text"
                    name="q"
                    value="{{ request('q') }}"
                    placeholder="{{ $searchPlaceholder }}"
                    class="w-full rounded-lg border-slate-300 text-sm"
                >
            </div>
        @endif

        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                <i class="fa-solid fa-calendar-days mr-1"></i> Period
            </label>
            <select name="period" class="w-full rounded-lg border-slate-300 text-sm">
                <option value="all" {{ request('period', 'all') === 'all' ? 'selected' : '' }}>All Time</option>
                <option value="today" {{ request('period') === 'today' ? 'selected' : '' }}>Today</option>
                <option value="week" {{ request('period') === 'week' ? 'selected' : '' }}>This Week</option>
                <option value="month" {{ request('period') === 'month' ? 'selected' : '' }}>This Month</option>
                <option value="year" {{ request('period') === 'year' ? 'selected' : '' }}>This Year</option>
                <option value="range" {{ request('period') === 'range' ? 'selected' : '' }}>Custom Range</option>
            </select>
        </div>

        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                <i class="fa-solid fa-calendar-day mr-1"></i> From
            </label>
            <input
                type="date"
                name="from"
                value="{{ request('from') }}"
                class="w-full rounded-lg border-slate-300 text-sm"
            >
        </div>

        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                <i class="fa-solid fa-calendar-check mr-1"></i> To
            </label>
            <input
                type="date"
                name="to"
                value="{{ request('to') }}"
                class="w-full rounded-lg border-slate-300 text-sm"
            >
        </div>

        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                <i class="fa-solid fa-building mr-1"></i> Branch
            </label>
            <select name="branch_id" class="w-full rounded-lg border-slate-300 text-sm">
                <option value="">All Branches</option>
                @foreach(($branch_options ?? collect()) as $branch)
                    <option value="{{ $branch->id }}" {{ (string)request('branch_id') === (string)$branch->id ? 'selected' : '' }}>
                        {{ $branch->name ?: ('Branch #'.$branch->id) }}
                    </option>
                @endforeach
            </select>
        </div>

        <div>
            <label class="mb-1 block text-xs font-semibold uppercase tracking-wide text-slate-500">
                <i class="fa-solid fa-truck mr-1"></i> Supplier
            </label>
            <select name="supplier_id" class="w-full rounded-lg border-slate-300 text-sm">
                <option value="">All Suppliers</option>
                @foreach(($supplier_options ?? collect()) as $supplier)
                    <option value="{{ $supplier->id }}" {{ (string)request('supplier_id') === (string)$supplier->id ? 'selected' : '' }}>
                        {{ $supplier->name }}
                    </option>
                @endforeach
            </select>
        </div>
    </div>

    <div class="mt-3 flex flex-wrap items-center gap-2">
        <button class="rounded-lg bg-indigo-600 px-4 py-2 text-sm font-semibold text-white hover:bg-indigo-700">
            <i class="fa-solid fa-filter mr-1"></i> Apply Filters
        </button>

        <a href="{{ $clearRoute }}"
           class="rounded-lg border border-slate-200 bg-white px-4 py-2 text-sm font-semibold text-slate-600 hover:bg-slate-50">
            <i class="fa-solid fa-rotate-left mr-1"></i> Clear
        </a>

        @if(request('from') || request('to'))
            <span class="text-xs text-slate-400">
                Range:
                {{ request('from') ?: 'Beginning' }}
                →
                {{ request('to') ?: 'Today' }}
            </span>
        @endif
    </div>
</form>
