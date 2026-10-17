@extends('layouts.app')
@section('title', 'Edit SaleItem')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">Edit SaleItem</h1>
@if($errors->any())
<div data-flash class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('sale_items.update', $sale_item->id) }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf @method('PUT')
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Sale Id</label>
  <select name="sale_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Sale Id --</option>
    @foreach($sale_options as $opt)
    <option value="{{ $opt->id }}" {{ ($sale_item->sale_id == $opt->id) ? 'selected' : '' }}>{{ $opt->id }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Product Id</label>
  <select name="product_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Product Id --</option>
    @foreach($product_options as $opt)
    <option value="{{ $opt->id }}" {{ ($sale_item->product_id == $opt->id) ? 'selected' : '' }}>{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Quantity</label>
  <input type="number" name="quantity" value="{{ $sale_item->quantity }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Price</label>
  <input type="number" step='0.01' name="price" value="{{ $sale_item->price }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Vat Amount</label>
  <input type="number" step='0.01' name="vat_amount" value="{{ $sale_item->vat_amount }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Total</label>
  <input type="number" step='0.01' name="total" value="{{ $sale_item->total }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Update</button>
<a href="{{ route('sale_items.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@endsection
