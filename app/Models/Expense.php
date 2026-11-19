<?php

namespace App\Models;

use App\Models\Concerns\HasUserstamps;
use Illuminate\Database\Eloquent\Model;

class Expense extends Model
{
    use HasUserstamps;

    protected $table = 'expenses';

    protected $fillable = [
        'title',
        'amount',
        'payment_method',
        'expense_date',
        'category',
        'description',
        'receipt_image',
        'branch_id',
        'supplier_id',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'expense_date' => 'date',
    ];

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    /** The form option that reveals a box for typing a new category. */
    public const NEW_CATEGORY = '__new__';

    /**
     * The category to save. A typed-in category is added to the list (matched
     * case-insensitively) so it can simply be picked next time.
     */
    public static function resolveCategory(?string $category, ?string $typed = null): string
    {
        $name = trim((string) ($category === self::NEW_CATEGORY ? $typed : ($category ?: $typed)));
        if ($name === '') {
            return 'Miscellaneous';
        }

        $existing = ExpenseCategory::query()->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])->first();
        if ($existing) {
            return $existing->name;
        }

        return ExpenseCategory::query()->create(['name' => mb_substr($name, 0, 150), 'status' => true])->name;
    }

    /** A readable title when none is typed: "Fuel - Shell Bugolobi" or "Fuel - 15 Nov 2026". */
    public static function defaultTitle(array $data): string
    {
        $category = trim((string) ($data['category'] ?? '')) ?: 'Expense';
        $who = trim((string) ($data['payee'] ?? $data['vendor'] ?? ''));
        if ($who !== '') {
            return mb_substr($category.' - '.$who, 0, 255);
        }

        $date = $data['expense_date'] ?? null;
        try {
            $date = $date ? \Illuminate\Support\Carbon::parse($date)->format('d M Y') : now()->format('d M Y');
        } catch (\Throwable) {
            $date = now()->format('d M Y');
        }

        return $category.' - '.$date;
    }

    /**
     * Category names for the expense form: active categories plus any already
     * used on expenses, so editing an old expense keeps its category selected.
     *
     * @return array<int, string>
     */
    public static function categoryOptions(): array
    {
        $listed = ExpenseCategory::query()->where('status', true)->orderBy('name')->pluck('name');
        $used = self::query()->whereNotNull('category')->where('category', '!=', '')->distinct()->pluck('category');

        return $listed->concat($used)
            ->map(fn ($n) => trim((string) $n))
            ->filter()
            ->unique(fn ($n) => mb_strtolower($n))
            ->sort(SORT_NATURAL | SORT_FLAG_CASE)
            ->values()
            ->all();
    }
}
