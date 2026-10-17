@extends('layouts.app')
@section('title', 'Edit OrderLog')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">Edit OrderLog</h1>
@if($errors->any())
<div data-flash class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('order_logs.update', $order_log->id) }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf @method('PUT')
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Order Id</label>
  <select name="order_id" class="w-full rounded-md border-slate-300 shadow-sm" required>
    <option value="">-- Select Order Id --</option>
    @foreach($order_options as $opt)
    <option value="{{ $opt->id }}" {{ ($order_log->order_id == $opt->id) ? 'selected' : '' }}>{{ $opt->id }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Action</label>
  <input type="text" name="action" value="{{ $order_log->action }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
  <textarea name="description" rows="3" class="w-full rounded-md border-slate-300 shadow-sm" >{{ $order_log->description }}</textarea>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Created By</label>
  <select name="created_by" class="w-full rounded-md border-slate-300 shadow-sm" >
    <option value="">-- Select Created By --</option>
    @foreach($created_by_options as $opt)
    <option value="{{ $opt->id }}" {{ ($order_log->created_by == $opt->id) ? 'selected' : '' }}>{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Update</button>
<a href="{{ route('order_logs.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@endsection
