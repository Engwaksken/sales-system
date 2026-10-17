@extends('layouts.app')
@section('title', 'Edit User')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">Edit User</h1>
@if($errors->any())
<div data-flash class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('users.update', $user->id) }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf @method('PUT')
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
  <input type="text" name="name" value="{{ $user->name }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Email</label>
  <input type="text" name="email" value="{{ $user->email }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Password</label>
  <div class="relative"><input type="password" name="password" autocomplete="new-password" class="w-full rounded-md border-slate-300 shadow-sm pr-10"><button type="button" data-password-toggle class="absolute right-2 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-700" aria-label="Show password"><i class="fas fa-eye"></i></button></div>password }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Role</label>
  <select name="role" class="w-full rounded-md border-slate-300 shadow-sm" required><option value='admin' {{ ($user->role == 'admin') ? 'selected' : '' }}>admin</option><option value='cashier' {{ ($user->role == 'cashier') ? 'selected' : '' }}>cashier</option><option value='customer' {{ ($user->role == 'customer') ? 'selected' : '' }}>customer</option><option value='manager' {{ ($user->role == 'manager') ? 'selected' : '' }}>manager</option></select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Branch Id</label>
  <select name="branch_id" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Branch Id --</option>
    @foreach($branch_options as $opt)
    <option value="{{ $opt->id }}" {{ ($user->branch_id == $opt->id) ? 'selected' : '' }}>{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Profile Pic</label>
  <input type="text" name="profile_pic" value="{{ $user->profile_pic }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Verification Code</label>
  <input type="text" name="verification_code" value="{{ $user->verification_code }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Code Expires</label>
  <input type="datetime-local" name="code_expires" value="{{ $user->code_expires }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Remember Token</label>
  <input type="text" name="remember_token" value="{{ $user->remember_token }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Update</button>
<a href="{{ route('users.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@push('scripts')
@include('partials.password-toggle-script')
@endpush
@endsection
