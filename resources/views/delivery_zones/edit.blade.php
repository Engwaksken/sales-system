@extends('layouts.app')
@section('title', 'Edit DeliveryZone')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">Edit DeliveryZone</h1>
@if($errors->any())
<div data-flash class="mb-4 rounded-md bg-red-50 text-red-700 px-4 py-2 text-sm">
<ul>@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
</div>
@endif
<form method="POST" action="{{ route('delivery_zones.update', $delivery_zone->id) }}" class="bg-white shadow rounded-lg p-6 space-y-4 max-w-2xl">
@csrf @method('PUT')
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Zone Name</label>
  <input type="text" name="zone_name" value="{{ $delivery_zone->zone_name }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Fee</label>
  <input type="number" step='0.01' name="fee" value="{{ $delivery_zone->fee }}" class="w-full rounded-md border-slate-300 shadow-sm" required>
</div>
<div>
  <label class="block text-sm font-medium text-slate-700 mb-1">Description</label>
  <input type="text" name="description" value="{{ $delivery_zone->description }}" class="w-full rounded-md border-slate-300 shadow-sm" >
</div>
<div class="flex items-center gap-2">
  <input type="hidden" name="is_active" value="0">
  <input type="checkbox" name="is_active" value="1" {{ $delivery_zone->is_active ? 'checked' : '' }} class="rounded border-slate-300">
  <label class="text-sm font-medium text-slate-700">Is Active</label>
</div>
<div class="pt-2">
<button class="bg-indigo-600 text-white px-4 py-2 rounded-md text-sm hover:bg-indigo-700">Update</button>
<a href="{{ route('delivery_zones.index') }}" class="ml-2 text-sm text-slate-500">Cancel</a>
</div>
</form>
@endsection
