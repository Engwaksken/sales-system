@php
    $brand = \App\Support\Brand::get();
    $ways = [];
    if ($branch?->contact) {
        $ways[] = '<strong style="color: #0f172a;">'.e($branch->contact).'</strong>';
    }
    if ($branch?->branch_email) {
        $ways[] = '<a href="mailto:'.e($branch->branch_email).'" style="color: '.$brand['primary'].';">'.e($branch->branch_email).'</a>';
    }
@endphp
@if ($ways)
<p style="margin: 18px 0; padding: 12px 14px; border: 1px solid #e2e8f0; border-radius: 10px; font-size: 13px; color: #64748b;">
Questions about your order? Contact {{ $branch->name }} on {!! implode(' or ', $ways) !!} and quote your order reference.
</p>
@endif
