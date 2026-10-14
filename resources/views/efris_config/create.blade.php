@extends('layouts.app')
@section('title', 'New EfrisConfig')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">New EfrisConfig</h1>
@if($errors->any())
<div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('efris_config.store') }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf
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
  <label class="block text-sm font-medium text-slate-700 mb-1">Tin</label>
  <input type="text" name="tin" value="{{ old('tin') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Taxpayer Name</label>
  <input type="text" name="taxpayer_name" value="{{ old('taxpayer_name') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Taxpayer Address</label>
  <textarea name="taxpayer_address" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" >{{ old('taxpayer_address') }}</textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Taxpayer Email</label>
  <input type="text" name="taxpayer_email" value="{{ old('taxpayer_email') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Taxpayer Phone</label>
  <input type="text" name="taxpayer_phone" value="{{ old('taxpayer_phone') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Business Type</label>
  <input type="text" name="business_type" value="{{ old('business_type') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="vat_registered" value="0">
  <input type="checkbox" name="vat_registered" value="1" {{ old('vat_registered') ? 'checked' : '' }} class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Vat Registered</label>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Environment</label>
  <select name="environment" class="w-full rounded-md border-slate-300 shadow-sm" required><option value='sandbox' {{ (old('environment') == 'sandbox') ? 'selected' : '' }}>sandbox</option><option value='production' {{ (old('environment') == 'production') ? 'selected' : '' }}>production</option></select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Private Key Pem</label>
  <textarea name="private_key_pem" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" >{{ old('private_key_pem') }}</textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Public Key Pem</label>
  <textarea name="public_key_pem" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" >{{ old('public_key_pem') }}</textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Ura Public Key Pem</label>
  <textarea name="ura_public_key_pem" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" >{{ old('ura_public_key_pem') }}</textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Portal Username</label>
  <input type="text" name="portal_username" value="{{ old('portal_username') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Portal Password Enc</label>
  <textarea name="portal_password_enc" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" >{{ old('portal_password_enc') }}</textarea>
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="is_active" value="0">
  <input type="checkbox" name="is_active" value="1" {{ old('is_active') ? 'checked' : '' }} class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Is Active</label>
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
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Save</button>
<a href="{{ route('efris_config.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@endsection
