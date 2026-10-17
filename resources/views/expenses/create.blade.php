@extends('layouts.app')
@section('title', 'New Expense')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">New Expense</h1>
@if($errors->any())
<div data-flash class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('expenses.store') }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Title</label>
  <input type="text" name="title" value="{{ old('title') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Amount</label>
  <input type="number" step='0.01' name="amount" value="{{ old('amount') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Expense Date</label>
  <input type="date" name="expense_date" value="{{ old('expense_date') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Category</label>
  <input type="text" name="category" value="{{ old('category') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
  <textarea name="description" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" >{{ old('description') }}</textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Branch Id</label>
  <select name="branch_id" class="w-full rounded-md border-slate-300 shadow-sm" required>
    <option value="">-- Select Branch Id --</option>
    @foreach($branch_options as $opt)
    <option value="{{ $opt->id }}" {{ (old('branch_id') == $opt->id) ? 'selected' : '' }}>{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Save</button>
<a href="{{ route('expenses.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@endsection
