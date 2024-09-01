<?php

/*
|--------------------------------------------------------------------------
| Customer Management Routes (Including Contacts)
|--------------------------------------------------------------------------
| This file defines the routes related to customer management, including
| customer contacts. Managing customers and their
| contacts is a fundamental part of our application, and this file contains
| all the route definitions for this functionality.
|
| The routes in this file encompass various aspects of customer management, such as:
| - Listing customers
| - Creating, updating, and deleting customers
| - Viewing individual customer profiles
| - Managing customer contacts
|
| It is crucial to maintain proper organization and adhere to clear route
| naming conventions within this file for consistency throughout the project.
|--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Customers\CustomerController;
use App\Http\Controllers\Customers\ContactController as CustomerContactController;
use App\Http\Controllers\Contact\ContactController;

Route::namespace('Customers')->prefix('customers')->group(function () {
    Route::get('/', [CustomerController::class, 'all']);
    Route::get('/export', [CustomerController::class, 'exportDataTable']);
    Route::get('/suggest/{value?}', [CustomerController::class, 'suggest']);
    Route::get('/currency/{customer_id}', [CustomerController::class, 'getCurrency']);
    Route::get('/{customer_id}', [CustomerController::class, 'index']);
    Route::post('/', [CustomerController::class, 'create']);
    Route::put('/{customer_id}', [CustomerController::class, 'update']);
    Route::post('/import', [CustomerController::class, 'uploadImportFile']);
    Route::post('/import/finalize', [CustomerController::class, 'finalizeImportFile']);
    Route::get('/{customer_id}/export/{type}/{extension}', [CustomerController::class, 'export']);

    Route::namespace('Contacts')->prefix('/{customer}/contacts')->group(function () {
        Route::get('/{contact}', [CustomerContactController::class, 'index']);
        Route::post('/', [CustomerContactController::class, 'create']);
        Route::put('/{contact}', [CustomerContactController::class, 'update']);
        Route::delete('/', [CustomerContactController::class, 'delete']);
    });
});

Route::namespace('Contacts')->prefix('/contacts')->group(function () {
    Route::get('/', [ContactController::class, 'all']);
    Route::get('/export', [ContactController::class, 'exportDataTable']);
    Route::get('/suggest/{value?}', [ContactController::class, 'suggest']);
});
