<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

/**
 * Writes the audit trail. Logging must never block a sale or a stock change,
 * so every write is wrapped and failures are only reported.
 *
 * Business flows announce themselves with during('pos_sale', fn () => ...) so the
 * observer can tell a normal stock movement (a sale, a delivery, a write-off)
 * from someone editing the numbers by hand.
 */
class ActivityLogger
{
    /** Flows whose stock and sale changes are routine, not hand edits. */
    public const ROUTINE_CONTEXTS = ['pos_sale', 'sale_payment', 'stock_received', 'stock_issue', 'stock_import', 'bulk_upload', 'order_checkout'];

    /** @var array<int, string> */
    private static array $context = [];

    private static ?bool $ready = null;

    /**
     * @template T
     * @param  callable(): T  $callback
     * @return T
     */
    public static function during(string $context, callable $callback): mixed
    {
        self::$context[] = $context;

        try {
            return $callback();
        } finally {
            array_pop(self::$context);
        }
    }

    /** For long blocks that cannot be wrapped in during(); always pair with leave() in a finally. */
    public static function enter(string $context): void
    {
        self::$context[] = $context;
    }

    public static function leave(): void
    {
        array_pop(self::$context);
    }

    public static function context(): ?string
    {
        return self::$context === [] ? null : end(self::$context);
    }

    public static function inRoutineFlow(): bool
    {
        return in_array(self::context(), self::ROUTINE_CONTEXTS, true);
    }

    /**
     * @param  array<string, array{0: mixed, 1: mixed}>  $changes  field => [old, new]
     */
    public static function record(
        string $action,
        string $description,
        ?Model $subject = null,
        array $changes = [],
        ?string $flagReason = null,
        mixed $user = null,
    ): void {
        try {
            if (! self::ready()) {
                return;
            }

            $user ??= Auth::user();
            $request = app()->runningInConsole() && ! app()->runningUnitTests() ? null : request();

            ActivityLog::create([
                'user_id' => $user?->getKey(),
                'user_name' => $user?->name,
                'user_role' => $user?->role,
                'branch_id' => $subject?->getAttribute('branch_id') ?? $user?->branch_id,
                'action' => $action,
                'subject_type' => $subject ? Str::snake(class_basename($subject)) : null,
                'subject_id' => $subject?->getKey(),
                'description' => Str::limit($description, 495),
                'changes' => $changes ?: null,
                'context' => self::context(),
                'flagged' => $flagReason !== null,
                'flag_reason' => $flagReason ? Str::limit($flagReason, 250) : null,
                'source' => self::source($request),
                'ip_address' => $request?->ip(),
                'user_agent' => $request ? Str::limit((string) $request->userAgent(), 250) : null,
                'created_at' => now(),
            ]);
        } catch (Throwable $e) {
            report($e);
        }
    }

    private static function source($request): string
    {
        if (! $request) {
            return 'system';
        }

        return $request->is('api/*') ? 'mobile' : 'web';
    }

    /** Deployments may ship the code before running the migration; skip quietly until then. */
    private static function ready(): bool
    {
        if (self::$ready === null || self::$ready === false) {
            try {
                self::$ready = Schema::hasTable('activity_logs');
            } catch (Throwable) {
                self::$ready = false;
            }
        }

        return self::$ready;
    }

    /** Tests rebuild the schema between cases. */
    public static function reset(): void
    {
        self::$ready = null;
        self::$context = [];
    }
}
