@component('mail::message')
# Payment Received

We've received your payment. Receipt **{{ $receiptNo }}** is attached to this email.

**Amount:** UGX {{ number_format((float) $amount, 0) }}
**Method:** {{ $method }}

Thanks,<br>
{{ config('app.name') }}
@endcomponent
