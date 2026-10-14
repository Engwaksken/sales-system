@extends('layouts.app')
@section('title', 'Stock Details')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">Stock #{{ $stock->id }}</h1>
<div class="bg-white shadow rounded-lg p-6 max-w-2xl">
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Supplier Id</span><span class='text-slate-800'>{{ $stock->supplier?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Product Id</span><span class='text-slate-800'>{{ $stock->product?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Branch Id</span><span class='text-slate-800'>{{ $stock->branch?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Total Stock</span><span class='text-slate-800'>{{ $stock->total_stock }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Quantity</span><span class='text-slate-800'>{{ $stock->quantity }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Unit Type</span><span class='text-slate-800'>{{ $stock->unit_type }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Cost Price</span><span class='text-slate-800'>{{ $stock->cost_price }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Expiry Date</span><span class='text-slate-800'>{{ $stock->expiry_date }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Selling Price</span><span class='text-slate-800'>{{ $stock->selling_price }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created By</span><span class='text-slate-800'>{{ $stock->createdBy?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated By</span><span class='text-slate-800'>{{ $stock->updatedBy?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created At</span><span class='text-slate-800'>{{ $stock->created_at?->format('d M Y H:i') ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated At</span><span class='text-slate-800'>{{ $stock->updated_at?->format('d M Y H:i') ?? '—' }}</span></div>
</div>
<a href="{{ route('stock.index') }}" class="inline-block mt-4 text-sm text-slate-500">&larr; Back to list</a>
@endsection
