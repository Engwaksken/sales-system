<?php

namespace App\Models;

use App\Http\Middleware\CheckPermission;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Throwable;

/**
 * What a user wants to be told about. Every type is on until the user turns it
 * off, and a user only ever sees the types their role can receive.
 */
class NotificationPreference extends Model
{
    protected $fillable = ['user_id', 'preferences', 'low_stock_threshold'];

    protected $casts = [
        'preferences' => 'array',
        'low_stock_threshold' => 'integer',
    ];

    public const DEFAULT_LOW_STOCK = 5;

    /**
     * key => label, description, channel (app|email), and who gets it:
     * 'audience' customer|staff|all, or a 'permission' key (admins always qualify).
     */
    public const OPTIONS = [
        'announcements' => [
            'label' => 'Announcements',
            'description' => 'Notices and news posted by the business.',
            'channel' => 'app', 'audience' => 'all',
        ],
        'order_updates' => [
            'label' => 'My order updates',
            'description' => 'When one of your orders moves to a new stage.',
            'channel' => 'app', 'audience' => 'customer',
        ],
        'chat_replies' => [
            'label' => 'Support replies',
            'description' => 'When the support team replies to your chat.',
            'channel' => 'app', 'audience' => 'customer',
        ],
        'email_receipts' => [
            'label' => 'Invoices and receipts by email',
            'description' => 'Email me a PDF invoice and receipt when I buy or pay.',
            'channel' => 'email', 'audience' => 'customer',
        ],
        'new_orders' => [
            'label' => 'Online orders',
            'description' => 'New online orders and changes to them.',
            'channel' => 'app', 'permission' => 'manage_orders',
        ],
        'customer_chats' => [
            'label' => 'Customer chats',
            'description' => 'New messages from customers waiting for a reply.',
            'channel' => 'app', 'permission' => 'manage_chats',
        ],
        'low_stock' => [
            'label' => 'Low stock alerts',
            'description' => 'When a product at your branch drops to your low-stock limit.',
            'channel' => 'app', 'permission' => 'manage_stock',
        ],
        'flagged_activity' => [
            'label' => 'Flagged activity',
            'description' => 'When someone deletes a sale, edits a stock count by hand or changes a payment.',
            'channel' => 'app', 'permission' => 'view_activity_logs',
        ],
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /** The user's saved row, or unsaved defaults (remembered on the user object). */
    public static function for(User $user): self
    {
        if ($user->relationLoaded('notificationPreference') && $user->notificationPreference) {
            return $user->notificationPreference;
        }

        $row = null;
        try {
            if (Schema::hasTable('notification_preferences')) {
                $row = self::query()->where('user_id', $user->getKey())->first();
            }
        } catch (Throwable) {
            // Before the migration runs everybody keeps the defaults.
        }

        $row ??= new self([
            'user_id' => $user->getKey(),
            'preferences' => [],
            'low_stock_threshold' => self::DEFAULT_LOW_STOCK,
        ]);
        $user->setRelation('notificationPreference', $row);

        return $row;
    }

    /** Receipt/invoice emails go out unless the customer's own account turned them off. */
    public static function customerWantsEmail(?Customer $customer): bool
    {
        $user = $customer?->user;

        return ! $user || self::wants($user, 'email_receipts');
    }

    /** Whether $user receives notifications of type $key (types they cannot get are false). */
    public static function wants(?User $user, string $key): bool
    {
        if (! $user || ! isset(self::OPTIONS[$key]) || ! self::eligible($user, $key)) {
            return false;
        }

        return (bool) (self::for($user)->preferences[$key] ?? true);
    }

    public static function eligible(User $user, string $key): bool
    {
        $option = self::OPTIONS[$key] ?? null;
        if (! $option) {
            return false;
        }

        $isCustomer = ($user->role ?: 'customer') === 'customer';

        if (isset($option['permission'])) {
            return ! $isCustomer && CheckPermission::allows($user, $option['permission']);
        }

        return match ($option['audience'] ?? 'all') {
            'customer' => $isCustomer,
            'staff' => ! $isCustomer,
            default => true,
        };
    }

    /** The options this user can change, with their current values. */
    public static function settingsFor(User $user): array
    {
        $saved = self::for($user);

        return [
            'options' => collect(self::OPTIONS)
                ->filter(fn ($option, $key) => self::eligible($user, $key))
                ->map(fn ($option, $key) => [
                    'key' => $key,
                    'label' => $option['label'],
                    'description' => $option['description'],
                    'channel' => $option['channel'],
                    'enabled' => (bool) ($saved->preferences[$key] ?? true),
                ])
                ->values()
                ->all(),
            'low_stock_threshold' => self::eligible($user, 'low_stock')
                ? ($saved->low_stock_threshold ?: self::DEFAULT_LOW_STOCK)
                : null,
        ];
    }

    /** Saves the toggles the user is allowed to change; other keys are ignored. */
    public static function saveFor(User $user, array $toggles, ?int $threshold = null): self
    {
        $row = self::query()->firstOrNew(['user_id' => $user->getKey()]);
        $prefs = $row->preferences ?? [];

        foreach ($toggles as $key => $value) {
            if (self::eligible($user, (string) $key)) {
                $prefs[$key] = filter_var($value, FILTER_VALIDATE_BOOL);
            }
        }

        $row->preferences = $prefs;
        if ($threshold !== null && self::eligible($user, 'low_stock')) {
            $row->low_stock_threshold = max(0, min($threshold, 100000));
        }
        $row->low_stock_threshold ??= self::DEFAULT_LOW_STOCK;
        $row->save();

        $user->setRelation('notificationPreference', $row);

        return $row;
    }
}
