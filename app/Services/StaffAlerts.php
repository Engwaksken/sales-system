<?php

namespace App\Services;

use App\Http\Middleware\CheckPermission;
use App\Models\ActivityLog;
use App\Models\AppNotification;
use App\Models\NotificationPreference;
use App\Models\Stock;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * In-app alerts for staff, sent only to people whose role can act on them and
 * who keep that alert switched on in their notification settings.
 */
class StaffAlerts
{
    /** Tell owners about a flagged change (not the person who made it). */
    public static function flaggedActivity(ActivityLog $log): void
    {
        try {
            foreach (self::staffWith('view_activity_logs') as $user) {
                if ($user->getKey() === $log->user_id || ! NotificationPreference::wants($user, 'flagged_activity')) {
                    continue;
                }

                self::send($user, 'Flagged: '.$log->flag_reason, trim(($log->user_name ?? 'Someone').': '.$log->description).'. Open the Activity Log for details.', 'warning');
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** A stock count fell: alert each stock manager whose own limit it just crossed. */
    public static function stockDropped(Stock $stock, float $old, float $new): void
    {
        try {
            if ($new >= $old) {
                return;
            }
            $stock->loadMissing(['product:id,name', 'branch:id,name']);

            foreach (self::staffWith('manage_stock') as $user) {
                // Admins hear about every branch; others about their own.
                if ($user->role !== 'admin' && (int) $user->branch_id !== (int) $stock->branch_id) {
                    continue;
                }
                if (! NotificationPreference::wants($user, 'low_stock')) {
                    continue;
                }

                $limit = (float) (NotificationPreference::for($user)->low_stock_threshold ?? NotificationPreference::DEFAULT_LOW_STOCK);
                if (! ($old > $limit && $new <= $limit)) {
                    continue;
                }

                $what = ($stock->product?->name ?? 'A product').($stock->branch?->name ? ' at '.$stock->branch->name : '');
                self::send(
                    $user,
                    $new <= 0 ? 'Out of stock: '.($stock->product?->name ?? 'product') : 'Low stock: '.($stock->product?->name ?? 'product'),
                    $what.' is down to '.self::number($new).' (your alert level is '.self::number($limit).').',
                    $new <= 0 ? 'danger' : 'warning',
                );
            }
        } catch (Throwable $e) {
            report($e);
        }
    }

    /** @return Collection<int, User> active staff who pass the permission check */
    private static function staffWith(string $permission): Collection
    {
        if (! Schema::hasTable('notifications_app')) {
            return collect();
        }

        return User::query()
            ->whereNotNull('role')
            ->where('role', '!=', 'customer')
            ->get()
            ->filter(fn (User $user) => CheckPermission::allows($user, $permission));
    }

    private static function send(User $user, string $title, string $message, string $type): void
    {
        AppNotification::create([
            'user_id' => $user->getKey(),
            'title' => mb_substr($title, 0, 250),
            'message' => $message,
            'type' => $type,
            'is_read' => false,
        ]);
    }

    private static function number(float $n): string
    {
        return $n == (int) $n ? (string) (int) $n : rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }
}
