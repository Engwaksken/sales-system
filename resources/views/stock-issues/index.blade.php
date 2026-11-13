@extends('layouts.app')
@section('title', 'Stock Issues')
@section('content')
<div class="mb-5 flex items-center justify-between"><h1 class="text-xl font-semibold text-slate-800">Stock issues</h1><a class="text-sm text-indigo-600" href="{{ route('stock.index') }}">Back to stock</a></div>
@if(session('status'))<div class="mb-4 rounded bg-emerald-50 p-3 text-emerald-800">{{ session('status') }}</div>@endif
<section class="mb-6 rounded-lg bg-white p-5 shadow">
    <h2 class="mb-1 font-semibold">Record stock used or written off</h2>
    <p class="mb-4 text-sm text-slate-500">This reduces inventory without recording a sale, revenue, or cash received.</p>
    <form method="POST" action="{{ route('stock-issues.store') }}" class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">@csrf
        <label class="text-sm">Type<select name="type" required class="mt-1 block w-full rounded border-slate-300">@foreach($types as $key => $label)<option value="{{ $key }}">{{ $label }}</option>@endforeach</select></label>
        <label class="text-sm">Product<select name="product_id" required class="mt-1 block w-full rounded border-slate-300"><option value="">Choose product</option>@foreach($products as $product)<option value="{{ $product->id }}">{{ $product->name }}</option>@endforeach</select></label>
        <label class="text-sm">Branch<select name="branch_id" required class="mt-1 block w-full rounded border-slate-300"><option value="">Choose branch</option>@foreach($branches as $branch)<option value="{{ $branch->id }}">{{ $branch->name }}</option>@endforeach</select></label>
        <label class="text-sm">Quantity<input type="number" name="quantity" min="1" step="1" required class="mt-1 block w-full rounded border-slate-300"></label>
        <label class="text-sm sm:col-span-2">Reason / notes<input name="reason" maxlength="1000" class="mt-1 block w-full rounded border-slate-300" placeholder="Optional details"></label>
        <div><button class="rounded bg-indigo-600 px-4 py-2 text-sm text-white hover:bg-indigo-700">Record issue</button></div>
        @error('quantity')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        @foreach(['product_id','branch_id','type'] as $field)@error($field)<p class="text-sm text-red-600">{{ $message }}</p>@enderror @endforeach
    </form>
</section>
<section class="overflow-x-auto rounded-lg bg-white shadow"><table class="min-w-full text-sm"><thead class="bg-slate-50 text-left"><tr><th class="p-3">Date</th><th class="p-3">Type</th><th class="p-3">Product</th><th class="p-3">Branch</th><th class="p-3">Qty</th><th class="p-3">Recorded by</th><th class="p-3">Reason</th></tr></thead><tbody>@forelse($issues as $issue)<tr class="border-t"><td class="p-3">{{ $issue->created_at->format('d M Y H:i') }}</td><td class="p-3">{{ $types[$issue->type] }}</td><td class="p-3">{{ $issue->product?->name }}</td><td class="p-3">{{ $issue->branch?->name }}</td><td class="p-3">{{ $issue->quantity }}</td><td class="p-3">{{ $issue->user?->name ?? '—' }}</td><td class="p-3">{{ $issue->reason ?: '—' }}</td></tr>@empty<tr><td colspan="7" class="p-5 text-center text-slate-500">No stock issues recorded yet.</td></tr>@endforelse</tbody></table><div class="p-3">{{ $issues->links() }}</div></section>
@endsection
