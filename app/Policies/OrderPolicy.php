<?php

namespace App\Policies;

use App\Models\Order;
use App\Models\NotificationInbox;
use App\Models\User;

class OrderPolicy
{
    /**
     * Gate::authorize('customerView', $order);
     * For the customer notification JSON endpoint, never the staff order routes.
     * A linked customer takes precedence; email fallback applies only without one.
     */
    public function customerView(User $user, Order $order): bool
    {
        return NotificationInbox::canOwnOrder($user, $order);
    }

    /**
     * Gate::authorize('notificationRead', $order);
     * Status/version conflicts belong to NotificationInbox, not authorization.
     */
    public function notificationRead(User $user, Order $order): bool
    {
        return NotificationInbox::canManageOrders($user)
            || NotificationInbox::canOwnOrder($user, $order);
    }
}
