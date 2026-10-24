@extends('layouts.app')
@section('title', 'New Product')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">New Product</h1>
@if($errors->any())
<div data-flash class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('products.store') }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
  <input type="text" name="name" value="{{ old('name') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Sku</label>
  <input type="text" name="sku" value="{{ old('sku') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Barcode</label>
  <input type="text" name="barcode" value="{{ old('barcode') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Branch Id</label>
  <select name="branch_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Branch Id --</option>
    @foreach($branch_options as $opt)
    <option value="{{ $opt->id }}" {{ (old('branch_id') == $opt->id) ? 'selected' : '' }}>{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Category Id</label>
  <select name="category_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Category Id --</option>
    @foreach($category_options as $opt)
    <option value="{{ $opt->id }}" {{ (old('category_id') == $opt->id) ? 'selected' : '' }}>{{ $opt->category_name }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
  <textarea name="description" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" >{{ old('description') }}</textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Image Url</label>
  <input type="text" name="image_url" value="{{ old('image_url') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="is_active" value="0">
  <input type="checkbox" name="is_active" value="1" {{ old('is_active') ? 'checked' : '' }} class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Is Active</label>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Save</button>
<a href="{{ route('products.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@endsection
