@php($brand = \App\Support\Brand::get())
@php($branch = $order->branch)
@php($cancelled = $order->status === 'cancelled')
@component('mail::message')
# {{ $cancelled ? 'Your order was cancelled' : 'Your order is '.strtolower($stageLabel) }}

Hello {{ $order->customer_name }},

{{ $customerMessage }}

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 8px 0 22px; border-radius: 12px; background: {{ $cancelled ? '#fef2f2' : $brand['primarySoft'] }};">
<tr>
<td style="padding: 16px 18px;">
<span style="display: block; font-size: 12px; text-transform: uppercase; letter-spacing: .06em; color: #64748b;">Current stage</span>
<span style="display: block; margin-top: 2px; font-size: 20px; font-weight: bold; color: {{ $cancelled ? '#b91c1c' : $brand['primary'] }};">{{ $stageLabel }}</span>
</td>
<td align="right" style="padding: 16px 18px;">
<span style="display: block; font-size: 12px; text-transform: uppercase; letter-spacing: .06em; color: #64748b;">Order</span>
<span style="display: block; margin-top: 2px; font-size: 15px; font-weight: bold; color: #0f172a;">{{ $order->order_number }}</span>
</td>
</tr>
</table>

**Order total:** UGX {{ number_format((float) $order->total_amount, 0) }}<br>
**Payment status:** {{ ucwords(str_replace('_', ' ', (string) $order->payment_status)) }}

@if ($order->status === 'ready_for_pickup')
Please wait for the pickup team to confirm the collection point and timing before travelling.
@elseif ($order->status === 'ready_for_delivery')
Our team is preparing the order for dispatch to **{{ $order->delivery_location }}**.
@elseif ($order->status === 'out_for_delivery')
Your order is on the way to **{{ $order->delivery_location }}**.
@elseif ($order->status === 'completed')
Thank you for shopping with us.
@endif

@if ($order->order_number || $order->order_id)
@component('mail::button', ['url' => $order->trackingUrl()])
Track your order
@endcomponent
@endif

@include('emails.partials.order-help', ['branch' => $branch])

Thanks,<br>
{!! $branch ? '**'.e($branch->name).'** · ' : '' !!}{{ $brand['name'] }}
@endcomponent
