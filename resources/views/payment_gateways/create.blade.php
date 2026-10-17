@extends('layouts.app')
@section('title', 'New PaymentGateway')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">New PaymentGateway</h1>
@if($errors->any())
<div data-flash class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('payment_gateways.store') }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Gateway Name</label>
  <input type="text" name="gateway_name" value="{{ old('gateway_name') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Gateway Code</label>
  <input type="text" name="gateway_code" value="{{ old('gateway_code') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Display Name</label>
  <input type="text" name="display_name" value="{{ old('display_name') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Provider Type</label>
  <select name="provider_type" class="w-full rounded-md border-slate-300 shadow-sm" required><option value='mobile_money' {{ (old('provider_type') == 'mobile_money') ? 'selected' : '' }}>mobile_money</option><option value='card' {{ (old('provider_type') == 'card') ? 'selected' : '' }}>card</option><option value='bank' {{ (old('provider_type') == 'bank') ? 'selected' : '' }}>bank</option><option value='wallet' {{ (old('provider_type') == 'wallet') ? 'selected' : '' }}>wallet</option><option value='mixed' {{ (old('provider_type') == 'mixed') ? 'selected' : '' }}>mixed</option></select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
  <textarea name="description" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" >{{ old('description') }}</textarea>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="is_active" value="0">
  <input type="checkbox" name="is_active" value="1" {{ old('is_active') ? 'checked' : '' }} class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Is Active</label>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="is_default" value="0">
  <input type="checkbox" name="is_default" value="1" {{ old('is_default') ? 'checked' : '' }} class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Is Default</label>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="sandbox_mode" value="0">
  <input type="checkbox" name="sandbox_mode" value="1" {{ old('sandbox_mode') ? 'checked' : '' }} class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Sandbox Mode</label>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Token Url</label>
  <input type="text" name="token_url" value="{{ old('token_url') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Collect Url</label>
  <input type="text" name="collect_url" value="{{ old('collect_url') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Base Url</label>
  <input type="text" name="base_url" value="{{ old('base_url') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Status Url</label>
  <input type="text" name="status_url" value="{{ old('status_url') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Client Id</label>
  <input type="text" name="client_id" value="{{ old('client_id') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Client Secret</label>
  <input type="text" name="client_secret" value="{{ old('client_secret') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Wallet Guid</label>
  <input type="text" name="wallet_guid" value="{{ old('wallet_guid') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Callback Url</label>
  <input type="text" name="callback_url" value="{{ old('callback_url') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Return Url</label>
  <input type="text" name="return_url" value="{{ old('return_url') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="supports_collection" value="0">
  <input type="checkbox" name="supports_collection" value="1" {{ old('supports_collection') ? 'checked' : '' }} class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Supports Collection</label>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="supports_disbursement" value="0">
  <input type="checkbox" name="supports_disbursement" value="1" {{ old('supports_disbursement') ? 'checked' : '' }} class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Supports Disbursement</label>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="supports_mtn" value="0">
  <input type="checkbox" name="supports_mtn" value="1" {{ old('supports_mtn') ? 'checked' : '' }} class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Supports Mtn</label>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="supports_airtel" value="0">
  <input type="checkbox" name="supports_airtel" value="1" {{ old('supports_airtel') ? 'checked' : '' }} class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Supports Airtel</label>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Created By</label>
  <select name="created_by" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Created By --</option>
    @foreach($created_by_options as $opt)
    <option value="{{ $opt->id }}" {{ (old('created_by') == $opt->id) ? 'selected' : '' }}>{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Updated By</label>
  <select name="updated_by" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Updated By --</option>
    @foreach($updated_by_options as $opt)
    <option value="{{ $opt->id }}" {{ (old('updated_by') == $opt->id) ? 'selected' : '' }}>{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Save</button>
<a href="{{ route('payment_gateways.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@endsection
