@component('mail::message')
@php($brandName = $siteSettings['site_name'] ?? config('mail.from.name', config('app.name', 'Sales System')))
# Your Delivery Note

Delivery note **{{ $sale->delivery_note_no }}** for invoice **{{ $sale->invoice_no }}** is attached to this email.

**Date:** {{ optional($sale->sale_date)->format('d M Y') ?? $sale->sale_date }}
**Items:** {{ $sale->sale_items->count() }} line(s), {{ (int) $sale->sale_items->sum('quantity') }} unit(s)

@if(!empty($note))
{{ $note }}

@endif
Please check the goods against the attached note on delivery. If anything is missing or damaged, just reply to this email.

Thanks,<br>
{{ $brandName }}
@endcomponent
