<?php

namespace App\Providers;

use App\Http\Controllers\BulkUploadController;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\ServiceProvider;

class BulkUploadTemplateServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Route::middleware(['web', 'auth'])->group(function (): void {
            Route::get(
                '/bulk-upload/templates/products',
                [BulkUploadController::class, 'productsTemplate']
            )
                ->name('bulk-upload.templates.products')
                ->middleware('permission:manage_products');

            Route::get(
                '/bulk-upload/templates/customers',
                [BulkUploadController::class, 'customersTemplate']
            )
                ->name('bulk-upload.templates.customers')
                ->middleware('permission:manage_customers');
        });
    }
}
