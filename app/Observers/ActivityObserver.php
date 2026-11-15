<?php

namespace App\Observers;

use App\Models\Customer;
use App\Models\Expense;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Product;
use App\Models\RolePermission;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Setting;
use App\Models\Stock;
use App\Models\StockIssue;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\StaffAlerts;
use Illuminate\Database\Eloquent\Model;

/**
 * Logs create / update / delete on the records that matter for money and stock,
 * and flags the edits that can hide a sale: deleting or changing a sale after the
 * fact, changing its items or payments, and editing stock counts by hand.
 */
class ActivityObserver
{
    /** Models that are audited (registered in AppServiceProvider). */
    public const MODELS = [
        Sale::class, SaleItem::class, Payment::class, Stock::class, StockIssue::class,
        Product::class, Order::class, Expense::class, Customer::class, Supplier::class,
        User::class, RolePermission::class, Setting::class,
    ];

    /** Bookkeeping columns that say nothing about what a person did. */
    private const IGNORED = [
        'created_at', 'updated_at', 'created_by', 'updated_by', 'notification_version',
        'password', 'remember_token', 'verification_code', 'code_expires',
    ];

    /** Sale columns that change what was sold or what is owed. */
    private const SALE_MONEY_FIELDS = ['total_amount', 'discount', 'vat', 'customer_id', 'sale_date', 'branch_id', 'invoice_no', 'transaction_type'];

    public function created(Model $model): void
    {
        // Routine POS items are summarised by their sale; log only hand-made ones.
        if ($model instanceof SaleItem && ActivityLogger::inRoutineFlow()) {
            return;
        }

        $flag = match (true) {
            $model instanceof SaleItem => 'Item added to a sale after it was recorded.',
            $model instanceof Sale && ! ActivityLogger::inRoutineFlow() && ($model->transaction_type ?? 'sale') === 'sale'
                => 'Sale entered by hand: no stock was taken off for it.',
            $model instanceof RolePermission => 'Permission granted to a role.',
            default => null,
        };

        ActivityLogger::record('created', $this->describe($model, 'created'), $model, $this->snapshot($model), $flag);
    }

    public function updated(Model $model): void
    {
        $changes = $this->changes($model);
        if ($changes === []) {
            return;
        }

        ActivityLogger::record('updated', $this->describe($model, 'updated'), $model, $changes, $this->updateFlag($model, $changes));

        if ($model instanceof Stock && isset($changes['quantity'])) {
            StaffAlerts::stockDropped($model, (float) $changes['quantity'][0], (float) $changes['quantity'][1]);
        }
    }

    public function deleted(Model $model): void
    {
        $flag = match (true) {
            $model instanceof Sale => 'Sale deleted: its stock was not returned and its takings drop out of reports.',
            $model instanceof SaleItem => 'Item removed from a recorded sale.',
            $model instanceof Payment => 'Payment receipt deleted.',
            $model instanceof Stock => 'Stock record deleted.',
            $model instanceof Order => 'Online order deleted.',
            $model instanceof RolePermission => 'Permission removed from a role.',
            $model instanceof User => 'User account deleted.',
            default => null,
        };

        ActivityLogger::record('deleted', $this->describe($model, 'deleted'), $model, $this->snapshot($model), $flag);
    }

    /** @param  array<string, array{0: mixed, 1: mixed}>  $changes */
    private function updateFlag(Model $model, array $changes): ?string
    {
        $routine = ActivityLogger::inRoutineFlow();

        if ($model instanceof Stock && isset($changes['quantity']) && ! $routine) {
            [$old, $new] = $changes['quantity'];

            return (float) $new > (float) $old
                ? 'Stock count raised by hand from '.$this->number($old).' to '.$this->number($new).' (not a delivery).'
                : 'Stock count lowered by hand from '.$this->number($old).' to '.$this->number($new).' (not a sale or write-off).';
        }

        if ($model instanceof Sale && array_intersect(array_keys($changes), self::SALE_MONEY_FIELDS)) {
            return 'Sale changed after it was recorded ('.implode(', ', array_intersect(array_keys($changes), self::SALE_MONEY_FIELDS)).').';
        }

        if ($model instanceof SaleItem) {
            return 'Sold item changed after the sale ('.implode(', ', array_keys($changes)).').';
        }

        if ($model instanceof Payment && array_intersect(array_keys($changes), ['amount', 'method', 'status', 'sale_id']) && ! $routine) {
            return 'Payment receipt changed ('.implode(', ', array_keys($changes)).').';
        }

        if ($model instanceof User && isset($changes['role'])) {
            return 'User role changed from '.($changes['role'][0] ?? 'none').' to '.($changes['role'][1] ?? 'none').'.';
        }

        return null;
    }

    /** @return array<string, array{0: mixed, 1: mixed}> */
    private function changes(Model $model): array
    {
        $changes = [];
        foreach ($model->getChanges() as $field => $new) {
            if (in_array($field, self::IGNORED, true)) {
                continue;
            }
            $old = $model->getOriginal($field);
            if ($this->same($old, $new)) {
                continue;
            }
            $changes[$field] = [$this->plain($old), $this->plain($new)];
        }

        return $changes;
    }

    /** Field => [null, value] for creates, [value, null] for deletes. */
    private function snapshot(Model $model): array
    {
        $deleting = ! $model->exists;
        $out = [];
        foreach ($model->getAttributes() as $field => $value) {
            if (in_array($field, self::IGNORED, true) || $field === 'id' || $value === null || $value === '') {
                continue;
            }
            $out[$field] = $deleting ? [$this->plain($value), null] : [null, $this->plain($value)];
        }

        return $out;
    }

    private function describe(Model $model, string $verb): string
    {
        $what = match (true) {
            $model instanceof Sale => 'sale '.($model->invoice_no ?: '#'.$model->id).' ('.$this->money($model->total_amount).')',
            $model instanceof SaleItem => $this->number($model->quantity).' × '.($model->product?->name ?? 'product #'.$model->product_id)
                .' on sale '.($model->sale?->invoice_no ?? '#'.$model->sale_id),
            $model instanceof Payment => 'payment '.($model->receipt_no ?: '#'.$model->id).' of '.$this->money($model->amount)
                .($model->sale?->invoice_no ? ' for '.$model->sale->invoice_no : ''),
            $model instanceof Stock => 'stock of '.($model->product?->name ?? 'product #'.$model->product_id)
                .($model->branch?->name ? ' at '.$model->branch->name : ''),
            $model instanceof StockIssue => 'stock issue ('.str_replace('_', ' ', (string) $model->type).') of '
                .$this->number($model->quantity).' × '.($model->product?->name ?? 'product #'.$model->product_id),
            $model instanceof Product => 'product '.$model->name,
            $model instanceof Order => 'order '.($model->order_number ?: $model->order_id ?: '#'.$model->id),
            $model instanceof Expense => 'expense '.($model->title ?: '#'.$model->id).' ('.$this->money($model->amount).')',
            $model instanceof Customer => 'customer '.($model->name ?: '#'.$model->id),
            $model instanceof Supplier => 'supplier '.$model->name,
            $model instanceof User => 'user '.$model->name.' ('.$model->role.')',
            $model instanceof RolePermission => 'permission '.$model->permission_key.' for role '.$model->role,
            $model instanceof Setting => 'setting '.$model->setting_key,
            default => class_basename($model).' #'.$model->getKey(),
        };

        return ucfirst($verb).' '.$what;
    }

    private function same(mixed $a, mixed $b): bool
    {
        if (is_numeric($a) && is_numeric($b)) {
            return abs((float) $a - (float) $b) < 0.000001;
        }

        return (string) $this->plain($a) === (string) $this->plain($b);
    }

    private function plain(mixed $value): mixed
    {
        return match (true) {
            $value instanceof \DateTimeInterface => $value->format('Y-m-d H:i'),
            $value instanceof \BackedEnum => $value->value,
            is_array($value), is_object($value) => json_encode($value),
            default => $value,
        };
    }

    private function number(mixed $value): string
    {
        $n = (float) $value;

        return $n == (int) $n ? (string) (int) $n : rtrim(rtrim(number_format($n, 2, '.', ''), '0'), '.');
    }

    private function money(mixed $value): string
    {
        return 'UGX '.number_format((float) $value, 0);
    }
}
