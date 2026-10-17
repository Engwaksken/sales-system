@extends('layouts.app')
@section('title', 'Edit Branch')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">Edit Branch</h1>
@if($errors->any())
<div data-flash class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('branches.update', $branch->id) }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf @method('PUT')
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Name</label>
  <input type="text" name="name" value="{{ $branch->name }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Location</label>
  <input type="text" name="location" value="{{ $branch->location }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Contact</label>
  <input type="text" name="contact" value="{{ $branch->contact }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Branch Email</label>
  <input type="text" name="branch_email" value="{{ $branch->branch_email }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Update</button>
<a href="{{ route('branches.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@endsection
