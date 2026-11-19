@props([
    'url',
    'color' => 'primary',
    'align' => 'center',
])
@php($brand = \App\Support\Brand::get())
@php([$bg, $fg] = match ($color) {
    'success', 'green' => ['#16a34a', '#ffffff'],
    'error', 'red' => ['#dc2626', '#ffffff'],
    default => [$brand['primary'], $brand['onPrimary']],
})
<table class="action" align="{{ $align }}" width="100%" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table width="100%" border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td align="{{ $align }}">
<table border="0" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td>
<a href="{{ $url }}" class="button" target="_blank" rel="noopener" style="display: inline-block; padding: 12px 26px; border-radius: 10px; background-color: {{ $bg }}; border: 1px solid {{ $bg }}; color: {{ $fg }}; font-weight: bold; text-decoration: none;">{!! $slot !!}</a>
</td>
</tr>
</table>
</td>
</tr>
</table>
</td>
</tr>
</table>
