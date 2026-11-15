<?php

namespace App\Http\Controllers;

use App\Models\UnitType;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/** The list of units stock forms choose from (manage_stock). */
class UnitTypesController extends Controller
{
    public function index(Request $request)
    {
        $units = UnitType::query()
            ->when(trim((string) $request->query('q', '')), fn ($q, $s) => $q->where(fn ($w) => $w
                ->where('name', 'like', "%{$s}%")->orWhere('abbreviation', 'like', "%{$s}%")))
            ->get()
            ->sortBy(fn (UnitType $u) => [UnitType::categoryOrder($u->category), strtolower($u->name)])
            ->values();

        return view('unit_types.index', [
            'units' => $units,
            'categories' => UnitType::CATEGORIES,
            'inUse' => \App\Models\Stock::query()->whereNotNull('unit_type')
                ->selectRaw('LOWER(unit_type) as unit, count(*) as total')->groupBy('unit')->pluck('total', 'unit'),
        ]);
    }

    public function store(Request $request)
    {
        UnitType::create($this->validated($request) + ['is_active' => true]);

        return redirect()->route('unit_types.index')->with('status', 'Unit added. It is now in every stock form.');
    }

    public function update(Request $request, UnitType $unit_type)
    {
        $unit_type->update($this->validated($request, $unit_type) + ['is_active' => $request->boolean('is_active')]);

        return redirect()->route('unit_types.index')->with('status', 'Unit updated.');
    }

    public function destroy(UnitType $unit_type)
    {
        if (\App\Models\Stock::query()->whereRaw('LOWER(unit_type) = ?', [strtolower($unit_type->abbreviation)])->exists()) {
            return back()->withErrors(['unit' => "\"{$unit_type->name}\" is used by stock records. Switch it off instead of deleting it."]);
        }
        $unit_type->delete();

        return redirect()->route('unit_types.index')->with('status', 'Unit deleted.');
    }

    private function validated(Request $request, ?UnitType $unit = null): array
    {
        return $request->validate([
            'name' => ['required', 'string', 'max:60'],
            'abbreviation' => ['required', 'string', 'max:20', Rule::unique('unit_types', 'abbreviation')->ignore($unit?->getKey())],
            'category' => ['nullable', Rule::in(UnitType::CATEGORIES)],
        ], ['abbreviation.unique' => 'That short name is already used by another unit.']);
    }
}
