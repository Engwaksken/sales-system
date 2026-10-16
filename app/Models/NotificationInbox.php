<?php

namespace App\Models;

use Carbon\CarbonInterface;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Exceptions\HttpResponseException;

/** Shared inbox queries and explicitly authorized read-receipt orchestration. */
final class NotificationInbox
{
    public static function canManageOrders(User $user): bool
    {
        return self::canManage($user, 'manage_orders');
    }

    public static function canManageChats(User $user): bool
    {
        return self::canManage($user, 'manage_chats');
    }

    private static function canManage(User $user, string $permission): bool
    {
        if ($user->role === 'admin') {
            return true;
        }

        return in_array($user->role, ['manager', 'cashier'], true)
            && RolePermission::query()->where('role', $user->role)
                ->where('permission_key', $permission)->exists();
    }

    public static function canOwnOrder(User $user, Order $order): bool
    {
        if ($user->role !== 'customer') {
            return false;
        }

        $customerId = $user->customer?->getKey();
        if ($customerId !== null) {
            return (string) $order->customer_id === (string) $customerId;
        }

        if (! self::hasEmail($user)) {
            return false;
        }

        if ($order->exists) {
            return self::whereNormalizedEmail(Order::query(), 'orders.email', $user)
                ->whereKey($order->getKey())->exists();
        }

        return self::normalizedEmail($order->email) === self::normalizedEmail($user->email);
    }

    public static function canOwnCustomerChat(User $user, ChatConversation $conversation): bool
    {
        if ($user->role !== 'customer' || ! self::hasEmail($user)) {
            return false;
        }

        if ($conversation->exists) {
            return self::whereNormalizedEmail(ChatConversation::query(), 'chat_conversations.email', $user)
                ->whereKey($conversation->getKey())->exists();
        }

        return self::normalizedEmail($conversation->email) === self::normalizedEmail($user->email);
    }

    public static function visibleSystem(User $user): Builder
    {
        return AppNotification::query()
            ->select('notifications_app.*')
            ->selectRaw('CASE WHEN notifications_app.user_id IS NULL THEN EXISTS (
                SELECT 1 FROM system_notification_reads
                WHERE system_notification_reads.notification_id = notifications_app.id
                AND system_notification_reads.user_id = ?
            ) ELSE notifications_app.is_read END AS notification_is_read', [$user->getKey()])
            ->where(function (Builder $query) use ($user) {
                $query->whereNull('notifications_app.user_id')
                    ->orWhere('notifications_app.user_id', $user->getKey());
            });
    }

    public static function visibleOrders(User $user): Builder
    {
        return self::ownedOrders($user);
    }

    private static function ownedOrders(User $user): Builder
    {
        $query = Order::query();

        if ($user->role === 'customer') {
            $customerId = $user->customer?->getKey();
            if ($customerId !== null) {
                $query->where('orders.customer_id', $customerId);
            } elseif (self::hasEmail($user)) {
                self::whereNormalizedEmail($query, 'orders.email', $user);
            } else {
                $query->whereRaw('1 = 0');
            }
        } elseif (! self::canManageOrders($user)) {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function visibleChats(User $user): Builder
    {
        $query = ChatMessage::query()->with('conversation');

        if ($user->role === 'customer') {
            if (! self::hasEmail($user)) {
                return $query->whereRaw('1 = 0');
            }

            $query->whereHas('conversation', function (Builder $conversation) use ($user) {
                self::whereNormalizedEmail($conversation, 'chat_conversations.email', $user);
            });
        } elseif (self::canManageChats($user)) {
            $query->where('chat_messages.sender_type', 'customer');
        } else {
            $query->whereRaw('1 = 0');
        }

        return $query;
    }

    public static function unreadSystem(User $user): Builder
    {
        return self::visibleSystem($user)->where(function (Builder $query) use ($user) {
            $query->where(function (Builder $broadcast) use ($user) {
                $broadcast->whereNull('notifications_app.user_id')
                    ->whereDoesntHave('notificationReads', function (Builder $reads) use ($user) {
                        $reads->where('user_id', $user->getKey());
                    });
            })->orWhere(function (Builder $private) use ($user) {
                $private->where('notifications_app.user_id', $user->getKey())
                    ->where('notifications_app.is_read', false);
            });
        });
    }

    public static function unreadOrders(User $user): Builder
    {
        return self::visibleOrders($user)
            ->whereDoesntHave('notificationReads', function (Builder $reads) use ($user) {
                $reads->where('user_id', $user->getKey())
                    ->whereColumn('order_notification_reads.notification_version', 'orders.notification_version');
            });
    }

    public static function unreadChats(User $user): Builder
    {
        $query = self::visibleChats($user)->where('chat_messages.is_read', false);

        if ($user->role === 'customer') {
            $query->where('chat_messages.sender_type', 'staff');
        }

        return $query;
    }

    /** @return array{system: int, orders: int, chats: int, total: int} */
    public static function counts(User $user): array
    {
        $system = self::unreadSystem($user)->count();
        $orders = self::unreadOrders($user)->count();
        $chats = self::unreadChats($user)->count();

        return compact('system', 'orders', 'chats') + ['total' => $system + $orders + $chats];
    }

    /**
     * Collections only; use counts() for unlimited badge totals.
     * System entries expose notification_is_read without changing global is_read.
     */
    public static function feed(User $user): array
    {
        return [
            'system' => self::visibleSystem($user)->latest('notifications_app.created_at')
                ->orderByDesc('notifications_app.id')->limit(20)->get(),
            'orders' => self::unreadOrders($user)->latest('orders.updated_at')
                ->orderByDesc('orders.id')->limit(5)->get(),
            'chats' => self::unreadChats($user)->latest('chat_messages.created_at')
                ->orderByDesc('chat_messages.id')->limit(5)->get(),
        ];
    }

    /** Call only after policy authorization; ownership is rechecked under lock. */
    public static function markSystemRead(User $user, AppNotification $notification): array
    {
        $data = $notification->getConnection()->transaction(function () use ($user, $notification) {
            $locked = AppNotification::query()->whereKey($notification->getKey())
                ->lockForUpdate()->firstOrFail();
            self::assertSystemOwner($user, $locked);

            if ($locked->user_id === null) {
                self::insertSystemReceipts($user, [$locked->getKey()], now());
                $readAt = SystemNotificationRead::query()
                    ->where('user_id', $user->getKey())->where('notification_id', $locked->getKey())
                    ->firstOrFail()->read_at;
            } else {
                if (! $locked->is_read) {
                    $locked->is_read = true;
                    $locked->read_at = $locked->read_at ?? now();
                    $locked->save();
                }
                $readAt = $locked->read_at;
            }

            return ['success' => true, 'type' => 'system', 'id' => $locked->getKey(),
                'read_at' => $readAt?->toISOString()];
        });

        return $data + ['counts' => self::counts($user)];
    }

    /** Conflicts are reported after releasing the row lock, with current counts. */
    public static function markOrderRead(User $user, Order $order, int $version): array
    {
        $data = $order->getConnection()->transaction(function () use ($user, $order, $version) {
            $locked = Order::query()->whereKey($order->getKey())->lockForUpdate()->firstOrFail();
            abort_unless(self::canManageOrders($user) || self::canOwnOrder($user, $locked), 403);
            if ($version !== $locked->notification_version) {
                return ['success' => false, 'code' => 'stale_notification',
                    'notification_version' => $locked->notification_version];
            }

            self::insertOrderReceipts($user, [$locked], now());
            $receipt = OrderNotificationRead::query()->where('user_id', $user->getKey())
                ->where('order_id', $locked->getKey())->where('notification_version', $version)
                ->firstOrFail();

            return ['success' => true, 'type' => 'order', 'id' => $locked->getKey(),
                'notification_version' => $version, 'read_at' => $receipt->read_at->toISOString()];
        });

        $data['counts'] = self::counts($user);
        if (! $data['success']) {
            throw new HttpResponseException(response()->json($data, 409));
        }

        return $data;
    }

    public static function markCustomerChatRead(User $user, ChatConversation $conversation): array
    {
        $data = $conversation->getConnection()->transaction(function () use ($user, $conversation) {
            $locked = ChatConversation::query()->whereKey($conversation->getKey())
                ->lockForUpdate()->firstOrFail();
            abort_unless(self::canOwnCustomerChat($user, $locked), 403);

            $updated = ChatMessage::query()->where('conversation_id', $locked->getKey())
                ->where('sender_type', 'staff')->where('is_read', false)->update(['is_read' => true]);

            return ['success' => true, 'type' => 'chat', 'conversation_id' => $locked->getKey(),
                'updated' => $updated];
        });

        return $data + ['counts' => self::counts($user)];
    }

    /**
     * The controller MUST authorize the requested scope before calling this method.
     * Scope all acknowledges only the types the caller is authorized to view.
     * Counts run once after commit, never once per record/chunk.
     */
    public static function markAllRead(User $user, string $scope = 'system'): array
    {
        if (! in_array($scope, ['system', 'all'], true)) {
            throw new HttpResponseException(response()->json([
                'success' => false, 'code' => 'invalid_scope',
            ], 422));
        }

        if ($scope === 'all') {
            abort_unless(in_array($user->role, ['admin', 'manager', 'cashier', 'customer'], true), 403);
        }

        $updated = (new AppNotification)->getConnection()->transaction(function () use ($user, $scope) {
            $updated = ['system' => 0, 'orders' => 0, 'chats' => 0];
            $now = now();

            // Lock visible rows, not only a snapshot of unread rows. Unique inserts
            // determine whether a broadcast was actually newly read under concurrency.
            self::visibleSystem($user)->lockForUpdate()->chunkById(500,
                function ($notifications) use ($user, $now, &$updated) {
                    $broadcasts = [];
                    $privateWithDate = [];
                    $privateWithoutDate = [];
                    foreach ($notifications as $notification) {
                        self::assertSystemOwner($user, $notification);
                        if ($notification->user_id === null) {
                            $broadcasts[] = $notification->getKey();
                        } elseif (! $notification->is_read) {
                            if ($notification->read_at === null) {
                                $privateWithoutDate[] = $notification->getKey();
                            } else {
                                $privateWithDate[] = $notification->getKey();
                            }
                        }
                    }
                    $updated['system'] += self::insertSystemReceipts($user, $broadcasts, $now);
                    if ($privateWithoutDate) {
                        $updated['system'] += AppNotification::query()->whereKey($privateWithoutDate)
                            ->where('is_read', false)->update(['is_read' => true, 'read_at' => $now]);
                    }
                    if ($privateWithDate) {
                        $updated['system'] += AppNotification::query()->whereKey($privateWithDate)
                            ->where('is_read', false)->update(['is_read' => true]);
                    }
                }, 'notifications_app.id', 'id');

            if ($scope === 'all') {
                self::visibleOrders($user)->lockForUpdate()->chunkById(500,
                    function ($orders) use ($user, $now, &$updated) {
                        $updated['orders'] += self::insertOrderReceipts($user, $orders->all(), $now);
                    }, 'orders.id', 'id');

                if ($user->role === 'customer') {
                    if (self::hasEmail($user)) {
                        // Ownership cannot change between checking the conversation
                        // and marking its replies: keep conversation locks until commit.
                        self::whereNormalizedEmail(ChatConversation::query(), 'chat_conversations.email', $user)
                            ->lockForUpdate()->chunkById(500,
                                function ($conversations) use ($user, &$updated) {
                                    $ids = $conversations->modelKeys();
                                    $updated['chats'] += self::unreadChats($user)
                                        ->whereIn('conversation_id', $ids)->update(['is_read' => true]);
                                });
                    }
                } elseif (self::canManageChats($user)) {
                    $updated['chats'] = self::unreadChats($user)->update(['is_read' => true]);
                }
            }

            return $updated;
        });

        return ['scope' => $scope, 'updated' => $updated, 'counts' => self::counts($user)];
    }

    private static function assertSystemOwner(User $user, AppNotification $notification): void
    {
        abort_unless($notification->user_id === null
            || (string) $notification->user_id === (string) $user->getKey(), 403);
    }

    /** Unique-key insert preserves the original receipt and first read_at. */
    private static function insertSystemReceipts(User $user, array $ids, CarbonInterface $now): int
    {
        if (! $ids) {
            return 0;
        }

        return SystemNotificationRead::query()->insertOrIgnore(array_map(fn ($id) => [
            'user_id' => $user->getKey(), 'notification_id' => $id,
            'read_at' => $now, 'created_at' => $now, 'updated_at' => $now,
        ], $ids));
    }

    private static function insertOrderReceipts(User $user, array $orders, CarbonInterface $now): int
    {
        if (! $orders) {
            return 0;
        }

        return OrderNotificationRead::query()->insertOrIgnore(array_map(fn (Order $order) => [
            'user_id' => $user->getKey(), 'order_id' => $order->getKey(),
            'notification_version' => $order->notification_version,
            'read_at' => $now, 'created_at' => $now, 'updated_at' => $now,
        ], $orders));
    }

    private static function hasEmail(User $user): bool
    {
        return self::normalizedEmail($user->email) !== '';
    }

    private static function normalizedEmail(?string $email): string
    {
        return strtolower(trim($email ?? ''));
    }

    private static function whereNormalizedEmail(Builder $query, string $column, User $user): Builder
    {
        return $query->whereRaw("LOWER(TRIM({$column})) = ?", [self::normalizedEmail($user->email)]);
    }
}
