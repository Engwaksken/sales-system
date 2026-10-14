@extends('layouts.app')
@section('title', 'ExpenseCategory Details')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">ExpenseCategory #{{ $expense_category->id }}</h1>
<div class="bg-white shadow rounded-lg p-6 max-w-2xl">
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Name</span><span class='text-slate-800'>{{ $expense_category->name }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Status</span><span class='text-slate-800'>{{ $expense_category->status ? 'Yes' : 'No' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created By</span><span class='text-slate-800'>{{ $expense_category->createdBy?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated By</span><span class='text-slate-800'>{{ $expense_category->updatedBy?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created At</span><span class='text-slate-800'>{{ $expense_category->created_at?->format('d M Y H:i') ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated At</span><span class='text-slate-800'>{{ $expense_category->updated_at?->format('d M Y H:i') ?? '—' }}</span></div>
</div>
<a href="{{ route('expense_categories.index') }}" class="inline-block mt-4 text-sm text-slate-500">&larr; Back to list</a>
@endsection
