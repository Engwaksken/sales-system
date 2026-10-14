<div class="mb-6 grid grid-cols-2 gap-4 md:grid-cols-4">
    @include('partials.stat-card', [
        'icon' => 'fa-building',
        'color' => 'sky',
        'label' => 'Branches',
        'value' => $branchStat ?? 0
    ])

    @include('partials.stat-card', [
        'icon' => 'fa-truck',
        'color' => 'amber',
        'label' => 'Suppliers',
        'value' => $supplierStat ?? 0
    ])

    @include('partials.stat-card', [
        'icon' => 'fa-filter-circle-dollar',
        'color' => 'indigo',
        'label' => $filteredStatLabel ?? 'Filtered Records',
        'value' => $filteredStat ?? 0
    ])

    @include('partials.stat-card', [
        'icon' => 'fa-database',
        'color' => 'emerald',
        'label' => $totalStatLabel ?? 'All Records',
        'value' => $totalStat ?? 0
    ])
</div>
