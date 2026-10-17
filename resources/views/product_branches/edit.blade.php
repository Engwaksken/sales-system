@extends('layouts.app')
@section('title', 'Edit ProductBranch')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">Edit ProductBranch</h1>
@if($errors->any())
<div data-flash class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('product_branches.update', $product_branch->id) }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf @method('PUT')
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Product Id</label>
  <select name="product_id" class="w-full rounded-md border-slate-300 shadow-sm" required>
    <option value="">-- Select Product Id --</option>
    @foreach($product_options as $opt)
    <option value="{{ $opt->id }}" {{ ($product_branch->product_id == $opt->id) ? 'selected' : '' }}>{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Branch Id</label>
  <select name="branch_id" class="w-full rounded-md border-slate-300 shadow-sm" required>
    <option value="">-- Select Branch Id --</option>
    @foreach($branch_options as $opt)
    <option value="{{ $opt->id }}" {{ ($product_branch->branch_id == $opt->id) ? 'selected' : '' }}>{{ $opt->name }}</option>
    @endforeach
  </select>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Update</button>
<a href="{{ route('product_branches.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@endsection
