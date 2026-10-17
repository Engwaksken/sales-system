@extends('layouts.app')
@section('title', 'EfrisConfig')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold text-slate-800 flex items-center gap-2"><i class="fa-solid fa-shield-halved text-slate-400"></i> EfrisConfig</h1>
    <button type="button" onclick="openCreateModal('efris_config-modal', 'efris_config-form', '{{ route('efris_config.store') }}')" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 flex items-center gap-2"><i class="fa-solid fa-plus"></i> New EfrisConfig</button>
</div>
<div class="grid grid-cols-2 md:grid-cols-2 gap-4 mb-6">
@include('partials.stat-card', ['icon' => 'fa-shield-halved', 'color' => 'indigo', 'label' => 'Total EfrisConfig', 'value' => $items->total()])
@include('partials.stat-card', ['icon' => 'fa-circle-check', 'color' => 'emerald', 'label' => 'Active', 'value' => $stat1])
</div>
@php($isMoney = false)
@include('partials.period-stats', ['periodStats' => $periodStats, 'isMoney' => $isMoney])
<form method="GET" class="flex items-center gap-2 text-sm mb-4">
    <div class="relative">
        <i class="fa-solid fa-magnifying-glass absolute left-3 top-1/2 -translate-y-1/2 text-slate-300 text-xs"></i>
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Search efris config…"
               class="rounded-md border-slate-300 text-sm pl-8 pr-3 py-1.5 w-56">
    </div>
    <button class="text-sm text-slate-400 hover:text-slate-600"><i class="fa-solid fa-arrow-right"></i></button>
    <label class="text-slate-500 ml-2"><i class="fa-solid fa-filter"></i> Period:</label>
    <select name="period" onchange="this.form.submit()" class="rounded-md border-slate-300 text-sm">
        <option value="all" {{ request('period','all')=='all'?'selected':'' }}>All Time</option>
        <option value="today" {{ request('period')=='today'?'selected':'' }}>Today</option>
        <option value="week" {{ request('period')=='week'?'selected':'' }}>This Week</option>
        <option value="month" {{ request('period')=='month'?'selected':'' }}>This Month</option>
        <option value="year" {{ request('period')=='year'?'selected':'' }}>This Year</option>
    </select>
    @if(request('q') || (request('period') && request('period') !== 'all'))
    <a href="{{ route('efris_config.index') }}" class="text-slate-400 hover:text-slate-600 text-xs">Clear <i class="fa-solid fa-xmark"></i></a>
    @endif
</form>
<details class="mb-4 bg-white rounded-lg shadow">
  <summary class="cursor-pointer px-4 py-2 text-sm text-slate-600 flex items-center gap-2 list-none">
    <i class="fa-solid fa-file-export"></i> Export <i class="fa-solid fa-chevron-down text-xs ml-1"></i>
  </summary>
  <form method="GET" action="{{ route('efris_config.export.csv') }}" class="px-4 pb-4 pt-2 border-t border-slate-100">
    <div class="text-xs text-slate-400 mb-2">Select the columns to include:</div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-2 mb-3 text-sm">
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="branch_id" checked class="rounded border-slate-300"> Branch</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="tin" checked class="rounded border-slate-300"> Tin</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="taxpayer_name" checked class="rounded border-slate-300"> Taxpayer Name</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="taxpayer_address" checked class="rounded border-slate-300"> Taxpayer Address</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="taxpayer_email" checked class="rounded border-slate-300"> Taxpayer Email</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="taxpayer_phone" checked class="rounded border-slate-300"> Taxpayer Phone</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="business_type" checked class="rounded border-slate-300"> Business Type</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="vat_registered" checked class="rounded border-slate-300"> Vat Registered</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="environment" checked class="rounded border-slate-300"> Environment</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="private_key_pem" checked class="rounded border-slate-300"> Private Key Pem</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="public_key_pem" checked class="rounded border-slate-300"> Public Key Pem</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="ura_public_key_pem" checked class="rounded border-slate-300"> Ura Public Key Pem</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="portal_username" checked class="rounded border-slate-300"> Portal Username</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="portal_password_enc" checked class="rounded border-slate-300"> Portal Password Enc</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="is_active" checked class="rounded border-slate-300"> Is Active</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="created_by" checked class="rounded border-slate-300"> Createdby</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="updated_by" checked class="rounded border-slate-300"> Updatedby</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="created_at" checked class="rounded border-slate-300"> Created At</label>
<label class="flex items-center gap-2"><input type="checkbox" name="columns[]" value="updated_at" checked class="rounded border-slate-300"> Updated At</label>
    </div>
    <input type="hidden" name="period" value="{{ request('period', 'all') }}">
    <input type="hidden" name="q" value="{{ request('q', '') }}">
    <div class="flex items-center gap-2">
      <button formaction="{{ route('efris_config.export.csv') }}" class="bg-indigo-600 text-white px-4 py-1.5 rounded-md text-sm flex items-center gap-2"><i class="fa-solid fa-file-csv"></i> Export CSV</button>
      <button formaction="{{ route('efris_config.export.pdf') }}" class="bg-slate-600 text-white px-4 py-1.5 rounded-md text-sm flex items-center gap-2"><i class="fa-solid fa-file-pdf"></i> Export PDF</button>
    </div>
  </form>
</details>
@if(session('status'))
<div data-flash class="mb-4 rounded-md bg-emerald-50 text-emerald-700 px-4 py-2 text-sm">{{ session('status') }}</div>
@endif
@if($errors->any())
<div data-flash class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<div class="bg-white shadow rounded-lg overflow-x-auto">
<table class="min-w-full divide-y divide-slate-200">
<thead class="bg-slate-50"><tr><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Tin</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Taxpayer Name</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Environment</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Is Active</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Createdby</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Updatedby</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Created At</th><th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Updated At</th><th class="px-4 py-2"></th></tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($items as $row)
<tr><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->tin }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->taxpayer_name }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->environment }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->is_active ? 'Yes' : 'No' }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->createdBy?->name ?? '—' }}</td><td class='px-4 py-2 text-sm text-slate-700'>{{ $row->updatedBy?->name ?? '—' }}</td><td class='px-4 py-2 text-sm text-slate-500'>{{ $row->created_at?->format('d M Y H:i') ?? '—' }}</td><td class='px-4 py-2 text-sm text-slate-500'>{{ $row->updated_at?->format('d M Y H:i') ?? '—' }}</td><td class="px-4 py-2 text-right text-sm space-x-3 whitespace-nowrap">
<button type="button" title="View" onclick='openViewModal("efris_config-modal", "efris_config-form", @json($row))' class="text-slate-500 hover:text-slate-700"><i class="fa-solid fa-eye"></i></button>
<button type="button" title="Edit" onclick='openEditModal("efris_config-modal", "efris_config-form", "{{ route('efris_config.update', $row->id) }}", @json($row))' class="text-indigo-600 hover:text-indigo-800"><i class="fa-solid fa-pen"></i></button>
<form method="POST" action="{{ route('efris_config.destroy', $row->id) }}" class="inline" onsubmit="return confirm('Delete this record?')">
@csrf @method('DELETE')
<button title="Delete" class="text-red-600 hover:text-red-800"><i class="fa-solid fa-trash"></i></button>
</form>
</td></tr>
@empty
<tr><td colspan="9" class="px-4 py-6 text-center text-sm text-slate-400">No records yet.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="mt-4">@include('partials.table-pagination', ['paginator' => $items])</div>

<div id="efris_config-modal" class="fixed inset-0 bg-black/40 hidden items-center justify-center z-30 p-4">
  <div class="bg-white rounded-xl max-w-2xl w-full max-h-[90vh] overflow-y-auto p-6">
    <div class="flex justify-between items-center mb-4">
      <h2 id="efris_config-modal-title" class="font-semibold text-lg flex items-center gap-2"><i class="fa-solid fa-shield-halved text-indigo-500"></i> New EfrisConfig</h2>
      <button type="button" onclick="closeModal('efris_config-modal')" class="text-slate-400 text-xl leading-none">&times;</button>
    </div>
    <form id="efris_config-form" method="POST" action="{{ route('efris_config.store') }}" class="space-y-4">
      @csrf
      <input type="hidden" name="_method" value="POST">
      <div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Branch Id</label>
  <select name="branch_id" class="w-full rounded-md border-slate-300 shadow-sm" required>
    <option value="">-- Select Branch Id --</option>
    @foreach($branch_options as $opt)
    <option value="{{ $opt->id }}">{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Tin</label>
  <input type="text" name="tin" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Taxpayer Name</label>
  <input type="text" name="taxpayer_name" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Taxpayer Address</label>
  <textarea name="taxpayer_address" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" ></textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Taxpayer Email</label>
  <input type="text" name="taxpayer_email" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Taxpayer Phone</label>
  <input type="text" name="taxpayer_phone" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Business Type</label>
  <input type="text" name="business_type" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="vat_registered" value="0">
  <input type="checkbox" name="vat_registered" value="1" class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Vat Registered</label>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Environment</label>
  <select name="environment" class="w-full rounded-md border-slate-300 shadow-sm" required><option value='sandbox'>sandbox</option><option value='production'>production</option></select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Private Key Pem</label>
  <textarea name="private_key_pem" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" ></textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Public Key Pem</label>
  <textarea name="public_key_pem" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" ></textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Ura Public Key Pem</label>
  <textarea name="ura_public_key_pem" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" ></textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Portal Username</label>
  <input type="text" name="portal_username" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Portal Password Enc</label>
  <textarea name="portal_password_enc" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" ></textarea>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="is_active" value="0">
  <input type="checkbox" name="is_active" value="1" class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Is Active</label>
</div>
      <div class="pt-2 flex items-center gap-2">
        <button type="submit" class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700 flex items-center gap-2"><i class="fa-solid fa-check"></i> Save</button>
        <button type="button" onclick="closeModal('efris_config-modal')" class="text-sm text-slate-500">Cancel</button>
      </div>
    </form>
  </div>
</div>
@endsection
