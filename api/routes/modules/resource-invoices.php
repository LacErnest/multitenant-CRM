<?php

/*
|--------------------------------------------------------------------------
| Resource Invoices Routes
|--------------------------------------------------------------------------
| This file defines the routes related to resource invoices for a company.
| Resource invoices are an integral part of our application, and this file contains
| all the route definitions for managing and handling resource invoice-related
| functionality.
|
| The routes in this file encompass various aspects of resource invoices, including:
| - Managing resource invoices
| - Listing resource invoice details
| - Export resource invoice records
|
| Maintaining proper organization and adhering to clear route naming conventions
| within this file is essential for consistency throughout the project.
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Invoice\ResourceInvoiceController;
use Illuminate\Support\Facades\Route;

Route::namespace('ResourceInvoices')->prefix('resource_invoices')->group(function () {
    Route::get('/', [ResourceInvoiceController::class, 'getAll']);
    Route::get('/export', [ResourceInvoiceController::class, 'exportDataTable']);
    Route::get('/{project_id}', [ResourceInvoiceController::class, 'getAllFromProject']);
});
