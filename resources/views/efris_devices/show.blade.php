@extends('layouts.app')
@section('title', 'EfrisDevice Details')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">EfrisDevice #{{ $efris_device->id }}</h1>
<div class="bg-white shadow rounded-lg p-6 max-w-2xl">
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Config Id</span><span class='text-slate-800'>{{ $efris_device->config?->id ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Branch Id</span><span class='text-slate-800'>{{ $efris_device->branch?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Device Serial No</span><span class='text-slate-800'>{{ $efris_device->device_serial_no }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Device Type</span><span class='text-slate-800'>{{ $efris_device->device_type }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Device Name</span><span class='text-slate-800'>{{ $efris_device->device_name }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Activation Code</span><span class='text-slate-800'>{{ $efris_device->activation_code }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Activation Status</span><span class='text-slate-800'>{{ $efris_device->activation_status }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Activated At</span><span class='text-slate-800'>{{ $efris_device->activated_at }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Invoice Counter</span><span class='text-slate-800'>{{ $efris_device->invoice_counter }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Notes</span><span class='text-slate-800'>{{ $efris_device->notes }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created By</span><span class='text-slate-800'>{{ $efris_device->createdBy?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated By</span><span class='text-slate-800'>{{ $efris_device->updatedBy?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created At</span><span class='text-slate-800'>{{ $efris_device->created_at?->format('d M Y H:i') ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated At</span><span class='text-slate-800'>{{ $efris_device->updated_at?->format('d M Y H:i') ?? '—' }}</span></div>
</div>
<a href="{{ route('efris_devices.index') }}" class="inline-block mt-4 text-sm text-slate-500">&larr; Back to list</a>
@endsection
