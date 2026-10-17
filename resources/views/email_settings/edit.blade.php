@extends('layouts.app')
@section('title', 'Edit EmailSetting')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">Edit EmailSetting</h1>
@if($errors->any())
<div data-flash class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('email_settings.update', $email_setting->id) }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf @method('PUT')
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Smtp Host</label>
  <input type="text" name="smtp_host" value="{{ $email_setting->smtp_host }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Smtp Port</label>
  <input type="number" name="smtp_port" value="{{ $email_setting->smtp_port }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Smtp User</label>
  <input type="text" name="smtp_user" value="{{ $email_setting->smtp_user }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Smtp Password</label>
  <div class="relative"><input type="password" name="smtp_password" autocomplete="new-password" class="w-full rounded-md border-slate-300 shadow-sm pr-10" required><button type="button" data-password-toggle class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700" aria-label="Show password"><i class="fas fa-eye"></i></button></div>smtp_password }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Smtp Secure</label>
  <input type="text" name="smtp_secure" value="{{ $email_setting->smtp_secure }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">From Email</label>
  <input type="text" name="from_email" value="{{ $email_setting->from_email }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">From Name</label>
  <input type="text" name="from_name" value="{{ $email_setting->from_name }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Update</button>
<a href="{{ route('email_settings.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@push('scripts')
@include('partials.password-toggle-script')
@endpush
@endsection
