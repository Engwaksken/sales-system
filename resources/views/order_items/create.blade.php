@extends('layouts.app')
@section('title', 'New OrderItem')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">New OrderItem</h1>
@if($errors->any())
<div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('order_items.store') }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Order Id</label>
  <select name="order_id" class="w-full rounded-md border-slate-300 shadow-sm" required>
    <option value="">-- Select Order Id --</option>
    @foreach($order_options as $opt)
    <option value="{{ $opt->id }}" {{ (old('order_id') == $opt->id) ? 'selected' : '' }}>{{ $opt->id }}</option>
    @endforeach
  </select>
</div>
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
  <label class="block text-sm font-medium text-slate-700 mb-1">Product Name</label>
  <input type="text" name="product_name" value="{{ old('product_name') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Sku</label>
  <input type="text" name="sku" value="{{ old('sku') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Quantity</label>
  <input type="number" name="quantity" value="{{ old('quantity') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Price</label>
  <input type="number" step='0.01' name="price" value="{{ old('price') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Subtotal</label>
  <input type="number" step='0.01' name="subtotal" value="{{ old('subtotal') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Cost Price</label>
  <input type="number" step='0.01' name="cost_price" value="{{ old('cost_price') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Save</button>
<a href="{{ route('order_items.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@endsection
