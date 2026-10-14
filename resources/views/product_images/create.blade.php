@extends('layouts.app')
@section('title', 'New ProductImage')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">New ProductImage</h1>
@if($errors->any())
<div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('product_images.store') }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Product Id</label>
  <select name="product_id" class="w-full rounded-md border-slate-300 shadow-sm" required>
    <option value="">-- Select Product Id --</option>
    @foreach($product_options as $opt)
    <option value="{{ $opt->id }}" {{ (old('product_id') == $opt->id) ? 'selected' : '' }}>{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Image Path</label>
  <input type="text" name="image_path" value="{{ old('image_path') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="is_main" value="0">
  <input type="checkbox" name="is_main" value="1" {{ old('is_main') ? 'checked' : '' }} class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Is Main</label>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="is_primary" value="0">
  <input type="checkbox" name="is_primary" value="1" {{ old('is_primary') ? 'checked' : '' }} class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Is Primary</label>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Save</button>
<a href="{{ route('product_images.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@endsection
