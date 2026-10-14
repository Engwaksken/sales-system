@extends('layouts.app')
@section('title', 'New EmailSetting')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">New EmailSetting</h1>
@if($errors->any())
<div class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('email_settings.store') }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Smtp Host</label>
  <input type="text" name="smtp_host" value="{{ old('smtp_host') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Smtp Port</label>
  <input type="number" name="smtp_port" value="{{ old('smtp_port') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Smtp User</label>
  <input type="text" name="smtp_user" value="{{ old('smtp_user') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Smtp Password</label>
  <div class="relative"><input type="password" name="smtp_password" autocomplete="new-password" class="w-full rounded-md border-slate-300 shadow-sm pr-10" required><button type="button" data-password-toggle class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700" aria-label="Show password"><i class="fas fa-eye"></i></button></div>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Smtp Secure</label>
  <input type="text" name="smtp_secure" value="{{ old('smtp_secure') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">From Email</label>
  <input type="text" name="from_email" value="{{ old('from_email') }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">From Name</label>
  <input type="text" name="from_name" value="{{ old('from_name') }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Save</button>
<a href="{{ route('email_settings.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@push('scripts')
@include('partials.password-toggle-script')
@endpush
@endsection
