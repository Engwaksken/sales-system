<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\EfrisInvoiceController;
use App\Http\Controllers\IotecPaymentController;
use App\Http\Controllers\ShopController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\BulkUploadController;
use App\Http\Controllers\ProductsController;
use App\Http\Controllers\CustomersController;
use App\Http\Controllers\StockController;
use App\Http\Controllers\SalesController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\RolePermissionsController;
use App\Http\Controllers\ChatController;
use App\Http\Controllers\BranchesController;
use App\Http\Controllers\CategoriesController;
use App\Http\Controllers\ExpenseCategoriesController;
use App\Http\Controllers\PermissionsController;
use App\Http\Controllers\NavPagesController;
use App\Http\Controllers\DeliveryZonesController;
use App\Http\Controllers\ChartOfAccountsController;
use App\Http\Controllers\EmailSettingsController;
use App\Http\Controllers\SlidesController;
use App\Http\Controllers\PaymentSettingsController;
use App\Http\Controllers\IotecCollectionsController;
use App\Http\Controllers\UsersController;
use App\Http\Controllers\PaymentGatewaysController;
use App\Http\Controllers\SuppliersController;
use App\Http\Controllers\ProductImagesController;
use App\Http\Controllers\ProductBranchesController;
use App\Http\Controllers\SaleItemsController;
use App\Http\Controllers\OrdersController;
use App\Http\Controllers\OrderItemsController;
use App\Http\Controllers\OrderLogsController;
use App\Http\Controllers\OrderPaymentReceiptsController;
use App\Http\Controllers\PaymentsController;
use App\Http\Controllers\ExpensesController;
use App\Http\Controllers\EfrisConfigController;
use App\Http\Controllers\EfrisDevicesController;
use App\Http\Controllers\EfrisApiLogsController;
use App\Http\Controllers\AccountingJournalsController;
use App\Http\Controllers\JournalEntriesController;
use App\Http\Controllers\NotificationsAppController;
use App\Http\Controllers\UserNotificationController;

Route::get('/login', [LoginController::class, 'create'])->name('login');
Route::post('/login', [LoginController::class, 'store']);
Route::post('/logout', [LoginController::class, 'destroy'])->name('logout');

// Password recovery with 6-digit email OTP
Route::get('/forgot-password', [LoginController::class, 'forgotForm'])->name('password.request');
Route::post('/forgot-password', [LoginController::class, 'sendResetOtp'])->name('password.email')->middleware('throttle:5,1');
Route::get('/verify-otp', [LoginController::class, 'otpForm'])->name('password.otp');
Route::post('/verify-otp', [LoginController::class, 'verifyOtp'])->name('password.otp.verify')->middleware('throttle:10,1');
Route::post('/verify-otp/resend', [LoginController::class, 'resendOtp'])->name('password.otp.resend')->middleware('throttle:3,1');
Route::get('/reset-password', [LoginController::class, 'resetForm'])->name('password.reset');
Route::post('/reset-password', [LoginController::class, 'resetPassword'])->name('password.update');

// Public storefront + guest checkout
Route::get('/shop', [ShopController::class, 'index'])->name('shop.index');
Route::get('/shop/product-image/{product}', [ShopController::class, 'productImage'])->name('shop.product-image');
Route::post('/checkout', [CheckoutController::class, 'store'])->name('checkout.store');

// Authenticated customer chat widget endpoints retain web/CSRF protection.
Route::prefix('chat')->name('chat.')->middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::post('/start', [ChatController::class, 'start'])->name('start');
    Route::post('/{conversation}/message', [ChatController::class, 'sendMessage'])->name('send');
    Route::get('/{conversation}/messages', [ChatController::class, 'messages'])->name('messages');
});
Route::get('/chat/file/{message}', [ChatController::class, 'attachment'])->name('chat.attachment')->middleware('signed');

Route::prefix('my-notifications')->name('my-notifications.')->middleware(['auth', 'throttle:60,1'])->group(function () {
    Route::get('/', [UserNotificationController::class, 'index'])->name('index');
    Route::post('/read-all', [UserNotificationController::class, 'readAll'])->name('read-all');
    Route::get('/orders/{order}', [UserNotificationController::class, 'showCustomerOrder'])->name('orders.show');
    Route::post('/orders/{order}/read', [UserNotificationController::class, 'readOrder'])->name('orders.read');
    Route::post('/customer-chats/{conversation}/read', [UserNotificationController::class, 'readCustomerChat'])->name('customer-chats.read');
    Route::post('/{notification}/read', [UserNotificationController::class, 'read'])->name('read');
});

// Public mobile-money endpoints — called by the storefront/checkout (collect, status)
// and by the IOTEC gateway itself (webhook). None require a logged-in user, matching
// the original iotec_collect.php / check_payment_status.php / iotec_webhook.php.
Route::post('/iotec/collect', [IotecPaymentController::class, 'collect'])->name('iotec.collect');
Route::post('/iotec/webhook', [IotecPaymentController::class, 'webhook'])->name('iotec.webhook');
Route::match(['get', 'post'], '/payments/check-status', [IotecPaymentController::class, 'status'])->name('iotec.status');

Route::middleware('auth')->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
    Route::get('/reports/{type?}', [ReportController::class, 'show'])->name('reports.show');

    // Exports (CSV/PDF) — must be registered before the resource routes below so
    // 'export' isn't swallowed by Route::resource's {product} etc. wildcard.
    // PDF exports have no generic equivalent — kept as specials.
    Route::get('/stock/report/pdf', [StockController::class, 'reportPdf'])->name('stock.report.pdf')->middleware('permission:manage_stock');
    Route::get('/sales/{sale}/invoice.pdf', [SalesController::class, 'invoicePdf'])->name('sales.invoice-pdf')->middleware('permission:manage_sales');
    Route::get('/sales/{sale}/invoice/preview', [SalesController::class, 'invoicePreview'])->name('sales.invoice-preview')->middleware('permission:manage_sales');
    Route::get('/sales/{sale}/invoice/download', [SalesController::class, 'invoiceDownload'])->name('sales.invoice-download')->middleware('permission:manage_sales');
    Route::get('/sales/{sale}/invoice/print', [SalesController::class, 'invoicePrint'])->name('sales.invoice-print')->middleware('permission:manage_sales');
    Route::post('/sales/pos', [SalesController::class, 'posStore'])->name('sales.pos-store')->middleware('permission:manage_sales');
    Route::post('/sales/{sale}/payments', [SalesController::class, 'recordPayment'])->name('sales.record-payment')->middleware('permission:manage_sales');
    Route::get('/payments/{payment}/receipt.pdf', [PaymentsController::class, 'receiptPdf'])->name('payments.receipt-pdf')->middleware('permission:manage_sales');
    Route::get('/payments/{payment}/receipt/preview', [PaymentsController::class, 'receiptPreview'])->name('payments.receipt-preview')->middleware('permission:manage_sales');
    Route::get('/payments/{payment}/receipt/download', [PaymentsController::class, 'receiptDownload'])->name('payments.receipt-download')->middleware('permission:manage_sales');
    Route::get('/payments/{payment}/receipt/print', [PaymentsController::class, 'receiptPrint'])->name('payments.receipt-print')->middleware('permission:manage_sales');

    // Bulk upload
    Route::get('/bulk-upload', [BulkUploadController::class, 'index'])->name('bulk-upload.index')->middleware('permission:manage_products');
    Route::post('/bulk-upload/products', [BulkUploadController::class, 'importProducts'])->name('bulk-upload.products')->middleware('permission:manage_products');
    Route::post('/bulk-upload/customers', [BulkUploadController::class, 'importCustomers'])->name('bulk-upload.customers')->middleware('permission:manage_customers');

    // CSV import for data pages (each page's permission is checked in ImportController)
    Route::get('/imports/{module}/template', [\App\Http\Controllers\ImportController::class, 'template'])->name('imports.template');
    Route::post('/imports/{module}/preview', [\App\Http\Controllers\ImportController::class, 'preview'])->name('imports.preview')->middleware('throttle:20,1');
    Route::post('/imports/{module}/confirm', [\App\Http\Controllers\ImportController::class, 'confirm'])->name('imports.confirm')->middleware('throttle:20,1');

    Route::resource('efris_invoices', EfrisInvoiceController::class)->only(['index', 'show'])->middleware('permission:manage_settings');
    Route::post('/sales/{sale}/efris-submit', [EfrisInvoiceController::class, 'submit'])->name('sales.efris-submit')->middleware('permission:manage_settings');

    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index')->middleware('permission:manage_settings');
    Route::put('/settings', [SettingsController::class, 'update'])->name('settings.update')->middleware('permission:manage_settings');

    Route::get('/role-permissions', [RolePermissionsController::class, 'index'])->name('role_permissions.index')->middleware('permission:manage_users');
    Route::put('/role-permissions', [RolePermissionsController::class, 'update'])->name('role_permissions.update')->middleware('permission:manage_users');

    Route::get('/chat-support', [ChatController::class, 'index'])->name('chat.index')->middleware('permission:manage_chats');
    Route::get('/chat-support/{conversation}', [ChatController::class, 'show'])->name('chat.show')->middleware('permission:manage_chats');
    Route::get('/chat-support/{conversation}/messages', [ChatController::class, 'staffMessages'])->name('chat.staff-messages')->middleware('permission:manage_chats');
    Route::post('/chat-support/{conversation}/reply', [ChatController::class, 'reply'])->name('chat.reply')->middleware('permission:manage_chats');
    Route::post('/chat-support/{conversation}/close', [ChatController::class, 'close'])->name('chat.close')->middleware('permission:manage_chats');

    Route::get('/branches/export/csv', [BranchesController::class, 'exportCsv'])->name('branches.export.csv')->middleware('permission:manage_branches');
    Route::get('/branches/export/pdf', [BranchesController::class, 'exportPdf'])->name('branches.export.pdf')->middleware('permission:manage_branches');
    Route::resource('branches', BranchesController::class)->except(['create', 'edit'])->middleware('permission:manage_branches');
    Route::get('/categories/export/csv', [CategoriesController::class, 'exportCsv'])->name('categories.export.csv')->middleware('permission:manage_categories');
    Route::get('/categories/export/pdf', [CategoriesController::class, 'exportPdf'])->name('categories.export.pdf')->middleware('permission:manage_categories');
    Route::resource('categories', CategoriesController::class)->except(['create', 'edit'])->middleware('permission:manage_categories');
    Route::get('/expense_categories/export/csv', [ExpenseCategoriesController::class, 'exportCsv'])->name('expense_categories.export.csv')->middleware('permission:manage_expenses');
    Route::get('/expense_categories/export/pdf', [ExpenseCategoriesController::class, 'exportPdf'])->name('expense_categories.export.pdf')->middleware('permission:manage_expenses');
    Route::resource('expense_categories', ExpenseCategoriesController::class)->except(['create', 'edit'])->middleware('permission:manage_expenses');
    Route::get('/permissions/export/csv', [PermissionsController::class, 'exportCsv'])->name('permissions.export.csv')->middleware('permission:manage_users');
    Route::get('/permissions/export/pdf', [PermissionsController::class, 'exportPdf'])->name('permissions.export.pdf')->middleware('permission:manage_users');
    Route::resource('permissions', PermissionsController::class)->except(['create', 'edit'])->middleware('permission:manage_users');
    Route::get('/nav_pages/export/csv', [NavPagesController::class, 'exportCsv'])->name('nav_pages.export.csv')->middleware('permission:manage_settings');
    Route::get('/nav_pages/export/pdf', [NavPagesController::class, 'exportPdf'])->name('nav_pages.export.pdf')->middleware('permission:manage_settings');
    Route::resource('nav_pages', NavPagesController::class)->except(['create', 'edit'])->middleware('permission:manage_settings');
    Route::get('/delivery_zones/export/csv', [DeliveryZonesController::class, 'exportCsv'])->name('delivery_zones.export.csv')->middleware('permission:manage_settings');
    Route::get('/delivery_zones/export/pdf', [DeliveryZonesController::class, 'exportPdf'])->name('delivery_zones.export.pdf')->middleware('permission:manage_settings');
    Route::resource('delivery_zones', DeliveryZonesController::class)->except(['create', 'edit'])->middleware('permission:manage_settings');
    Route::get('/chart_of_accounts/export/csv', [ChartOfAccountsController::class, 'exportCsv'])->name('chart_of_accounts.export.csv')->middleware('permission:manage_statements');
    Route::get('/chart_of_accounts/export/pdf', [ChartOfAccountsController::class, 'exportPdf'])->name('chart_of_accounts.export.pdf')->middleware('permission:manage_statements');
    Route::resource('chart_of_accounts', ChartOfAccountsController::class)->except(['create', 'edit'])->middleware('permission:manage_statements');
    Route::get('/email_settings/export/csv', [EmailSettingsController::class, 'exportCsv'])->name('email_settings.export.csv')->middleware('permission:manage_settings');
    Route::get('/email_settings/export/pdf', [EmailSettingsController::class, 'exportPdf'])->name('email_settings.export.pdf')->middleware('permission:manage_settings');
    Route::resource('email_settings', EmailSettingsController::class)->except(['create', 'edit'])->middleware('permission:manage_settings');
    Route::get('/slides/export/csv', [SlidesController::class, 'exportCsv'])->name('slides.export.csv')->middleware('permission:manage_settings');
    Route::get('/slides/export/pdf', [SlidesController::class, 'exportPdf'])->name('slides.export.pdf')->middleware('permission:manage_settings');
    Route::resource('slides', SlidesController::class)->except(['create', 'edit'])->middleware('permission:manage_settings');
    Route::get('/payment_settings/export/csv', [PaymentSettingsController::class, 'exportCsv'])->name('payment_settings.export.csv')->middleware('permission:manage_settings');
    Route::get('/payment_settings/export/pdf', [PaymentSettingsController::class, 'exportPdf'])->name('payment_settings.export.pdf')->middleware('permission:manage_settings');
    Route::resource('payment_settings', PaymentSettingsController::class)->except(['create', 'edit'])->middleware('permission:manage_settings');
    Route::get('/iotec_collections/export/csv', [IotecCollectionsController::class, 'exportCsv'])->name('iotec_collections.export.csv')->middleware('permission:manage_sales');
    Route::get('/iotec_collections/export/pdf', [IotecCollectionsController::class, 'exportPdf'])->name('iotec_collections.export.pdf')->middleware('permission:manage_sales');
    Route::resource('iotec_collections', IotecCollectionsController::class)->except(['create', 'edit'])->middleware('permission:manage_sales');
    Route::get('/users/export/csv', [UsersController::class, 'exportCsv'])->name('users.export.csv')->middleware('permission:manage_users');
    Route::get('/users/export/pdf', [UsersController::class, 'exportPdf'])->name('users.export.pdf')->middleware('permission:manage_users');
    Route::resource('users', UsersController::class)->except(['create', 'edit'])->middleware('permission:manage_users');
    Route::get('/payment_gateways/export/csv', [PaymentGatewaysController::class, 'exportCsv'])->name('payment_gateways.export.csv')->middleware('permission:manage_settings');
    Route::get('/payment_gateways/export/pdf', [PaymentGatewaysController::class, 'exportPdf'])->name('payment_gateways.export.pdf')->middleware('permission:manage_settings');
    Route::resource('payment_gateways', PaymentGatewaysController::class)->except(['create', 'edit'])->middleware('permission:manage_settings');
    Route::get('/customers/export/csv', [CustomersController::class, 'exportCsv'])->name('customers.export.csv')->middleware('permission:manage_customers');
    Route::get('/customers/export/pdf', [CustomersController::class, 'exportPdf'])->name('customers.export.pdf')->middleware('permission:manage_customers');
    Route::resource('customers', CustomersController::class)->except(['create', 'edit'])->middleware('permission:manage_customers');
    Route::get('/suppliers/export/csv', [SuppliersController::class, 'exportCsv'])->name('suppliers.export.csv')->middleware('permission:manage_suppliers');
    Route::get('/suppliers/export/pdf', [SuppliersController::class, 'exportPdf'])->name('suppliers.export.pdf')->middleware('permission:manage_suppliers');
    Route::resource('suppliers', SuppliersController::class)->except(['create', 'edit'])->middleware('permission:manage_suppliers');
    Route::get('/products/export/csv', [ProductsController::class, 'exportCsv'])->name('products.export.csv')->middleware('permission:manage_products');
    Route::get('/products/export/pdf', [ProductsController::class, 'exportPdf'])->name('products.export.pdf')->middleware('permission:manage_products');
    Route::resource('products', ProductsController::class)->except(['create', 'edit'])->middleware('permission:manage_products');
    Route::get('/product_images/export/csv', [ProductImagesController::class, 'exportCsv'])->name('product_images.export.csv')->middleware('permission:manage_products');
    Route::get('/product_images/export/pdf', [ProductImagesController::class, 'exportPdf'])->name('product_images.export.pdf')->middleware('permission:manage_products');
    Route::resource('product_images', ProductImagesController::class)->except(['create', 'edit'])->middleware('permission:manage_products');
    Route::get('/product_branches/export/csv', [ProductBranchesController::class, 'exportCsv'])->name('product_branches.export.csv')->middleware('permission:manage_products');
    Route::get('/product_branches/export/pdf', [ProductBranchesController::class, 'exportPdf'])->name('product_branches.export.pdf')->middleware('permission:manage_products');
    Route::resource('product_branches', ProductBranchesController::class)->except(['create', 'edit'])->middleware('permission:manage_products');
    Route::get('/stock/export/csv', [StockController::class, 'exportCsv'])->name('stock.export.csv')->middleware('permission:manage_stock');
    Route::get('/stock/export/pdf', [StockController::class, 'exportPdf'])->name('stock.export.pdf')->middleware('permission:manage_stock');
    Route::resource('stock', StockController::class)->except(['create', 'edit'])->middleware('permission:manage_stock');
    Route::get('/sales/export/csv', [SalesController::class, 'exportCsv'])->name('sales.export.csv')->middleware('permission:manage_sales');
    Route::get('/sales/export/pdf', [SalesController::class, 'exportPdf'])->name('sales.export.pdf')->middleware('permission:manage_sales');
    Route::resource('sales', SalesController::class)->except(['create', 'edit'])->middleware('permission:manage_sales');
    Route::get('/sale_items/export/csv', [SaleItemsController::class, 'exportCsv'])->name('sale_items.export.csv')->middleware('permission:manage_sales');
    Route::get('/sale_items/export/pdf', [SaleItemsController::class, 'exportPdf'])->name('sale_items.export.pdf')->middleware('permission:manage_sales');
    Route::resource('sale_items', SaleItemsController::class)->except(['create', 'edit'])->middleware('permission:manage_sales');
    Route::get('/orders/export/csv', [OrdersController::class, 'exportCsv'])->name('orders.export.csv')->middleware('permission:manage_orders');
    Route::get('/orders/export/pdf', [OrdersController::class, 'exportPdf'])->name('orders.export.pdf')->middleware('permission:manage_orders');
    Route::patch('/orders/{order}/stage', [OrdersController::class, 'updateStage'])->name('orders.stage')->middleware('permission:manage_orders');
    Route::resource('orders', OrdersController::class)->except(['create', 'edit'])->middleware('permission:manage_orders');
    Route::get('/order_items/export/csv', [OrderItemsController::class, 'exportCsv'])->name('order_items.export.csv')->middleware('permission:manage_orders');
    Route::get('/order_items/export/pdf', [OrderItemsController::class, 'exportPdf'])->name('order_items.export.pdf')->middleware('permission:manage_orders');
    Route::resource('order_items', OrderItemsController::class)->except(['create', 'edit'])->middleware('permission:manage_orders');
    Route::get('/order_logs/export/csv', [OrderLogsController::class, 'exportCsv'])->name('order_logs.export.csv')->middleware('permission:manage_orders');
    Route::get('/order_logs/export/pdf', [OrderLogsController::class, 'exportPdf'])->name('order_logs.export.pdf')->middleware('permission:manage_orders');
    Route::resource('order_logs', OrderLogsController::class)->except(['create', 'edit'])->middleware('permission:manage_orders');
    Route::get('/order_payment_receipts/export/csv', [OrderPaymentReceiptsController::class, 'exportCsv'])->name('order_payment_receipts.export.csv')->middleware('permission:manage_orders');
    Route::get('/order_payment_receipts/export/pdf', [OrderPaymentReceiptsController::class, 'exportPdf'])->name('order_payment_receipts.export.pdf')->middleware('permission:manage_orders');
    Route::get('/order_payment_receipts/order/{order}/details', [OrderPaymentReceiptsController::class, 'orderDetails'])->name('order_payment_receipts.order-details')->middleware('permission:manage_orders');
    Route::resource('order_payment_receipts', OrderPaymentReceiptsController::class)->except(['create', 'edit'])->middleware('permission:manage_orders');
    Route::get('/payments/export/csv', [PaymentsController::class, 'exportCsv'])->name('payments.export.csv')->middleware('permission:manage_sales');
    Route::get('/payments/export/pdf', [PaymentsController::class, 'exportPdf'])->name('payments.export.pdf')->middleware('permission:manage_sales');
    Route::resource('payments', PaymentsController::class)->except(['create', 'edit'])->middleware('permission:manage_sales');
    Route::get('/expenses/export/csv', [ExpensesController::class, 'exportCsv'])->name('expenses.export.csv')->middleware('permission:manage_expenses');
    Route::get('/expenses/export/pdf', [ExpensesController::class, 'exportPdf'])->name('expenses.export.pdf')->middleware('permission:manage_expenses');
    Route::resource('expenses', ExpensesController::class)->except(['create', 'edit'])->middleware('permission:manage_expenses');
    Route::get('/efris_config/export/csv', [EfrisConfigController::class, 'exportCsv'])->name('efris_config.export.csv')->middleware('permission:manage_settings');
    Route::get('/efris_config/export/pdf', [EfrisConfigController::class, 'exportPdf'])->name('efris_config.export.pdf')->middleware('permission:manage_settings');
    Route::resource('efris_config', EfrisConfigController::class)->except(['create', 'edit'])->middleware('permission:manage_settings');
    Route::get('/efris_devices/export/csv', [EfrisDevicesController::class, 'exportCsv'])->name('efris_devices.export.csv')->middleware('permission:manage_settings');
    Route::get('/efris_devices/export/pdf', [EfrisDevicesController::class, 'exportPdf'])->name('efris_devices.export.pdf')->middleware('permission:manage_settings');
    Route::resource('efris_devices', EfrisDevicesController::class)->except(['create', 'edit'])->middleware('permission:manage_settings');
    Route::get('/efris_api_logs/export/csv', [EfrisApiLogsController::class, 'exportCsv'])->name('efris_api_logs.export.csv')->middleware('permission:manage_settings');
    Route::get('/efris_api_logs/export/pdf', [EfrisApiLogsController::class, 'exportPdf'])->name('efris_api_logs.export.pdf')->middleware('permission:manage_settings');
    Route::resource('efris_api_logs', EfrisApiLogsController::class)->only(['index', 'show'])->middleware('permission:manage_settings');
    Route::get('/accounting_journals/export/csv', [AccountingJournalsController::class, 'exportCsv'])->name('accounting_journals.export.csv')->middleware('permission:manage_statements');
    Route::get('/accounting_journals/export/pdf', [AccountingJournalsController::class, 'exportPdf'])->name('accounting_journals.export.pdf')->middleware('permission:manage_statements');
    Route::resource('accounting_journals', AccountingJournalsController::class)->except(['create', 'edit'])->middleware('permission:manage_statements');
    Route::get('/journal_entries/export/csv', [JournalEntriesController::class, 'exportCsv'])->name('journal_entries.export.csv')->middleware('permission:manage_statements');
    Route::get('/journal_entries/export/pdf', [JournalEntriesController::class, 'exportPdf'])->name('journal_entries.export.pdf')->middleware('permission:manage_statements');
    Route::resource('journal_entries', JournalEntriesController::class)->except(['create', 'edit'])->middleware('permission:manage_statements');
    Route::get('/notifications_app/export/csv', [NotificationsAppController::class, 'exportCsv'])->name('notifications_app.export.csv')->middleware('permission:manage_notifications');
    Route::get('/notifications_app/export/pdf', [NotificationsAppController::class, 'exportPdf'])->name('notifications_app.export.pdf')->middleware('permission:manage_notifications');
    Route::resource('notifications_app', NotificationsAppController::class)->except(['create', 'edit'])->middleware('permission:manage_notifications');
});
