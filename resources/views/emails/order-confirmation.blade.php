@php($brand = \App\Support\Brand::get())
@component('mail::message')
# Thanks for your order, {{ $order->customer_name }}!

We've received your order from **{{ $branch->name }}** and will let you know as it moves along.

<table width="100%" cellpadding="0" cellspacing="0" role="presentation" style="margin: 8px 0 22px; border-radius: 12px; background: {{ $brand['primarySoft'] }};">
<tr>
<td style="padding: 16px 18px;">
<span style="display: block; font-size: 12px; text-transform: uppercase; letter-spacing: .06em; color: #64748b;">Order reference</span>
<span style="display: block; margin-top: 2px; font-size: 20px; font-weight: bold; color: {{ $brand['primary'] }};">{{ $order->order_number }}</span>
</td>
<td align="right" style="padding: 16px 18px;">
<span style="display: block; font-size: 12px; text-transform: uppercase; letter-spacing: .06em; color: #64748b;">Total</span>
<span style="display: block; margin-top: 2px; font-size: 20px; font-weight: bold; color: #0f172a;">UGX {{ number_format((float) $order->total_amount, 0) }}</span>
</td>
</tr>
</table>

@component('mail::table')
| Item | Qty | Price | Subtotal |
|:-----|:---:|------:|---------:|
@foreach ($order->order_items as $item)
| {{ $item->product_name }} | {{ $item->quantity }} | {{ number_format((float) $item->price, 0) }} | {{ number_format((float) $item->subtotal, 0) }} |
@endforeach
| | | Delivery | {{ number_format((float) $order->delivery_fee, 0) }} |
| | | **Total** | **UGX {{ number_format((float) $order->total_amount, 0) }}** |
@endcomponent

**Delivery to:** {{ $order->delivery_location }}{{ $order->delivery_zone ? ' ('.$order->delivery_zone.')' : '' }}<br>
**Payment method:** {{ ucwords(str_replace('-', ' ', (string) $order->payment_method)) }}

@if ($order->payment_method === 'mobile-money')
You'll receive a mobile money prompt on your phone shortly to complete payment.
@else
Please have payment ready when your order arrives.
@endif

@component('mail::button', ['url' => $order->trackingUrl()])
Track your order
@endcomponent

@include('emails.partials.order-help', ['branch' => $branch])

Thank you for shopping with us,<br>
**{{ $branch->name }}** | {{ $brand['name'] }}
@endcomponent
