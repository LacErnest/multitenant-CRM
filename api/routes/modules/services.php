<?php

/*
|--------------------------------------------------------------------------
| Services Routes
|--------------------------------------------------------------------------
| This file defines the routes related to services for a company.
| Services are a fundamental aspect of our application, and this file
| contains all the route definitions for managing and handling service-related
| functionality.
|
| The routes in this file encompass various aspects of services, including:
| - Listing available services
| - Creating, updating, and deleting service listings
| - Viewing individual service details
| - Managing service categories and attributes
|
| Maintaining proper organization and adhering to clear route naming conventions
| within this file is essential for consistency throughout the project.
|--------------------------------------------------------------------------
*/
use App\Http\Controllers\Service\ServiceController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Preferences\TablePreferenceController;

Route::namespace('Services')->prefix('services')->group(function () {
    Route::get('/', [ServiceController::class, 'all']);
    Route::get('/export', [ServiceController::class, 'exportDataTable']);
    Route::get('/suggest/{value?}', [ServiceController::class, 'suggest']);
    Route::get('/{service_id}', [ServiceController::class, 'index']);
    Route::post('/', [ServiceController::class, 'create']);
    Route::put('/{service_id}', [ServiceController::class, 'update']);
    Route::delete('/', [ServiceController::class, 'delete']);
});
Route::get('/price_modifiers', [TablePreferenceController::class, 'getPriceModifiers']);
