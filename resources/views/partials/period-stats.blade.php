@if(!empty($periodStats))
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-6">
    @include('partials.stat-card', ['icon' => 'fa-sun', 'color' => 'amber', 'label' => 'Today', 'value' => $isMoney ? 'UGX '.number_format($periodStats['today'], 0) : $periodStats['today']])
    @include('partials.stat-card', ['icon' => 'fa-calendar-week', 'color' => 'sky', 'label' => 'This Week', 'value' => $isMoney ? 'UGX '.number_format($periodStats['week'], 0) : $periodStats['week']])
    @include('partials.stat-card', ['icon' => 'fa-calendar-days', 'color' => 'violet', 'label' => 'This Month', 'value' => $isMoney ? 'UGX '.number_format($periodStats['month'], 0) : $periodStats['month']])
    @include('partials.stat-card', ['icon' => 'fa-calendar', 'color' => 'rose', 'label' => 'This Year', 'value' => $isMoney ? 'UGX '.number_format($periodStats['year'], 0) : $periodStats['year']])
</div>
@endif
