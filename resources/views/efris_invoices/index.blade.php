@extends('layouts.app')
@section('title', 'EFRIS Invoices')
@section('content')
<div class="flex items-center justify-between mb-4">
    <h1 class="text-xl font-semibold text-slate-800">EFRIS Invoices</h1>
    <span class="text-sm text-slate-400">Submitted from a Sale's detail page &mdash; see "Submit to EFRIS"</span>
</div>
@if(session('status'))
<div class="mb-4 rounded-md bg-emerald-50 text-emerald-700 px-4 py-2 text-sm">{{ session('status') }}</div>
@endif
<div class="bg-white shadow rounded-lg overflow-x-auto">
<table class="min-w-full divide-y divide-slate-200">
<thead class="bg-slate-50"><tr>
    <th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Invoice No</th>
    <th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>TIN</th>
    <th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Gross Amount</th>
    <th class='px-4 py-2 text-left text-xs font-semibold text-slate-500 uppercase'>Status</th>
    <th class="px-4 py-2"></th>
</tr></thead>
<tbody class="divide-y divide-slate-100">
@forelse($items as $row)
<tr>
    <td class='px-4 py-2 text-sm text-slate-700'>{{ $row->invoice_no }}</td>
    <td class='px-4 py-2 text-sm text-slate-700'>{{ $row->tin }}</td>
    <td class='px-4 py-2 text-sm text-slate-700'>{{ number_format($row->gross_amount, 2) }}</td>
    <td class='px-4 py-2 text-sm text-slate-700'>{{ $row->status }}</td>
    <td class="px-4 py-2 text-right text-sm">
        <a href="{{ route('efris_invoices.show', $row->id) }}" class="text-slate-500 hover:text-slate-700">View</a>
    </td>
</tr>
@empty
<tr><td colspan="5" class="px-4 py-6 text-center text-sm text-slate-400">No EFRIS invoices submitted yet.</td></tr>
@endforelse
</tbody>
</table>
</div>
<div class="mt-4">@include('partials.table-pagination', ['paginator' => $items])</div>
@endsection
