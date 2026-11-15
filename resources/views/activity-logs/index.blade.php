@extends('layouts.app')
@section('title', 'Activity Log')
@section('content')
@php
    $actionColors = ['created' => 'bg-emerald-100 text-emerald-800', 'updated' => 'bg-sky-100 text-sky-800', 'deleted' => 'bg-rose-100 text-rose-800',
        'login' => 'bg-slate-100 text-slate-700', 'logout' => 'bg-slate-100 text-slate-700', 'login_failed' => 'bg-amber-100 text-amber-800'];
    $fmt = function ($v) {
        if ($v === null || $v === '') return '—';
        return is_numeric($v) ? rtrim(rtrim(number_format((float) $v, 2), '0'), '.') : \Illuminate\Support\Str::limit((string) $v, 80);
    };
@endphp
<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-xl font-semibold text-slate-800">Activity log</h1>
        <p class="text-sm text-slate-500">Every change to sales, stock, payments, products, users and settings, with who made it and from where.</p>
    </div>
    <a href="{{ route('activity-logs.index', ['flagged' => 1]) }}"
       class="rounded-lg border border-rose-200 bg-rose-50 px-4 py-2 text-sm font-semibold text-rose-700 hover:bg-rose-100">
        <i class="fas fa-flag mr-1"></i> {{ $flaggedThisWeek }} flagged in the last 7 days
    </a>
</div>

@if($topFlagged->isNotEmpty())
<section class="mb-5 rounded-lg border border-amber-200 bg-amber-50 p-4 text-sm">
    <p class="mb-2 font-semibold text-amber-900">Most flagged changes this week</p>
    <div class="flex flex-wrap gap-2">
        @foreach($topFlagged as $row)
            <a href="{{ route('activity-logs.index', ['flagged' => 1, 'user_id' => $row->user_id]) }}" class="rounded-full bg-white px-3 py-1 text-amber-900 shadow-sm hover:underline">
                {{ $row->user_name ?? 'User #'.$row->user_id }} · {{ $row->total }}
            </a>
        @endforeach
    </div>
</section>
@endif

<form method="GET" class="mb-5 grid gap-3 rounded-lg bg-white p-4 shadow sm:grid-cols-2 lg:grid-cols-4">
    <label class="text-sm">Search<input name="q" value="{{ $filters['q'] ?? '' }}" class="mt-1 block w-full rounded border-slate-300" placeholder="Invoice, product, name..."></label>
    <label class="text-sm">User<select name="user_id" class="mt-1 block w-full rounded border-slate-300"><option value="">Everyone</option>@foreach($users as $u)<option value="{{ $u->id }}" @selected(($filters['user_id'] ?? '') == $u->id)>{{ $u->name }} ({{ str_replace('_', ' ', $u->role) }})</option>@endforeach</select></label>
    <label class="text-sm">Area<select name="module" class="mt-1 block w-full rounded border-slate-300"><option value="">All areas</option>@foreach($modules as $key => $label)<option value="{{ $key }}" @selected(($filters['module'] ?? '') === $key)>{{ $label }}</option>@endforeach</select></label>
    <label class="text-sm">Action<select name="action" class="mt-1 block w-full rounded border-slate-300"><option value="">Any action</option>@foreach(['created' => 'Created', 'updated' => 'Changed', 'deleted' => 'Deleted', 'login' => 'Signed in', 'login_failed' => 'Failed sign-in'] as $key => $label)<option value="{{ $key }}" @selected(($filters['action'] ?? '') === $key)>{{ $label }}</option>@endforeach</select></label>
    <label class="text-sm">Branch<select name="branch_id" class="mt-1 block w-full rounded border-slate-300"><option value="">All branches</option>@foreach($branches as $b)<option value="{{ $b->id }}" @selected(($filters['branch_id'] ?? '') == $b->id)>{{ $b->name }}</option>@endforeach</select></label>
    <label class="text-sm">From<input type="date" name="from" value="{{ $filters['from'] ?? '' }}" class="mt-1 block w-full rounded border-slate-300"></label>
    <label class="text-sm">To<input type="date" name="to" value="{{ $filters['to'] ?? '' }}" class="mt-1 block w-full rounded border-slate-300"></label>
    <div class="flex items-end gap-3">
        <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="flagged" value="1" @checked(!empty($filters['flagged'])) class="rounded border-slate-300"> Flagged only</label>
        <button class="rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700">Filter</button>
        <a href="{{ route('activity-logs.index') }}" class="text-sm text-slate-500 hover:underline">Reset</a>
    </div>
</form>

<section class="overflow-x-auto rounded-lg bg-white shadow">
<table class="min-w-full text-sm">
    <thead class="bg-slate-50 text-left"><tr><th class="p-3">When</th><th class="p-3">Who</th><th class="p-3">What happened</th><th class="p-3">Details</th><th class="p-3">From</th></tr></thead>
    <tbody>
    @forelse($logs as $log)
        <tr class="border-t align-top {{ $log->flagged ? 'bg-rose-50/60' : '' }}">
            <td class="whitespace-nowrap p-3 text-slate-600">{{ $log->created_at?->format('d M Y') }}<br><span class="text-xs">{{ $log->created_at?->format('H:i:s') }}</span></td>
            <td class="p-3"><div class="font-medium text-slate-800">{{ $log->user_name ?? 'System' }}</div><div class="text-xs text-slate-500">{{ str_replace('_', ' ', $log->user_role ?? '') }}</div></td>
            <td class="p-3">
                <span class="mr-1 rounded px-2 py-0.5 text-xs font-semibold {{ $actionColors[$log->action] ?? 'bg-slate-100 text-slate-700' }}">{{ str_replace('_', ' ', $log->action) }}</span>
                {{ $log->description }}
                @if($log->flagged)<div class="mt-1 text-xs font-semibold text-rose-700"><i class="fas fa-flag"></i> {{ $log->flag_reason }}</div>@endif
                @if($log->context)<div class="mt-1 text-xs text-slate-400">via {{ str_replace('_', ' ', $log->context) }}</div>@endif
            </td>
            <td class="p-3">
                @if($log->changes && $log->action === 'updated')
                    <ul class="space-y-0.5 text-xs">
                        @foreach($log->changes as $field => $pair)
                            <li><span class="text-slate-500">{{ str_replace('_', ' ', $field) }}:</span> <span class="text-rose-700 line-through">{{ $fmt($pair[0] ?? null) }}</span> → <span class="font-semibold text-emerald-700">{{ $fmt($pair[1] ?? null) }}</span></li>
                        @endforeach
                    </ul>
                @elseif($log->changes)
                    <details class="text-xs"><summary class="cursor-pointer text-slate-500">{{ count($log->changes) }} fields</summary>
                        <ul class="mt-1 space-y-0.5">@foreach($log->changes as $field => $pair)<li><span class="text-slate-500">{{ str_replace('_', ' ', $field) }}:</span> {{ $fmt($pair[1] ?? $pair[0] ?? null) }}</li>@endforeach</ul>
                    </details>
                @else
                    <span class="text-slate-400">—</span>
                @endif
            </td>
            <td class="whitespace-nowrap p-3 text-xs text-slate-500">{{ ucfirst($log->source ?? '') }}<br>{{ $log->ip_address }}</td>
        </tr>
    @empty
        <tr><td colspan="5" class="p-6 text-center text-slate-500">No activity matches these filters.</td></tr>
    @endforelse
    </tbody>
</table>
<div class="p-3">{{ $logs->links() }}</div>
</section>
@endsection
