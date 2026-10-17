@extends('layouts.app')
@section('title', 'New Category')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">New Category</h1>
@if($errors->any())
<div data-flash class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('categories.store') }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Category Name</label>
  <input type="text" name="category_name" value="{{ old('category_name') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
  <textarea name="description" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" >{{ old('description') }}</textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
  <select name="status" class="w-full rounded-md border-slate-300 shadow-sm" required><option value='active' {{ (old('status') == 'active') ? 'selected' : '' }}>active</option><option value='inactive' {{ (old('status') == 'inactive') ? 'selected' : '' }}>inactive</option></select>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Save</button>
<a href="{{ route('categories.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@endsection
