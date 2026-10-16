@component('mail::message')
@php($brandName = $siteSettings['site_name'] ?? config('mail.from.name', config('app.name', 'Sales System')))
# Your Invoice

Thank you for your purchase. Invoice **{{ $sale->invoice_no }}** is attached to this email.

**Date:** {{ $sale->sale_date }}
**Total:** UGX {{ number_format($sale->total_amount, 0) }}
**Payment method:** {{ $sale->payment_method }}

If you have any questions about this invoice, just reply to this email.

Thanks,<br>
{{ $brandName }}
@endcomponent
