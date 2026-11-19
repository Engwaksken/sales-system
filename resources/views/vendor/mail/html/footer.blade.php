@php($brand = \App\Support\Brand::get())
<tr>
<td>
<table class="footer" align="center" width="570" cellpadding="0" cellspacing="0" role="presentation">
<tr>
<td class="content-cell" align="center">
{{ Illuminate\Mail\Markdown::parse($slot) }}
<p style="margin-top: 6px;"><a href="{{ $brand['url'] }}" style="color: {{ $brand['primary'] }}; text-decoration: none;">Visit our shop</a></p>
</td>
</tr>
</table>
</td>
</tr>
