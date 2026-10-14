<?php

namespace App\Providers;

use App\Models\Expense;
use App\Models\OrderPaymentReceipt;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Setting;
use App\Observers\ExpenseObserver;
use App\Observers\OrderPaymentReceiptObserver;
use App\Observers\PaymentObserver;
use App\Observers\SaleObserver;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Throwable;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Sale::observe(SaleObserver::class);
        Expense::observe(ExpenseObserver::class);
        Payment::observe(PaymentObserver::class);
        OrderPaymentReceipt::observe(OrderPaymentReceiptObserver::class);

        View::share('navGroups', [
            'Dashboard' => [
                'dashboard' => ['label' => 'Dashboard', 'icon' => 'fa-gauge-high'],
            ],
            'Sales' => [
                'sales' => ['label' => 'Sales / POS', 'icon' => 'fa-cash-register'],
                'orders' => ['label' => 'Online Orders', 'icon' => 'fa-cart-shopping'],
                'order_logs' => ['label' => 'Order Logs', 'icon' => 'fa-clock-rotate-left'],
                'order_payment_receipts' => ['label' => 'Payment Receipts', 'icon' => 'fa-receipt'],
            ],
            'Support' => [
                'chat' => ['label' => 'Customer Chats', 'icon' => 'fa-comments'],
            ],
            'Catalog' => [
                'products' => ['label' => 'Products', 'icon' => 'fa-box'],
                'categories' => ['label' => 'Categories', 'icon' => 'fa-tags'],
                'stock' => ['label' => 'Stock', 'icon' => 'fa-warehouse'],
                'bulk-upload' => ['label' => 'Bulk Upload', 'icon' => 'fa-file-arrow-up'],
            ],
            'Purchasing' => [
                'suppliers' => ['label' => 'Suppliers', 'icon' => 'fa-truck'],
                'expenses' => ['label' => 'Expenses', 'icon' => 'fa-file-invoice-dollar'],
                'expense_categories' => ['label' => 'Expense Categories', 'icon' => 'fa-layer-group'],
            ],
            'Customers' => [
                'customers' => ['label' => 'Customers', 'icon' => 'fa-users'],
            ],
            'Payments' => [
                'payments' => ['label' => 'Payments', 'icon' => 'fa-money-bill-wave'],
                'payment_gateways' => ['label' => 'Payment Gateways', 'icon' => 'fa-credit-card'],
                'iotec_collections' => ['label' => 'Mobile Money Collections', 'icon' => 'fa-mobile-screen-button'],
            ],
            'Accounting' => [
                'chart_of_accounts' => ['label' => 'Chart of Accounts', 'icon' => 'fa-sitemap'],
                'accounting_journals' => ['label' => 'Journals', 'icon' => 'fa-book'],
                'reports' => ['label' => 'Financial Reports', 'icon' => 'fa-chart-line'],
            ],
            'Tax (EFRIS)' => [
                'efris_config' => ['label' => 'EFRIS Config', 'icon' => 'fa-shield-halved'],
                'efris_devices' => ['label' => 'EFRIS Devices', 'icon' => 'fa-satellite-dish'],
                'efris_invoices' => ['label' => 'EFRIS Invoices', 'icon' => 'fa-file-invoice'],
                'efris_api_logs' => ['label' => 'EFRIS API Logs', 'icon' => 'fa-list-check'],
            ],
            'Organization' => [
                'branches' => ['label' => 'Branches', 'icon' => 'fa-building'],
                'delivery_zones' => ['label' => 'Delivery Zones', 'icon' => 'fa-map-location-dot'],
            ],
            'Admin' => [
                'users' => ['label' => 'Users', 'icon' => 'fa-user-gear'],
                'permissions' => ['label' => 'Permissions', 'icon' => 'fa-lock'],
                'role_permissions' => ['label' => 'Role Permissions', 'icon' => 'fa-user-shield'],
                'settings' => ['label' => 'Settings', 'icon' => 'fa-gear'],
                'notifications_app' => ['label' => 'Notifications', 'icon' => 'fa-bell'],
            ],
        ]);

        /*
         * Share branding/theme settings with every Blade view.
         * The try/catch deliberately keeps artisan install/migrate commands safe
         * before the settings table exists.
         */
        View::composer('*', function ($view): void {
            $defaults = [
                'site_name' => 'Kemmytech Sales',
                'system_logo' => '',
                'system_favicon' => '',
                'theme_primary' => '#0f766e',
                'theme_secondary' => '#0ea5e9',
                'theme_accent' => '#f59e0b',
                'theme_sidebar' => '#0f172a',
                'theme_sidebar_text' => '#e2e8f0',
                'theme_background' => '#f8fafc',
                'theme_surface' => '#ffffff',
                'font_family' => 'Lato',
                'font_size' => '14',
                'accessibility_widget_enabled' => '1',
            ];

            try {
                if (!Schema::hasTable('settings')) {
                    $view->with('siteSettings', $defaults);
                    return;
                }

                $stored = Cache::remember('app.site_settings', now()->addMinutes(10), function (): array {
                    return Setting::query()
                        ->pluck('setting_value', 'setting_key')
                        ->toArray();
                });

                $view->with('siteSettings', array_merge($defaults, $stored));
            } catch (Throwable) {
                $view->with('siteSettings', $defaults);
            }
        });
    }
}
