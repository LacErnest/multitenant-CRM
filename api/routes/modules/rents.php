<?php

/*
|--------------------------------------------------------------------------
| Rents Routes
|--------------------------------------------------------------------------
| This file defines the routes related to rents.
| Managing rents is a significant aspect of our application, and this file
| contains all the route definitions for managing and handling rent-related
| functionality.
|
| The routes in this file encompass various aspects of rents, including:
| - Listing available rental properties
| - Handling rental applications
| - Managing lease agreements and rent payments
| - Viewing individual rental property details
|
| Maintaining proper organization and adhering to clear route naming conventions
| within this file is essential for consistency throughout the project.
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Rent\CompanyRentController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'rents', 'as' => 'rents.'], function () {
    Route::get('/', [CompanyRentController::class, 'index'])->name('index');
    Route::get('/{rent_id}', [CompanyRentController::class, 'view'])->name('view');
    Route::post('/', [CompanyRentController::class, 'create'])->name('create');
    Route::patch('/{rent_id}', [CompanyRentController::class, 'update'])->name('update');
    Route::delete('/{rent_id}', [CompanyRentController::class, 'delete'])
        ->name('delete')
        ->middleware(['admin']);
});
