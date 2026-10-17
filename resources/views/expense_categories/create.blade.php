@extends('layouts.app')
@section('title', 'New ExpenseCategory')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">New ExpenseCategory</h1>
@if($errors->any())
<div data-flash class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('expense_categories.store') }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
  <input type="text" name="name" value="{{ old('name') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="status" value="0">
  <input type="checkbox" name="status" value="1" {{ old('status') ? 'checked' : '' }} class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Status</label>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Save</button>
<a href="{{ route('expense_categories.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@endsection
