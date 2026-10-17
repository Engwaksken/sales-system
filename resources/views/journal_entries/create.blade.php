@extends('layouts.app')
@section('title', 'New JournalEntry')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">New JournalEntry</h1>
@if($errors->any())
<div data-flash class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('journal_entries.store') }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Journal Id</label>
  <select name="journal_id" class="w-full rounded-md border-slate-300 shadow-sm" required>
    <option value="">-- Select Journal Id --</option>
    @foreach($journal_options as $opt)
    <option value="{{ $opt->id }}" {{ (old('journal_id') == $opt->id) ? 'selected' : '' }}>{{ $opt->journal_no }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Account Code</label>
  <input type="text" name="account_code" value="{{ old('account_code') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
  <input type="text" name="description" value="{{ old('description') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Debit</label>
  <input type="number" step='0.01' name="debit" value="{{ old('debit') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Credit</label>
  <input type="number" step='0.01' name="credit" value="{{ old('credit') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Save</button>
<a href="{{ route('journal_entries.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@endsection
