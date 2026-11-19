@props(['url'])
@php($brand = \App\Support\Brand::get())
<tr>
<td class="header" style="padding: 28px 0 20px; text-align: center;">
<a href="{{ $url }}" style="display: inline-block; text-decoration: none;">
@if ($brand['logo'])
<img src="{{ $brand['logo'] }}" alt="{{ $brand['name'] }}" style="display: block; margin: 0 auto; max-width: 170px; max-height: 64px; height: auto; border: 0;">
@else
<span style="display: inline-block; padding: 10px 18px; border-radius: 12px; background: {{ $brand['primary'] }}; color: {{ $brand['onPrimary'] }}; font-size: 18px; font-weight: bold;">{{ $brand['name'] }}</span>
@endif
</a>
</td>
</tr>
