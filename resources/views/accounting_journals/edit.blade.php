@extends('layouts.app')
@section('title', 'Edit AccountingJournal')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">Edit AccountingJournal</h1>
@if($errors->any())
<div data-flash class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('accounting_journals.update', $accounting_journal->id) }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf @method('PUT')
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Journal No</label>
  <input type="text" name="journal_no" value="{{ $accounting_journal->journal_no }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Journal Type</label>
  <select name="journal_type" class="w-full rounded-md border-slate-300 shadow-sm" required><option value='GJ' {{ ($accounting_journal->journal_type == 'GJ') ? 'selected' : '' }}>GJ</option><option value='SJ' {{ ($accounting_journal->journal_type == 'SJ') ? 'selected' : '' }}>SJ</option><option value='PJ' {{ ($accounting_journal->journal_type == 'PJ') ? 'selected' : '' }}>PJ</option><option value='CRJ' {{ ($accounting_journal->journal_type == 'CRJ') ? 'selected' : '' }}>CRJ</option><option value='CPJ' {{ ($accounting_journal->journal_type == 'CPJ') ? 'selected' : '' }}>CPJ</option><option value='MJ' {{ ($accounting_journal->journal_type == 'MJ') ? 'selected' : '' }}>MJ</option></select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Reference No</label>
  <input type="text" name="reference_no" value="{{ $accounting_journal->reference_no }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Reference Type</label>
  <input type="text" name="reference_type" value="{{ $accounting_journal->reference_type }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Reference Id</label>
  <input type="number" name="reference_id" value="{{ $accounting_journal->reference_id }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Journal Date</label>
  <input type="date" name="journal_date" value="{{ $accounting_journal->journal_date }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
  <input type="text" name="description" value="{{ $accounting_journal->description }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Branch Id</label>
  <select name="branch_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Branch Id --</option>
    @foreach($branch_options as $opt)
    <option value="{{ $opt->id }}" {{ ($accounting_journal->branch_id == $opt->id) ? 'selected' : '' }}>{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Created By</label>
  <select name="created_by" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Created By --</option>
    @foreach($created_by_options as $opt)
    <option value="{{ $opt->id }}" {{ ($accounting_journal->created_by == $opt->id) ? 'selected' : '' }}>{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="is_posted" value="0">
  <input type="checkbox" name="is_posted" value="1" {{ $accounting_journal->is_posted ? 'checked' : '' }} class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Is Posted</label>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Update</button>
<a href="{{ route('accounting_journals.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@endsection
