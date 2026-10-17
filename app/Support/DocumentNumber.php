<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Sequential document numbers that restart every day: INV-20260815-001, -002, ...
 *
 * The counter row is locked for the rest of the caller's transaction, so two
 * sales saved at the same moment can never get the same number, and a sale
 * that rolls back releases its number instead of leaving a gap.
 */
class DocumentNumber
{
    public const PAD = 3;

    public static function invoice(): string
    {
        return self::next(self::prefix('invoice_prefix', 'INV-'), [['sales', 'invoice_no']]);
    }

    /** Receipts share one daily sequence across sale payments and online order payments. */
    public static function receipt(): string
    {
        return self::next('RCT-', [['payments', 'receipt_no'], ['order_payment_receipts', 'receipt_number']]);
    }

    /**
     * @param  array<int, array{0: string, 1: string}>  $sources  table/column pairs already holding numbers
     */
    public static function next(string $prefix, array $sources, ?Carbon $date = null): string
    {
        $base = $prefix.($date ?? now())->format('Ymd').'-';

        return DB::transaction(function () use ($base, $sources): string {
            $counter = DB::table('document_sequences')->where('sequence_key', $base)->lockForUpdate()->first();

            if (! $counter) {
                // First number of the day: continue after any sequential numbers already issued.
                DB::table('document_sequences')->insertOrIgnore([
                    'sequence_key' => $base,
                    'last_value' => self::highestIssued($base, $sources),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $counter = DB::table('document_sequences')->where('sequence_key', $base)->lockForUpdate()->first();
            }

            $value = (int) $counter->last_value + 1;
            DB::table('document_sequences')->where('id', $counter->id)->update(['last_value' => $value, 'updated_at' => now()]);

            return $base.str_pad((string) $value, self::PAD, '0', STR_PAD_LEFT);
        });
    }

    private static function highestIssued(string $base, array $sources): int
    {
        $highest = 0;
        foreach ($sources as [$table, $column]) {
            $numbers = DB::table($table)->where($column, 'like', $base.'%')->pluck($column);
            foreach ($numbers as $number) {
                $suffix = substr((string) $number, strlen($base));
                // Older random suffixes (e.g. 4AB35F or 482913) are six characters; ignore them.
                if (preg_match('/^\d{3,5}$/', $suffix)) {
                    $highest = max($highest, (int) $suffix);
                }
            }
        }

        return $highest;
    }

    private static function prefix(string $key, string $default): string
    {
        try {
            $value = trim((string) Setting::query()->where('setting_key', $key)->value('setting_value'));
        } catch (\Throwable) {
            $value = '';
        }

        $value = $value !== '' ? $value : $default;

        // "INV" and "INV-" both give INV-20260815-001.
        return preg_match('/[A-Za-z0-9]$/', $value) ? $value.'-' : $value;
    }
}
