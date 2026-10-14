@extends('layouts.app')
@section('title', 'AccountingJournal Details')
@section('content')
<h1 class="text-xl font-semibold text-slate-800 mb-4">AccountingJournal #{{ $accounting_journal->id }}</h1>
<div class="bg-white shadow rounded-lg p-6 max-w-2xl">
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Journal No</span><span class='text-slate-800'>{{ $accounting_journal->journal_no }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Journal Type</span><span class='text-slate-800'>{{ $accounting_journal->journal_type }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Reference No</span><span class='text-slate-800'>{{ $accounting_journal->reference_no }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Reference Type</span><span class='text-slate-800'>{{ $accounting_journal->reference_type }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Reference Id</span><span class='text-slate-800'>{{ $accounting_journal->reference_id }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Journal Date</span><span class='text-slate-800'>{{ $accounting_journal->journal_date }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Description</span><span class='text-slate-800'>{{ $accounting_journal->description }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Branch Id</span><span class='text-slate-800'>{{ $accounting_journal->branch?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created By</span><span class='text-slate-800'>{{ $accounting_journal->createdBy?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Is Posted</span><span class='text-slate-800'>{{ $accounting_journal->is_posted ? 'Yes' : 'No' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated By</span><span class='text-slate-800'>{{ $accounting_journal->updatedBy?->name ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Created At</span><span class='text-slate-800'>{{ $accounting_journal->created_at?->format('d M Y H:i') ?? '—' }}</span></div>
<div class='py-2 border-b border-slate-100'><span class='text-xs uppercase text-slate-400 block'>Updated At</span><span class='text-slate-800'>{{ $accounting_journal->updated_at?->format('d M Y H:i') ?? '—' }}</span></div>
</div>
<a href="{{ route('accounting_journals.index') }}" class="inline-block mt-4 text-sm text-slate-500">&larr; Back to list</a>
@endsection
