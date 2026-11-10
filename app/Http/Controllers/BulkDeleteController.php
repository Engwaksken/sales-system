<?php

namespace App\Http\Controllers;

use App\Models;
use Illuminate\Database\QueryException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Deletes several records of one module at once.
 *
 * Each module gets its own POST /{resource}/bulk-delete route (registered in
 * routes/web.php from RESOURCES) guarded by the same permission as its
 * resource routes. Records are deleted one by one with ->delete(), the same
 * as the single destroy actions, so model events and observers still run.
 */
class BulkDeleteController extends Controller
{
    /** resource route name => [model class, permission key] */
    public const RESOURCES = [
        'accounting_journals' => [Models\AccountingJournal::class, 'manage_statements'],
        'branches' => [Models\Branch::class, 'manage_branches'],
        'categories' => [Models\Category::class, 'manage_categories'],
        'chart_of_accounts' => [Models\ChartOfAccount::class, 'manage_statements'],
        'customers' => [Models\Customer::class, 'manage_customers'],
        'delivery_zones' => [Models\DeliveryZone::class, 'manage_settings'],
        'efris_config' => [Models\EfrisConfig::class, 'manage_settings'],
        'efris_devices' => [Models\EfrisDevice::class, 'manage_settings'],
        'email_settings' => [Models\EmailSetting::class, 'manage_settings'],
        'expense_categories' => [Models\ExpenseCategory::class, 'manage_expenses'],
        'expenses' => [Models\Expense::class, 'manage_expenses'],
        'iotec_collections' => [Models\IotecCollection::class, 'manage_sales'],
        'journal_entries' => [Models\JournalEntry::class, 'manage_statements'],
        'nav_pages' => [Models\NavPage::class, 'manage_settings'],
        'notifications_app' => [Models\AppNotification::class, 'manage_notifications'],
        'order_items' => [Models\OrderItem::class, 'manage_orders'],
        'order_logs' => [Models\OrderLog::class, 'manage_orders'],
        'order_payment_receipts' => [Models\OrderPaymentReceipt::class, 'manage_orders'],
        'orders' => [Models\Order::class, 'manage_orders'],
        'payment_gateways' => [Models\PaymentGateway::class, 'manage_settings'],
        'payment_settings' => [Models\PaymentSetting::class, 'manage_settings'],
        'payments' => [Models\Payment::class, 'manage_sales'],
        'permissions' => [Models\Permission::class, 'manage_users'],
        'product_branches' => [Models\ProductBranch::class, 'manage_products'],
        'product_images' => [Models\ProductImage::class, 'manage_products'],
        'products' => [Models\Product::class, 'manage_products'],
        'sale_items' => [Models\SaleItem::class, 'manage_sales'],
        'sales' => [Models\Sale::class, 'manage_sales'],
        'slides' => [Models\Slide::class, 'manage_settings'],
        'stock' => [Models\Stock::class, 'manage_stock'],
        'suppliers' => [Models\Supplier::class, 'manage_suppliers'],
        'users' => [Models\User::class, 'manage_users'],
    ];

    public const MAX_IDS = 500;

    public function destroy(Request $request, string $resource)
    {
        abort_unless(isset(self::RESOURCES[$resource]), 404);
        [$modelClass] = self::RESOURCES[$resource];

        $validated = $request->validate([
            'ids' => ['required', 'array', 'min:1', 'max:'.self::MAX_IDS],
            'ids.*' => ['integer', 'min:1'],
        ], [
            'ids.required' => 'Select at least one record to delete.',
            'ids.max' => 'You can delete at most '.self::MAX_IDS.' records at a time.',
        ]);

        $ids = array_values(array_unique(array_map('intval', $validated['ids'])));
        $records = $modelClass::query()->whereKey($ids)->get();

        $skippedSelf = false;
        if ($resource === 'users') {
            $records = $records->reject(function ($user) use (&$skippedSelf) {
                $isSelf = (int) $user->getKey() === (int) auth()->id();
                $skippedSelf = $skippedSelf || $isSelf;
                return $isSelf;
            });
        }

        $deleted = 0;
        $failed = 0;
        foreach ($records as $record) {
            try {
                // One transaction per record so a blocked delete doesn't undo the others.
                DB::transaction(fn () => $record->delete());
                $deleted++;
            } catch (QueryException $e) {
                report($e);
                $failed++;
            }
        }

        $redirect = back()->with('status', $deleted === 1 ? '1 record deleted.' : "{$deleted} records deleted.");

        $errors = [];
        if ($failed) {
            $errors[] = $failed === 1
                ? '1 record could not be deleted because other records still use it.'
                : "{$failed} records could not be deleted because other records still use them.";
        }
        if ($skippedSelf) {
            $errors[] = 'Your own account was not deleted.';
        }
        $missing = count($ids) - $records->count() - ($skippedSelf ? 1 : 0);
        if ($missing > 0) {
            $errors[] = $missing === 1
                ? '1 selected record no longer exists.'
                : "{$missing} selected records no longer exist.";
        }

        return $errors ? $redirect->withErrors(['bulk_delete' => $errors]) : $redirect;
    }
}
