@extends('layouts.app')
@section('title', 'Delivery Note Preview')
@section('content')
@php($recipient = $sale->deliveryRecipient())
<div class="max-w-6xl mx-auto">
    <div class="flex flex-col md:flex-row md:items-center justify-between gap-4 mb-5">
        <div>
            <div class="text-xs uppercase tracking-wider font-bold text-slate-400">Document Preview</div>
            <h1 class="text-xl font-extrabold text-slate-800 mt-1">
                <i class="fas fa-truck text-slate-400 mr-1"></i>
                Delivery Note {{ $sale->delivery_note_no }}
            </h1>
            <p class="text-sm text-slate-500 mt-1">Items and quantities for invoice {{ $sale->invoice_no }}. Review before downloading or emailing it.</p>
        </div>
        <div class="flex flex-wrap items-center gap-2">
            <a href="{{ route('sales.show', $sale->id) }}" class="px-4 py-2 rounded-lg border border-slate-300 bg-white text-slate-600 text-sm font-bold">
                <i class="fas fa-arrow-left"></i> Back
            </a>
            <button type="button" onclick="openModal('email-delivery-note-modal')"
                    class="px-4 py-2 rounded-lg bg-indigo-600 text-white text-sm font-bold hover:bg-indigo-700">
                <i class="fas fa-envelope"></i> Email
            </button>
            <a href="{{ route('sales.delivery-note-download', $sale->id) }}" class="px-4 py-2 rounded-lg bg-emerald-600 text-white text-sm font-bold hover:bg-emerald-700">
                <i class="fas fa-download"></i> Download PDF
            </a>
        </div>
    </div>

    @if(session('status'))
        <div class="mb-4 rounded-lg border border-emerald-200 bg-emerald-50 px-4 py-3 text-sm text-emerald-800">
            <i class="fas fa-circle-check mr-1"></i> {{ session('status') }}
        </div>
    @endif

    @if($errors->any())
        <div class="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-700">
            @foreach($errors->all() as $error)
                <div><i class="fas fa-circle-exclamation mr-1"></i> {{ $error }}</div>
            @endforeach
        </div>
    @endif

    <div class="bg-white border border-slate-200 rounded-2xl shadow-sm overflow-hidden">
        <div class="px-4 py-3 border-b border-slate-200 flex items-center gap-3">
            @if(!empty($siteSettings['system_logo']))
                <img src="{{ asset($siteSettings['system_logo']) }}" class="h-9 w-9 object-contain rounded-lg border border-slate-100 p-1 bg-white" alt="Logo">
            @endif
            <div>
                <strong class="block text-slate-800">{{ $branding['site_name'] }}</strong>
                <span class="text-xs text-slate-400">Delivery note preview · {{ $recipient['name'] }}</span>
            </div>
        </div>
        <iframe
            src="{{ route('sales.delivery-note-pdf', $sale->id) }}"
            class="w-full border-0 bg-slate-100"
            style="height:calc(100vh - 260px);min-height:620px"
            title="Delivery note {{ $sale->delivery_note_no }} preview"></iframe>
    </div>
</div>

<div id="email-delivery-note-modal" class="fixed inset-0 bg-black/40 {{ $errors->any() ? 'flex' : 'hidden' }} items-center justify-center z-30 p-4">
  <div class="bg-white rounded-xl shadow-xl w-full max-w-md p-6">
    <div class="flex items-center justify-between mb-4">
      <h2 class="font-semibold text-slate-800"><i class="fas fa-envelope text-indigo-500 mr-1"></i> Email Delivery Note</h2>
      <button type="button" onclick="closeModal('email-delivery-note-modal')" class="text-slate-400 text-xl leading-none">&times;</button>
    </div>
    <form method="POST" action="{{ route('sales.delivery-note-email', $sale->id) }}" class="space-y-4">
      @csrf
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Send to</label>
        <input type="email" name="email" required maxlength="255"
               value="{{ old('email', $recipient['email']) }}"
               placeholder="customer@example.com"
               class="w-full rounded-md border-slate-300 shadow-sm">
        @if(empty($recipient['email']))
          <p class="text-xs text-slate-400 mt-1">No email is saved for this customer. Enter one to send the note.</p>
        @endif
      </div>
      <div>
        <label class="block text-sm font-medium text-slate-700 mb-1">Message <span class="text-slate-400 font-normal">(optional)</span></label>
        <textarea name="message" rows="3" maxlength="1000"
                  class="w-full rounded-md border-slate-300 shadow-sm"
                  placeholder="e.g. Your goods will arrive between 2pm and 4pm.">{{ old('message') }}</textarea>
      </div>
      <p class="text-xs text-slate-400">The branded PDF ({{ $sale->delivery_note_no }}.pdf) is attached automatically.</p>
      <div class="flex justify-end gap-2">
        <button type="button" onclick="closeModal('email-delivery-note-modal')" class="px-4 py-2 rounded-md border border-slate-300 text-sm text-slate-600">Cancel</button>
        <button type="submit" class="px-4 py-2 rounded-md bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700">
          <i class="fas fa-paper-plane"></i> Send
        </button>
      </div>
    </form>
  </div>
</div>
@endsection
