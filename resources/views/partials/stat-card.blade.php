@php($color = $color ?? 'indigo')
<div class="stat-card bg-white rounded-lg shadow p-4 flex items-center gap-4">
    <div class="w-11 h-11 rounded-lg bg-{{ $color }}-50 text-{{ $color }}-600 flex items-center justify-center text-lg flex-shrink-0">
        <i class="fa-solid {{ $icon }}"></i>
    </div>
    <div class="min-w-0">
        <div class="text-xs text-slate-400 uppercase truncate">{{ $label }}</div>
        <div class="text-xl font-semibold text-slate-800 truncate">{{ $value }}</div>
    </div>
</div>
