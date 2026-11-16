<?php

namespace App\Support;

use App\Models\AccountingJournal;
use App\Models\AppNotification;
use App\Models\Branch;
use App\Models\ChartOfAccount;
use App\Models\DeliveryZone;
use App\Models\EfrisApiLog;
use App\Models\EfrisConfig;
use App\Models\EfrisDevice;
use App\Models\EfrisInvoice;
use App\Models\EmailSetting;
use App\Models\IotecCollection;
use App\Models\JournalEntry;
use App\Models\NavPage;
use App\Models\OrderLog;
use App\Models\OrderPaymentReceipt;
use App\Models\PaymentGateway;
use App\Models\PaymentSetting;
use App\Models\Permission;
use App\Models\Slide;
use App\Models\User;

/**
 * The website's admin modules, described so the mobile app can list, view,
 * create, edit and delete them with the same rules and permissions.
 *
 * Validation rules are the website controllers' rules (see e.g. BranchesController).
 * Field types drive the app's form: text, textarea, email, number, money, bool,
 * select (fixed options), relation (options from another table), date, datetime,
 * password, color, url, secret (never sent to the app; blank keeps the old value).
 */
class AdminResources
{
    public static function all(): array
    {
        return [
            'users' => [
                'label' => 'Users', 'group' => 'Admin', 'icon' => 'manage_accounts', 'model' => User::class, 'permission' => 'manage_users',
                'title' => 'name', 'subtitle' => ['email', 'role'], 'search' => ['name', 'email'],
                'fields' => [
                    self::f('name', 'text', 'Full name', 'required|string'),
                    self::f('email', 'email', 'Email', 'required|email|unique:users,email,{id}'),
                    self::f('password', 'password', 'Password', 'required|string|min:8', ['update_rules' => 'nullable|string|min:8', 'help' => 'Leave blank to keep the current password.']),
                    self::f('role', 'select', 'Role', ['required', 'in:'.implode(',', array_keys(User::roleOptions()))], ['options' => User::roleOptions()]),
                    self::f('branch_id', 'relation', 'Branch', 'nullable|exists:branches,id', ['source' => 'branches']),
                ],
            ],
            'permissions' => [
                'label' => 'Permissions', 'group' => 'Admin', 'icon' => 'key', 'model' => Permission::class, 'permission' => 'manage_users',
                'title' => 'label', 'subtitle' => ['permission_key', 'category'], 'search' => ['label', 'permission_key', 'category'],
                'fields' => [
                    self::f('permission_key', 'text', 'Permission key', 'required'),
                    self::f('label', 'text', 'Label', 'required'),
                    self::f('category', 'text', 'Category', 'required'),
                    self::f('description', 'textarea', 'Description', 'required'),
                ],
            ],
            'notifications' => [
                'label' => 'Notifications', 'group' => 'Admin', 'icon' => 'campaign', 'model' => AppNotification::class, 'permission' => 'manage_notifications',
                'title' => 'title', 'subtitle' => ['type', 'created_at'], 'search' => ['title', 'message', 'type'],
                'fields' => [
                    self::f('title', 'text', 'Title', 'required'),
                    self::f('message', 'textarea', 'Message', 'required'),
                    self::f('type', 'select', 'Type', 'required', ['options' => ['info' => 'Info', 'success' => 'Success', 'warning' => 'Warning', 'alert' => 'Alert', 'order' => 'Order', 'system' => 'System'], 'allow_custom' => true]),
                    self::f('user_id', 'relation', 'Send to', 'nullable|exists:users,id', ['source' => 'users', 'empty' => 'Everyone']),
                    self::f('is_read', 'bool', 'Marked as read', 'boolean'),
                ],
            ],
            'branches' => [
                'label' => 'Branches', 'group' => 'Organization', 'icon' => 'store', 'model' => Branch::class, 'permission' => 'manage_branches',
                'title' => 'name', 'subtitle' => ['location', 'contact'], 'search' => ['name', 'location', 'contact', 'branch_email'],
                'fields' => [
                    self::f('name', 'text', 'Name', 'nullable'),
                    self::f('location', 'text', 'Location', 'nullable'),
                    self::f('contact', 'text', 'Phone', 'nullable'),
                    self::f('branch_email', 'email', 'Email', 'nullable'),
                ],
            ],
            'delivery-zones' => [
                'label' => 'Delivery Zones', 'group' => 'Organization', 'icon' => 'local_shipping', 'model' => DeliveryZone::class, 'permission' => 'manage_settings',
                'title' => 'zone_name', 'subtitle' => ['fee', 'is_active'], 'search' => ['zone_name', 'description'],
                'fields' => [
                    self::f('zone_name', 'text', 'Zone name', 'required'),
                    self::f('fee', 'money', 'Delivery fee', 'required|numeric'),
                    self::f('description', 'textarea', 'Description', 'nullable'),
                    self::f('is_active', 'bool', 'Active', 'boolean'),
                ],
            ],
            'chart-of-accounts' => [
                'label' => 'Chart of Accounts', 'group' => 'Accounting', 'icon' => 'account_tree', 'model' => ChartOfAccount::class, 'permission' => 'manage_statements',
                'title' => 'account_name', 'subtitle' => ['account_code', 'account_type'], 'search' => ['account_code', 'account_name', 'account_category'],
                'fields' => [
                    self::f('account_code', 'text', 'Account code', 'required'),
                    self::f('account_name', 'text', 'Account name', 'required'),
                    self::f('account_type', 'select', 'Type', 'required|in:Asset,Liability,Equity,Revenue,COGS,Expense', ['options' => self::same(['Asset', 'Liability', 'Equity', 'Revenue', 'COGS', 'Expense'])]),
                    self::f('account_category', 'text', 'Category', 'required'),
                    self::f('normal_balance', 'select', 'Normal balance', 'required|in:Debit,Credit', ['options' => self::same(['Debit', 'Credit'])]),
                    self::f('parent_code', 'text', 'Parent code', 'nullable'),
                    self::f('description', 'textarea', 'Description', 'nullable'),
                    self::f('is_active', 'bool', 'Active', 'boolean'),
                ],
            ],
            'journals' => [
                'label' => 'Journals', 'group' => 'Accounting', 'icon' => 'menu_book', 'model' => AccountingJournal::class, 'permission' => 'manage_statements',
                'title' => 'journal_no', 'subtitle' => ['journal_type', 'journal_date', 'description'], 'search' => ['journal_no', 'reference_no', 'description'],
                'fields' => [
                    self::f('journal_no', 'text', 'Journal number', 'required'),
                    self::f('journal_type', 'select', 'Journal type', 'required|in:GJ,SJ,PJ,CRJ,CPJ,MJ', ['options' => ['GJ' => 'General (GJ)', 'SJ' => 'Sales (SJ)', 'PJ' => 'Purchases (PJ)', 'CRJ' => 'Cash receipts (CRJ)', 'CPJ' => 'Cash payments (CPJ)', 'MJ' => 'Manual (MJ)']]),
                    self::f('journal_date', 'date', 'Date', 'required|date'),
                    self::f('description', 'textarea', 'Description', 'required'),
                    self::f('reference_no', 'text', 'Reference number', 'nullable'),
                    self::f('reference_type', 'text', 'Reference type', 'nullable'),
                    self::f('reference_id', 'number', 'Reference ID', 'nullable|numeric'),
                    self::f('branch_id', 'relation', 'Branch', 'nullable|exists:branches,id', ['source' => 'branches']),
                    self::f('is_posted', 'bool', 'Posted', 'boolean'),
                ],
            ],
            'journal-entries' => [
                'label' => 'Journal Entries', 'group' => 'Accounting', 'icon' => 'receipt_long', 'model' => JournalEntry::class, 'permission' => 'manage_statements',
                'title' => 'account_code', 'subtitle' => ['debit', 'credit', 'description'], 'search' => ['account_code', 'description'],
                'fields' => [
                    self::f('journal_id', 'relation', 'Journal', 'required|exists:accounting_journals,id', ['source' => 'journals']),
                    self::f('account_code', 'relation', 'Account', 'required', ['source' => 'accounts']),
                    self::f('description', 'textarea', 'Description', 'nullable'),
                    self::f('debit', 'money', 'Debit', 'required|numeric'),
                    self::f('credit', 'money', 'Credit', 'required|numeric'),
                ],
            ],
            'payment-gateways' => [
                'label' => 'Payment Gateways', 'group' => 'Payments', 'icon' => 'credit_card', 'model' => PaymentGateway::class, 'permission' => 'manage_settings',
                'title' => 'display_name', 'subtitle' => ['gateway_code', 'provider_type', 'is_active'], 'search' => ['gateway_name', 'gateway_code', 'display_name'],
                'fields' => [
                    self::f('gateway_name', 'text', 'Gateway name', 'required'),
                    self::f('gateway_code', 'text', 'Gateway code', 'required'),
                    self::f('display_name', 'text', 'Display name', 'required'),
                    self::f('provider_type', 'select', 'Provider type', 'required|in:mobile_money,card,bank,wallet,mixed', ['options' => ['mobile_money' => 'Mobile money', 'card' => 'Card', 'bank' => 'Bank', 'wallet' => 'Wallet', 'mixed' => 'Mixed']]),
                    self::f('description', 'textarea', 'Description', 'nullable'),
                    self::f('is_active', 'bool', 'Active', 'boolean'),
                    self::f('is_default', 'bool', 'Default gateway', 'boolean'),
                    self::f('sandbox_mode', 'bool', 'Sandbox (test) mode', 'boolean'),
                    self::f('supports_collection', 'bool', 'Supports collections', 'boolean'),
                    self::f('supports_disbursement', 'bool', 'Supports disbursements', 'boolean'),
                    self::f('supports_mtn', 'bool', 'Supports MTN', 'boolean'),
                    self::f('supports_airtel', 'bool', 'Supports Airtel', 'boolean'),
                    self::f('base_url', 'url', 'Base URL', 'nullable'),
                    self::f('token_url', 'url', 'Token URL', 'nullable'),
                    self::f('collect_url', 'url', 'Collect URL', 'nullable'),
                    self::f('status_url', 'url', 'Status URL', 'nullable'),
                    self::f('callback_url', 'url', 'Callback URL', 'nullable'),
                    self::f('return_url', 'url', 'Return URL', 'nullable'),
                    self::f('client_id', 'text', 'Client ID', 'nullable'),
                    self::f('client_secret', 'secret', 'Client secret', 'nullable'),
                    self::f('wallet_guid', 'text', 'Wallet ID', 'nullable'),
                ],
            ],
            'payment-settings' => [
                'label' => 'Payment Settings', 'group' => 'Payments', 'icon' => 'tune', 'model' => PaymentSetting::class, 'permission' => 'manage_settings',
                'title' => 'setting_key', 'subtitle' => ['setting_value'], 'search' => ['setting_key', 'setting_value'],
                'fields' => [
                    self::f('setting_key', 'text', 'Key', 'required'),
                    self::f('setting_value', 'textarea', 'Value', 'required'),
                ],
            ],
            'mobile-money' => [
                'label' => 'Mobile Money Collections', 'group' => 'Payments', 'icon' => 'phone_iphone', 'model' => IotecCollection::class, 'permission' => 'manage_sales',
                'title' => 'phone', 'subtitle' => ['amount', 'status', 'created_at'], 'search' => ['phone', 'order_id', 'iotec_ref', 'status'],
                'fields' => [
                    self::f('order_id', 'text', 'Order', 'required'),
                    self::f('phone', 'text', 'Phone', 'required'),
                    self::f('network', 'select', 'Network', 'required', ['options' => ['MTN' => 'MTN', 'AIRTEL' => 'Airtel'], 'allow_custom' => true]),
                    self::f('amount', 'money', 'Amount', 'required|numeric'),
                    self::f('iotec_ref', 'text', 'Gateway reference', 'nullable'),
                    self::f('status', 'select', 'Status', 'required', ['options' => self::same(['pending', 'successful', 'failed']), 'allow_custom' => true]),
                    self::f('raw_response', 'textarea', 'Gateway response', 'nullable'),
                ],
            ],
            'order-payment-receipts' => [
                'label' => 'Order Payment Receipts', 'group' => 'Sales', 'icon' => 'receipt', 'model' => OrderPaymentReceipt::class, 'permission' => 'manage_orders',
                'title' => 'receipt_number', 'subtitle' => ['customer_name', 'amount_paid', 'payment_method'], 'search' => ['receipt_number', 'order_number', 'customer_name', 'phone'],
                'fields' => [
                    self::f('order_id', 'relation', 'Order', 'required|exists:orders,id', ['source' => 'orders']),
                    self::f('order_number', 'text', 'Order number', 'required'),
                    self::f('receipt_number', 'text', 'Receipt number', 'required'),
                    self::f('customer_id', 'relation', 'Customer', 'nullable|exists:customers,id', ['source' => 'customers']),
                    self::f('customer_name', 'text', 'Customer name', 'required'),
                    self::f('phone', 'text', 'Phone', 'nullable'),
                    self::f('email', 'email', 'Email', 'nullable'),
                    self::f('payment_method', 'text', 'Payment method', 'required'),
                    self::f('amount_paid', 'money', 'Amount paid', 'required|numeric'),
                    self::f('currency', 'text', 'Currency', 'required', ['default' => 'UGX']),
                    self::f('gateway_code', 'text', 'Gateway code', 'nullable'),
                    self::f('gateway_reference', 'text', 'Gateway reference', 'nullable'),
                    self::f('gateway_status', 'text', 'Gateway status', 'nullable'),
                    self::f('notes', 'textarea', 'Notes', 'nullable'),
                ],
            ],
            'order-logs' => [
                'label' => 'Order Logs', 'group' => 'Sales', 'icon' => 'history', 'model' => OrderLog::class, 'permission' => 'manage_orders',
                'title' => 'action', 'subtitle' => ['description', 'created_at'], 'search' => ['action', 'description'],
                'fields' => [
                    self::f('order_id', 'relation', 'Order', 'required|exists:orders,id', ['source' => 'orders']),
                    self::f('action', 'text', 'Action', 'required'),
                    self::f('description', 'textarea', 'Description', 'nullable'),
                ],
            ],
            'slides' => [
                'label' => 'Shop Slides', 'group' => 'Storefront', 'icon' => 'view_carousel', 'model' => Slide::class, 'permission' => 'manage_settings',
                'title' => 'title', 'subtitle' => ['badge_text', 'sort_order', 'is_active'], 'search' => ['title', 'subtitle', 'badge_text'],
                'fields' => [
                    self::f('title', 'text', 'Title', 'required'),
                    self::f('subtitle', 'text', 'Subtitle', 'nullable'),
                    self::f('badge_text', 'text', 'Badge text', 'required'),
                    self::f('image_url', 'url', 'Image URL', 'required'),
                    self::f('cta_label', 'text', 'Button label', 'required'),
                    self::f('cta_link', 'url', 'Button link', 'required'),
                    self::f('bg_color', 'color', 'Background colour', 'required'),
                    self::f('sort_order', 'number', 'Sort order', 'required|numeric', ['default' => 0]),
                    self::f('is_active', 'bool', 'Active', 'boolean'),
                ],
            ],
            'nav-pages' => [
                'label' => 'Navigation Pages', 'group' => 'Storefront', 'icon' => 'menu', 'model' => NavPage::class, 'permission' => 'manage_settings',
                'title' => 'label', 'subtitle' => ['url', 'permission_key'], 'search' => ['label', 'url', 'permission_key'],
                'fields' => [
                    self::f('label', 'text', 'Label', 'required'),
                    self::f('url', 'text', 'URL', 'required'),
                    self::f('icon', 'text', 'Icon', 'required'),
                    self::f('parent_id', 'relation', 'Parent page', 'nullable|exists:nav_pages,id', ['source' => 'nav-pages']),
                    self::f('sort_order', 'number', 'Sort order', 'required|numeric', ['default' => 0]),
                    self::f('permission_key', 'relation', 'Permission', 'required', ['source' => 'permission-keys']),
                    self::f('is_active', 'bool', 'Active', 'boolean'),
                ],
            ],
            'email-settings' => [
                'label' => 'Email Settings', 'group' => 'Settings', 'icon' => 'mail', 'model' => EmailSetting::class, 'permission' => 'manage_settings',
                'title' => 'from_email', 'subtitle' => ['smtp_host', 'smtp_port'], 'search' => ['smtp_host', 'from_email', 'from_name'],
                'fields' => [
                    self::f('smtp_host', 'text', 'SMTP host', 'required'),
                    self::f('smtp_port', 'number', 'SMTP port', 'required|numeric'),
                    self::f('smtp_user', 'text', 'SMTP username', 'required'),
                    self::f('smtp_password', 'secret', 'SMTP password', 'required', ['update_rules' => 'nullable']),
                    self::f('smtp_secure', 'select', 'Encryption', 'required', ['options' => ['tls' => 'TLS', 'ssl' => 'SSL', 'none' => 'None'], 'allow_custom' => true]),
                    self::f('from_email', 'email', 'From email', 'required'),
                    self::f('from_name', 'text', 'From name', 'nullable'),
                ],
            ],
            'efris-config' => [
                'label' => 'EFRIS Config', 'group' => 'Tax (EFRIS)', 'icon' => 'verified_user', 'model' => EfrisConfig::class, 'permission' => 'manage_settings',
                'title' => 'taxpayer_name', 'subtitle' => ['tin', 'environment', 'is_active'], 'search' => ['tin', 'taxpayer_name'],
                'fields' => [
                    self::f('branch_id', 'relation', 'Branch', 'required|exists:branches,id', ['source' => 'branches']),
                    self::f('tin', 'text', 'TIN', 'required'),
                    self::f('taxpayer_name', 'text', 'Taxpayer name', 'required'),
                    self::f('taxpayer_address', 'textarea', 'Address', 'nullable'),
                    self::f('taxpayer_email', 'email', 'Email', 'nullable'),
                    self::f('taxpayer_phone', 'text', 'Phone', 'nullable'),
                    self::f('business_type', 'text', 'Business type', 'nullable'),
                    self::f('vat_registered', 'bool', 'VAT registered', 'boolean'),
                    self::f('environment', 'select', 'Environment', 'required|in:sandbox,production', ['options' => ['sandbox' => 'Sandbox (testing)', 'production' => 'Production']]),
                    self::f('portal_username', 'text', 'Portal username', 'nullable'),
                    self::f('portal_password_enc', 'secret', 'Portal password', 'nullable'),
                    self::f('private_key_pem', 'secret', 'Private key (PEM)', 'nullable', ['multiline' => true]),
                    self::f('public_key_pem', 'secret', 'Public key (PEM)', 'nullable', ['multiline' => true]),
                    self::f('ura_public_key_pem', 'secret', 'URA public key (PEM)', 'nullable', ['multiline' => true]),
                    self::f('is_active', 'bool', 'Active', 'boolean'),
                ],
            ],
            'efris-devices' => [
                'label' => 'EFRIS Devices', 'group' => 'Tax (EFRIS)', 'icon' => 'devices', 'model' => EfrisDevice::class, 'permission' => 'manage_settings',
                'title' => 'device_serial_no', 'subtitle' => ['device_name', 'activation_status'], 'search' => ['device_serial_no', 'device_name'],
                'fields' => [
                    self::f('config_id', 'relation', 'EFRIS config', 'required|exists:efris_config,id', ['source' => 'efris-config']),
                    self::f('branch_id', 'relation', 'Branch', 'required|exists:branches,id', ['source' => 'branches']),
                    self::f('device_serial_no', 'text', 'Device serial number', 'required'),
                    self::f('device_type', 'text', 'Device type', 'nullable'),
                    self::f('device_name', 'text', 'Device name', 'nullable'),
                    self::f('activation_code', 'text', 'Activation code', 'nullable'),
                    self::f('activation_status', 'select', 'Activation status', 'required|in:pending,activated,suspended', ['options' => self::same(['pending', 'activated', 'suspended'])]),
                    self::f('activated_at', 'datetime', 'Activated at', 'nullable'),
                    self::f('invoice_counter', 'number', 'Invoice counter', 'required|numeric', ['default' => 0]),
                    self::f('notes', 'textarea', 'Notes', 'nullable'),
                ],
            ],
            'efris-invoices' => [
                'label' => 'EFRIS Invoices', 'group' => 'Tax (EFRIS)', 'icon' => 'request_page', 'model' => EfrisInvoice::class, 'permission' => 'manage_settings',
                'read_only' => true, 'title' => null, 'subtitle' => [], 'search' => [],
                'fields' => [],
            ],
            'efris-api-logs' => [
                'label' => 'EFRIS API Logs', 'group' => 'Tax (EFRIS)', 'icon' => 'list_alt', 'model' => EfrisApiLog::class, 'permission' => 'manage_settings',
                'read_only' => true, 'title' => null, 'subtitle' => [], 'search' => [],
                'fields' => [],
            ],
        ];
    }

    public static function get(string $key): ?array
    {
        return self::all()[$key] ?? null;
    }

    private static function f(string $name, string $type, string $label, string|array $rules, array $extra = []): array
    {
        return ['name' => $name, 'type' => $type, 'label' => $label, 'rules' => $rules] + $extra;
    }

    private static function same(array $values): array
    {
        return array_combine($values, array_map('ucfirst', $values));
    }
}
