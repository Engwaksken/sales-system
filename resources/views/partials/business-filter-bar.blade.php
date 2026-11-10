@php
    $showSearch = $showSearch ?? true;
    $searchPlaceholder = $searchPlaceholder ?? 'Search…';
    $clearRoute = $clearRoute ?? url()->current();
    $isFiltered = request('q') || request('from') || request('to') || request('branch_id') || request('supplier_id')
        || (request('period') && request('period') !== 'all');
@endphp

{{-- Pass 'inToolbar' => true when the page wraps this in its own .filter-toolbar (to add an Export menu). --}}
@unless($inToolbar ?? false)<div class="filter-toolbar">@endunless
    <form method="GET" class="filter-toolbar__filters">
        @if($showSearch)
            <div class="filter-search">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" value="{{ request('q') }}" placeholder="{{ $searchPlaceholder }}"
                       aria-label="Search" class="rounded-lg border-slate-300">
            </div>
            <span class="filter-toolbar__divider" aria-hidden="true"></span>
        @endif

        <select name="period" aria-label="Period" class="rounded-lg border-slate-300">
            <option value="all" @selected(request('period', 'all') === 'all')>All Time</option>
            <option value="today" @selected(request('period') === 'today')>Today</option>
            <option value="week" @selected(request('period') === 'week')>This Week</option>
            <option value="month" @selected(request('period') === 'month')>This Month</option>
            <option value="year" @selected(request('period') === 'year')>This Year</option>
            <option value="range" @selected(request('period') === 'range')>Custom Range</option>
        </select>

        <input type="date" name="from" value="{{ request('from') }}" aria-label="From date" title="From" class="rounded-lg border-slate-300">
        <span class="text-xs text-slate-400">to</span>
        <input type="date" name="to" value="{{ request('to') }}" aria-label="To date" title="To" class="rounded-lg border-slate-300">

        <select name="branch_id" aria-label="Branch" class="rounded-lg border-slate-300">
            <option value="">All Branches</option>
            @foreach(($branch_options ?? collect()) as $branch)
                <option value="{{ $branch->id }}" @selected((string) request('branch_id') === (string) $branch->id)>{{ $branch->name ?: ('Branch #'.$branch->id) }}</option>
            @endforeach
        </select>

        <select name="supplier_id" aria-label="Supplier" class="rounded-lg border-slate-300">
            <option value="">All Suppliers</option>
            @foreach(($supplier_options ?? collect()) as $supplier)
                <option value="{{ $supplier->id }}" @selected((string) request('supplier_id') === (string) $supplier->id)>{{ $supplier->name }}</option>
            @endforeach
        </select>

        <button type="submit" class="filter-btn"><i class="fa-solid fa-filter"></i> Apply</button>
        @if($isFiltered)
            <a href="{{ $clearRoute }}" class="filter-clear"><i class="fa-solid fa-xmark"></i> Clear</a>
        @endif
    </form>
@unless($inToolbar ?? false)</div>@endunless
