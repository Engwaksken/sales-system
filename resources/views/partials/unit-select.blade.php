{{-- Unit dropdown for stock forms. Pass $value for the current unit (kept even if not in the list). --}}
@php($unitOptions = \App\Models\UnitType::options($value ?? null)->groupBy('category'))
<select name="unit_type" class="w-full rounded-md border-slate-300 shadow-sm">
  <option value="">Choose unit</option>
  @foreach($unitOptions as $group => $units)
    <optgroup label="{{ $group ?: 'Other' }}">
      @foreach($units as $unit)
        <option value="{{ $unit['value'] }}" @selected(strcasecmp((string) ($value ?? ''), $unit['value']) === 0)>{{ $unit['label'] }}</option>
      @endforeach
    </optgroup>
  @endforeach
</select>
@if(\App\Http\Middleware\CheckPermission::allows(auth()->user(), 'manage_stock'))
  <a href="{{ route('unit_types.index') }}" class="mt-1 inline-block text-xs text-indigo-600 hover:underline" target="_blank">Manage units</a>
@endif
