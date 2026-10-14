@component('mail::message')
# Thanks for your order, {{ $order->customer_name }}!

Your order **{{ $order->order_number }}** from **{{ $branch->name }}** has been received.

@component('mail::table')
| Item | Qty | Price | Subtotal |
|:-----|:---:|------:|---------:|
@foreach ($order->order_items as $item)
| {{ $item->product_name }} | {{ $item->quantity }} | {{ number_format($item->price, 0) }} | {{ number_format($item->subtotal, 0) }} |
@endforeach
@endcomponent

**Delivery fee:** UGX {{ number_format($order->delivery_fee, 0) }}
**Total:** UGX {{ number_format($order->total_amount, 0) }}

**Delivery to:** {{ $order->delivery_location }}
**Payment method:** {{ ucwords(str_replace('-', ' ', $order->payment_method)) }}

@if ($order->payment_method === 'mobile-money')
You'll receive a mobile money prompt on your phone shortly to complete payment.
@else
Please have payment ready for delivery.
@endif

Thanks,<br>
{{ $branch->name }}
@endcomponent
