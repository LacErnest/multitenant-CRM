<?php

/*
|--------------------------------------------------------------------------
| Resource Management Routes
|--------------------------------------------------------------------------
| This file defines the routes related to resource management for a company.
| Managing resources is a key component of our application, and this file contains
| all the route definitions for managing and handling resource-related functionality.
|
| The routes in this file encompass various aspects of resource management, including:
| - Listing resources
| - Creating, updating, and deleting resource entries
| - Viewing individual resource details
| - Assigning resources to projects or users
|
| Maintaining proper organization and adhering to clear route naming conventions
| within this file is essential for consistency throughout the project.
|--------------------------------------------------------------------------
*/
use App\Enums\UserRole;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Resource\ResourceController;
use App\Http\Controllers\Service\ServiceController;


Route::namespace('Resources')->prefix('resources')->group(function () {
    Route::middleware(['auth:api'])->group(function () {
        Route::get('/', [ResourceController::class, 'all']);
        Route::get('/export', [ResourceController::class, 'exportDataTable']);
        Route::get('/suggest/{value?}', [ResourceController::class, 'suggest']);
        Route::get('/suggest_job_title/{value?}', [ResourceController::class, 'suggestJobTitle']);
        Route::post('/', [ResourceController::class, 'create'])
            ->name('create')
            ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                UserRole::owner()->getIndex()  . ',' .
                UserRole::pm()->getIndex()  . ',' .
                UserRole::hr()->getIndex()  . ',' .
                UserRole::accountant()->getIndex() . ',' .
                UserRole::pm_restricted()->getIndex()]);
        Route::post('/import', [ResourceController::class, 'uploadImportFile']);
        Route::post('/import/finalize', [ResourceController::class, 'finalizeImportFile']);
        Route::get('/{resource_id}/export/{type}/{extension}', [ResourceController::class, 'export']);
        Route::get('/{resource_id}/download/contract', [ResourceController::class, 'contractDownload'])->name('contract.download');
    });

    Route::middleware(['auth:api,resources'])->group(function () {
        Route::get('/{resource_id}', [ResourceController::class, 'index']);
        Route::put('/{resource_id}', [ResourceController::class, 'update']);
        Route::get('/{resource_id}/services', [ServiceController::class, 'getAllServices']);
        Route::post('/{resource_id}/services', [ResourceController::class, 'createServices']);
        Route::post('/{resource_id}/services/{service_id}', [ResourceController::class, 'updateService']);
        Route::post('/{resource_id}/purchase_orders/{purchase_order_id}/invoices/upload', [ResourceController::class, 'uploadInvoice']);
        Route::get('/{resource_id}/purchase_orders/{purchase_order_id}/invoices/{invoice_id}/download', [ResourceController::class, 'downloadInvoice']);
        Route::get('/{resource_id}/purchase_orders/{purchase_order_id}/export/{template_id}/{type}', [ResourceController::class, 'downloadPurchaseOrder']);
    });
});
