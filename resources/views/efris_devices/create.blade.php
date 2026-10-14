@extends('layouts.app')
@section('title', 'New EfrisDevice')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">New EfrisDevice</h1>
@if($errors->any())
<div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('efris_devices.store') }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Config Id</label>
  <select name="config_id" class="w-full rounded-md border-slate-300 shadow-sm" required>
    <option value="">-- Select Config Id --</option>
    @foreach($config_options as $opt)
    <option value="{{ $opt->id }}" {{ (old('config_id') == $opt->id) ? 'selected' : '' }}>{{ $opt->id }}</option>
    @endforeach
  </select>
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
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Device Serial No</label>
  <input type="text" name="device_serial_no" value="{{ old('device_serial_no') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Device Type</label>
  <input type="text" name="device_type" value="{{ old('device_type') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Device Name</label>
  <input type="text" name="device_name" value="{{ old('device_name') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Activation Code</label>
  <input type="text" name="activation_code" value="{{ old('activation_code') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Activation Status</label>
  <select name="activation_status" class="w-full rounded-md border-slate-300 shadow-sm" required><option value='pending' {{ (old('activation_status') == 'pending') ? 'selected' : '' }}>pending</option><option value='activated' {{ (old('activation_status') == 'activated') ? 'selected' : '' }}>activated</option><option value='suspended' {{ (old('activation_status') == 'suspended') ? 'selected' : '' }}>suspended</option></select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Activated At</label>
  <input type="datetime-local" name="activated_at" value="{{ old('activated_at') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Invoice Counter</label>
  <input type="number" name="invoice_counter" value="{{ old('invoice_counter') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Notes</label>
  <textarea name="notes" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" >{{ old('notes') }}</textarea>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Save</button>
<a href="{{ route('efris_devices.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@endsection
