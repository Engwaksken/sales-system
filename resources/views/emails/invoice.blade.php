@component('mail::message')
# Your Invoice

Thank you for your purchase. Invoice **{{ $sale->invoice_no }}** is attached to this email.

**Date:** {{ $sale->sale_date }}
**Total:** UGX {{ number_format($sale->total_amount, 0) }}
**Payment method:** {{ $sale->payment_method }}

If you have any questions about this invoice, just reply to this email.

Thanks,<br>
{{ config('app.name') }}
@endcomponent
