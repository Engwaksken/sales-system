@component('mail::message')
@php($brandName = $siteSettings['site_name'] ?? config('mail.from.name', config('app.name', 'Sales System')))
# Payment Received

We've received your payment. Receipt **{{ $receiptNo }}** is attached to this email.

**Amount:** UGX {{ number_format((float) $amount, 0) }}
**Method:** {{ $method }}

Thanks,<br>
{{ $brandName }}
@endcomponent
