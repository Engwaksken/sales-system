@extends('layouts.app')
@section('title', 'Unit Types')
@section('content')
<div class="mb-5 flex flex-wrap items-end justify-between gap-3">
    <div>
        <h1 class="text-xl font-semibold text-slate-800"><i class="fas fa-ruler-combined mr-2 text-indigo-600"></i>Unit types</h1>
        <p class="text-sm text-slate-500">The units stock is counted in. Every stock form lets you pick from this list.</p>
    </div>
    <a class="text-sm text-indigo-600" href="{{ route('stock.index') }}">Back to stock</a>
</div>
@if(session('status'))<div class="mb-4 rounded bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div>@endif
@if($errors->any())<div class="mb-4 rounded bg-rose-50 p-3 text-rose-700">{{ $errors->first() }}</div>@endif

<section class="mb-6 rounded-lg bg-white p-5 shadow">
    <h2 class="mb-3 font-semibold">Add a unit</h2>
    <form method="POST" action="{{ route('unit_types.store') }}" class="grid gap-4 sm:grid-cols-4">@csrf
        <label class="text-sm">Name<input name="name" required maxlength="60" value="{{ old('name') }}" placeholder="e.g. Crate of 24" class="mt-1 block w-full rounded border border-slate-300 px-3 py-2"></label>
        <label class="text-sm">Short name<input name="abbreviation" required maxlength="20" value="{{ old('abbreviation') }}" placeholder="e.g. crate24" class="mt-1 block w-full rounded border border-slate-300 px-3 py-2"></label>
        <label class="text-sm">Group<select name="category" class="mt-1 block w-full rounded border border-slate-300 px-3 py-2">@foreach($categories as $c)<option value="{{ $c }}" @selected(old('category', 'Count') === $c)>{{ $c }}</option>@endforeach</select></label>
        <div class="flex items-end"><button class="rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700"><i class="fas fa-plus mr-1"></i>Add unit</button></div>
    </form>
</section>

<section class="overflow-x-auto rounded-lg bg-white shadow">
    <form method="GET" class="flex gap-2 border-b p-3">
        <input name="q" value="{{ request('q') }}" placeholder="Search units" class="w-64 rounded border border-slate-300 px-3 py-1.5 text-sm">
        <button class="rounded border px-3 text-sm">Search</button>
    </form>
    <table class="min-w-full text-sm">
        <thead class="bg-slate-50 text-left"><tr><th class="p-3">Name</th><th class="p-3">Short name</th><th class="p-3">Group</th><th class="p-3">Stock rows</th><th class="p-3">Active</th><th class="p-3"></th></tr></thead>
        <tbody>
        @forelse($units as $unit)
            @php $f = 'unit-'.$unit->id; @endphp
            <tr class="border-t {{ $unit->is_active ? '' : 'bg-slate-50 text-slate-400' }}">
                <td class="p-2"><input form="{{ $f }}" name="name" value="{{ $unit->name }}" required maxlength="60" class="w-full rounded border border-slate-200 px-2 py-1 text-sm"></td>
                <td class="p-2"><input form="{{ $f }}" name="abbreviation" value="{{ $unit->abbreviation }}" required maxlength="20" class="w-28 rounded border border-slate-200 px-2 py-1 text-sm"></td>
                <td class="p-2"><select form="{{ $f }}" name="category" class="rounded border-slate-200 text-sm">@foreach($categories as $c)<option value="{{ $c }}" @selected($unit->category === $c)>{{ $c }}</option>@endforeach</select></td>
                <td class="p-2 text-slate-500">{{ number_format($inUse[strtolower($unit->abbreviation)] ?? 0) }}</td>
                <td class="p-2"><input form="{{ $f }}" type="checkbox" name="is_active" value="1" @checked($unit->is_active) class="rounded border-slate-300" aria-label="Active"></td>
                <td class="whitespace-nowrap p-2 text-right">
                    <form id="{{ $f }}" method="POST" action="{{ route('unit_types.update', $unit) }}" class="inline">@csrf @method('PUT')
                        <button class="rounded border border-indigo-200 px-3 py-1 text-xs font-semibold text-indigo-700 hover:bg-indigo-50">Save</button>
                    </form>
                    <form method="POST" action="{{ route('unit_types.destroy', $unit) }}" class="inline">@csrf @method('DELETE')
                        <button class="rounded px-2 py-1 text-xs text-rose-600 hover:bg-rose-50" title="Delete {{ $unit->name }}" aria-label="Delete {{ $unit->name }}"><i class="fas fa-trash"></i></button>
                    </form>
                </td>
            </tr>
        @empty
            <tr><td colspan="6" class="p-6 text-center text-slate-500">No units found.</td></tr>
        @endforelse
        </tbody>
    </table>
</section>
@endsection
