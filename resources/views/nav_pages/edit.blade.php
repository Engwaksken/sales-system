@extends('layouts.app')
@section('title', 'Edit NavPage')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">Edit NavPage</h1>
@if($errors->any())
<div data-flash class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('nav_pages.update', $nav_page->id) }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf @method('PUT')
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Label</label>
  <input type="text" name="label" value="{{ $nav_page->label }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Url</label>
  <input type="text" name="url" value="{{ $nav_page->url }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Icon</label>
  <input type="text" name="icon" value="{{ $nav_page->icon }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Parent Id</label>
  <select name="parent_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Parent Id --</option>
    @foreach($parent_options as $opt)
    <option value="{{ $opt->id }}" {{ ($nav_page->parent_id == $opt->id) ? 'selected' : '' }}>{{ $opt->label }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Sort Order</label>
  <input type="number" name="sort_order" value="{{ $nav_page->sort_order }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Permission Key</label>
  <input type="text" name="permission_key" value="{{ $nav_page->permission_key }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="is_active" value="0">
  <input type="checkbox" name="is_active" value="1" {{ $nav_page->is_active ? 'checked' : '' }} class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Is Active</label>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Update</button>
<a href="{{ route('nav_pages.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@endsection
