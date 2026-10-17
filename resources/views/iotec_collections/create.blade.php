@extends('layouts.app')
@section('title', 'New IotecCollection')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">New IotecCollection</h1>
@if($errors->any())
<div data-flash class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('iotec_collections.store') }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Order Id</label>
  <input type="text" name="order_id" value="{{ old('order_id') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Phone</label>
  <input type="text" name="phone" value="{{ old('phone') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Network</label>
  <input type="text" name="network" value="{{ old('network') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Amount</label>
  <input type="number" step='0.01' name="amount" value="{{ old('amount') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Iotec Ref</label>
  <input type="text" name="iotec_ref" value="{{ old('iotec_ref') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Status</label>
  <input type="text" name="status" value="{{ old('status') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Raw Response</label>
  <textarea name="raw_response" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" >{{ old('raw_response') }}</textarea>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Save</button>
<a href="{{ route('iotec_collections.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@endsection
