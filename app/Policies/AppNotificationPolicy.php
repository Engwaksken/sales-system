<?php

namespace App\Policies;

use App\Models\AppNotification;
use App\Models\User;

class AppNotificationPolicy
{
    /**
     * Gate::authorize('index', [AppNotification::class, $scope]);
     * Laravel removes the class argument; scope validation belongs to the request.
     * Feed queries must still enforce each authenticated user's visible inbox.
     */
    public function index(User $user, string $scope = 'system'): bool
    {
        return true;
    }

    /** Gate::authorize('read', $notification); */
    public function read(User $user, AppNotification $notification): bool
    {
        return $notification->user_id === null
            || ($user->getKey() !== null
                && (string) $notification->user_id === (string) $user->getKey());
    }

    /**
     * Gate::authorize('readAll', [AppNotification::class, $scope]);
     * Never pass an AppNotification instance for this class-level ability.
     * NotificationInbox::markAllRead must retain its per-user ownership filters.
     */
    public function readAll(User $user, string $scope = 'system'): bool
    {
        if ($scope === 'system') {
            return true;
        }

        if ($scope !== 'all') {
            return false;
        }

        // Each inbox type is independently gated by NotificationInbox.
        return in_array($user->role, ['admin', 'manager', 'cashier', 'customer'], true);
    }
}
