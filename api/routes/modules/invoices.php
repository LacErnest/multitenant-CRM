<?php
/*
|--------------------------------------------------------------------------
| Invoices Routes
|--------------------------------------------------------------------------
| This file defines the routes related to invoices for a project.
| Invoices play a crucial role in our application, and this file contains
| all the route definitions for managing and handling invoices.
|
| The routes in this file cover various aspects of invoices, including:
| - Listing invoices
| - Creating new invoices
| - Updating existing invoices
| - Deleting invoices
| - Viewing individual invoices
|
| It's essential to maintain proper organization and adhere to clear route
| naming conventions within this file for consistency throughout the project.
|--------------------------------------------------------------------------
*/

use App\Enums\UserRole;
use App\Http\Controllers\Invoice\InvoiceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Payment\InvoicePaymentController;

Route::namespace('Projects')->prefix('projects')->group(function () {
    Route::middleware('project.id')->prefix('/{project_id}')->group(function () {
        Route::namespace('Invoices')->prefix('invoices')->group(function () {
            Route::get('/', [InvoiceController::class, 'getAllFromProject']);
            Route::get('/{invoice_id}', [InvoiceController::class, 'getSingleFromProject']);
            Route::post('/', [InvoiceController::class, 'createInvoice'])
                ->name('create')
                ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                    UserRole::owner()->getIndex()  . ',' .
                    UserRole::accountant()->getIndex()]);
            Route::post('/{invoice_id}/items', [InvoiceController::class, 'createItem']);
            Route::post('/{invoice_id}/price_modifiers', [InvoiceController::class, 'createPriceModifier']);
            Route::post('/{invoice_id}/clone', [InvoiceController::class, 'clone']);
            Route::put('/{invoice_id}', [InvoiceController::class, 'updateInvoice']);
            Route::put('/{invoice_id}/status', [InvoiceController::class, 'updateInvoiceStatus']);
            Route::put('/{invoice_id}/items/{item_id}', [InvoiceController::class, 'updateItem']);
            Route::put('/{invoice_id}/price_modifiers/{price_modifier_id}', [InvoiceController::class, 'updatePriceModifier']);
            Route::delete('/{invoice_id}/items', [InvoiceController::class, 'deleteItems']);
            Route::delete('/{invoice_id}/price_modifiers/{price_modifier_id}', [InvoiceController::class, 'deletePriceModifier']);
            Route::get('/{invoice_id}/export/{template_id}/{type}', [InvoiceController::class, 'export']);
            Route::get('/{invoice_id}/email-template', [InvoiceController::class, 'getEmailTemplate']);
            Route::put('/{invoice_id}/sending-reminders-status', [InvoiceController::class, 'toggleSendingRemindersStatus']);

            Route::namespace('Invoices')->prefix('/{invoice_id}/payments')->group(function () {
                Route::get('/', [InvoicePaymentController::class, 'getAllFromInvoice']);
                Route::get('/{invoice_payment_id}', [InvoicePaymentController::class, 'getSingleFromInvoice']);
                Route::post('/', [InvoicePaymentController::class, 'createInvoicePayment'])
                    ->name('create')
                    ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                        UserRole::owner()->getIndex()  . ',' .
                        UserRole::accountant()->getIndex()]);
                Route::get('/analyse/paid-amount', [InvoicePaymentController::class, 'getTotalPaidAmountForInvoice']);
                Route::post('/{invoice_payment_id}/clone', [InvoicePaymentController::class, 'clone']);
                Route::put('/{invoice_payment_id}', [InvoicePaymentController::class, 'updateInvoicePayment']);
                Route::put('/{invoice_payment_id}/status', [InvoicePaymentController::class, 'updateInvoicePaymentStatus']);
                Route::get('/{invoice_payment_id}/export/{template_id}/{type}', [InvoicePaymentController::class, 'export']);
                Route::delete('/', [InvoicePaymentController::class, 'deletePaymentInvoices']);
            });
        });
    });
});
Route::namespace('Invoices')->prefix('invoices')->group(function () {
    Route::get('/', [InvoiceController::class, 'getAll']);
    Route::get('/export', [InvoiceController::class, 'exportDataTable']);
});
