@extends('layouts.app')
@section('title', 'New PaymentSetting')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">New PaymentSetting</h1>
@if($errors->any())
<div data-flash class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('payment_settings.store') }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Setting Key</label>
  <input type="text" name="setting_key" value="{{ old('setting_key') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Setting Value</label>
  <textarea name="setting_value" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" required>{{ old('setting_value') }}</textarea>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Save</button>
<a href="{{ route('payment_settings.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@endsection
