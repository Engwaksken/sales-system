<?php

namespace App\Models;

use App\Models\Concerns\HasUserstamps;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

/** A unit stock is counted in (pcs, kg, carton...). Stock rows store the abbreviation. */
class UnitType extends Model
{
    use HasUserstamps;

    protected $fillable = ['name', 'abbreviation', 'category', 'is_active', 'created_by', 'updated_by'];

    protected $casts = ['is_active' => 'boolean'];

    public const CATEGORIES = ['Count', 'Weight', 'Volume', 'Length', 'Area', 'Other'];

    /** Count first (the everyday units), then weight, volume, length, area, other. */
    public static function categoryOrder(?string $category): int
    {
        $index = array_search($category, self::CATEGORIES, true);

        return $index === false ? 99 : $index;
    }

    /**
     * Active units for a stock form; $current keeps a row's existing unit selectable
     * even if it was typed before the list existed or has since been switched off.
     *
     * @return Collection<int, array{value: string, label: string, category: ?string}>
     */
    public static function options(?string $current = null): Collection
    {
        try {
            $units = Schema::hasTable('unit_types')
                ? self::query()->where('is_active', true)->get()
                    ->sortBy(fn (self $u) => [self::categoryOrder($u->category), strtolower($u->name)])->values()
                : collect();
        } catch (Throwable) {
            $units = collect();
        }

        $options = $units->map(fn (self $u) => [
            'value' => $u->abbreviation,
            'label' => $u->name.' ('.$u->abbreviation.')',
            'category' => $u->category,
        ]);

        $current = trim((string) $current);
        if ($current !== '' && ! $options->contains(fn ($o) => strcasecmp($o['value'], $current) === 0)) {
            $options->prepend(['value' => $current, 'label' => $current, 'category' => 'Other']);
        }

        return $options->values();
    }
}
