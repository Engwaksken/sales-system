@component('mail::message')
@php($brandName = $siteSettings['site_name'] ?? config('mail.from.name', config('app.name', 'Sales System')))
# Order Update: {{ $stageLabel }}

Hello {{ $order->customer_name }},

{{ $customerMessage }}

**Order reference:** {{ $order->order_number }}  
**Current stage:** {{ $stageLabel }}  
**Order total:** UGX {{ number_format((float) $order->total_amount, 0) }}  
**Payment status:** {{ ucwords(str_replace('_', ' ', $order->payment_status)) }}

@if(in_array($order->status, ['ready_for_pickup', 'ready_for_delivery']))
Your order has now been transferred to our POS/payment desk for final invoicing and payment processing.
@endif

@if($order->status === 'ready_for_pickup')
Please wait for the pickup team to confirm the collection point and timing before travelling.
@endif

@if($order->status === 'ready_for_delivery')
Our team is preparing the order for dispatch to **{{ $order->delivery_location }}**.
@endif

@if($order->status === 'out_for_delivery')
Your order is on the way to **{{ $order->delivery_location }}**.
@endif

@if($order->status === 'completed')
Thank you for shopping with us. Your order is complete.
@endif

Thanks,<br>
{{ $order->branch?->name ?? $brandName }} · {{ $brandName }}
@endcomponent
