@extends('layouts.app')
@section('title', 'Import '.$module['label'])
@section('content')
@php
    $rowsCollection = collect($rows);
    $errorCount = $rowsCollection->where('action', 'error')->count();
    $createCount = $rowsCollection->where('action', 'create')->count();
    $updateCount = $rowsCollection->where('action', 'update')->count();
    $columns = array_keys($module['columns']);
@endphp
<div class="flex flex-col md:flex-row md:items-center justify-between gap-3 mb-4">
    <div>
        <div class="text-xs uppercase tracking-wider font-bold text-slate-400">Import preview</div>
        <h1 class="text-xl font-semibold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-file-csv text-slate-400"></i> {{ $module['label'] }} &middot; {{ $fileName }}</h1>
        <p class="text-sm text-slate-500 mt-1">Nothing has been saved yet.</p>
    </div>
    <div class="flex flex-wrap items-center gap-2">
        <a href="{{ route($module['index']) }}" class="px-4 py-2 rounded-md text-sm border border-slate-300 bg-white text-slate-700">Cancel</a>
        @if($errorCount === 0)
            <form method="POST" action="{{ route('imports.confirm', $module['key']) }}">
                @csrf
                <input type="hidden" name="token" value="{{ $token }}">
                <button type="submit" class="bg-emerald-600 text-white px-4 py-2 rounded-md text-sm font-semibold hover:bg-emerald-700 flex items-center gap-2"><i class="fa-solid fa-check"></i> Import {{ number_format(count($rows)) }} {{ \Illuminate\Support\Str::plural('row', count($rows)) }}</button>
            </form>
        @endif
    </div>
</div>

<div class="grid grid-cols-2 md:grid-cols-4 gap-3 mb-4">
    <div class="bg-white rounded-lg shadow px-4 py-3"><div class="text-xs text-slate-400">Rows</div><div class="text-2xl font-bold text-slate-800">{{ number_format(count($rows)) }}</div></div>
    <div class="bg-white rounded-lg shadow px-4 py-3"><div class="text-xs text-slate-400">New</div><div class="text-2xl font-bold text-emerald-600">{{ number_format($createCount) }}</div></div>
    <div class="bg-white rounded-lg shadow px-4 py-3"><div class="text-xs text-slate-400">{{ ($module['handler'] ?? null) === 'stock' ? 'Top-ups' : 'Updates' }}</div><div class="text-2xl font-bold text-indigo-600">{{ number_format($updateCount) }}</div></div>
    <div class="bg-white rounded-lg shadow px-4 py-3"><div class="text-xs text-slate-400">Errors</div><div class="text-2xl font-bold {{ $errorCount ? 'text-red-600' : 'text-slate-300' }}">{{ number_format($errorCount) }}</div></div>
</div>

@if($errorCount)
    <div class="mb-4 rounded-xl border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
        <strong>{{ $errorCount }} {{ \Illuminate\Support\Str::plural('row', $errorCount) }} need fixing.</strong>
        Nothing will be imported until every row is valid. Correct the file and upload it again.
    </div>
@elseif(!empty($module['note']))
    <div class="mb-4 rounded-xl border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800"><i class="fa-solid fa-circle-info"></i> {{ $module['note'] }}</div>
@endif

<div class="bg-white rounded-lg shadow overflow-x-auto">
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-slate-500 text-xs uppercase">
            <tr>
                <th class="px-3 py-2 text-left">Line</th>
                <th class="px-3 py-2 text-left">Result</th>
                @foreach($columns as $column)<th class="px-3 py-2 text-left whitespace-nowrap">{{ str_replace('_', ' ', $column) }}</th>@endforeach
            </tr>
        </thead>
        <tbody class="divide-y divide-slate-100">
            @foreach($rows as $row)
                <tr class="{{ $row['action'] === 'error' ? 'bg-red-50/60' : '' }}">
                    <td class="px-3 py-2 text-slate-400 align-top">{{ $row['line'] }}</td>
                    <td class="px-3 py-2 align-top whitespace-nowrap">
                        @switch($row['action'])
                            @case('create')<span class="inline-flex rounded-full bg-emerald-100 text-emerald-700 px-2 py-0.5 text-xs font-semibold">New</span>@break
                            @case('update')<span class="inline-flex rounded-full bg-indigo-100 text-indigo-700 px-2 py-0.5 text-xs font-semibold">{{ ($module['handler'] ?? null) === 'stock' ? 'Top up' : 'Update' }}</span>@break
                            @case('unchanged')<span class="inline-flex rounded-full bg-slate-100 text-slate-500 px-2 py-0.5 text-xs font-semibold">No change</span>@break
                            @default
                                <span class="inline-flex rounded-full bg-red-100 text-red-700 px-2 py-0.5 text-xs font-semibold">Error</span>
                                <ul class="mt-1 text-xs text-red-700 list-disc list-inside whitespace-normal max-w-xs">
                                    @foreach($row['errors'] as $error)<li>{{ $error }}</li>@endforeach
                                </ul>
                        @endswitch
                    </td>
                    @foreach($columns as $column)
                        <td class="px-3 py-2 align-top text-slate-700">{{ $row['values'][$column] ?? '' }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
