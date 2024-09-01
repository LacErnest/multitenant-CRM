<?php

/*
|--------------------------------------------------------------------------
| Orders Routes
|--------------------------------------------------------------------------
| This file defines the routes related to orders for a project.
| Orders are a fundamental aspect of our application, and this file
| contains all the route definitions for managing and handling orders.
|
| Routes in this file encompass various aspects of orders, including:
| - Listing orders
| - Creating new orders
| - Updating existing orders
| - Deleting orders
| - Viewing individual orders
|
| It's crucial to maintain proper organization and adhere to clear route
| naming conventions within this file to ensure consistency throughout
| the project.
|--------------------------------------------------------------------------
*/

use App\Enums\UserRole;
use App\Http\Controllers\Order\OrderController;
use Illuminate\Support\Facades\Route;

Route::namespace('Projects')->prefix('projects')->group(function () {
    Route::middleware('project.id')->prefix('/{project_id}')->group(function () {
        Route::namespace('Orders')->prefix('orders')->group(function () {
            Route::get('/{order_id}', [OrderController::class, 'singleFromProject']);
            Route::post('/', [OrderController::class, 'createOrder'])
                ->name('create')
                ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                    UserRole::owner()->getIndex()  . ',' .
                    UserRole::pm()->getIndex()  . ',' .
                    UserRole::accountant()->getIndex()]);
            Route::post('/{order_id}/items', [OrderController::class, 'createItem']);
            Route::post('/{order_id}/price_modifiers', [OrderController::class, 'createPriceModifier']);
            Route::post('/{order_id}/document', [OrderController::class, 'addDocument']);
            Route::put('/{order_id}', [OrderController::class, 'updateOrder']);
            Route::put('/{order_id}/status', [OrderController::class, 'updateOrderStatus']);
            Route::put('/{order_id}/items/{item_id}', [OrderController::class, 'updateItem']);
            Route::put('/{order_id}/price_modifiers/{price_modifier_id}', [OrderController::class, 'updatePriceModifier']);
            Route::delete('/{order_id}/items', [OrderController::class, 'deleteItems']);
            Route::delete('/{order_id}/document', [OrderController::class, 'deleteDocument']);
            Route::delete('/{order_id}/price_modifiers/{price_modifier_id}', [OrderController::class, 'deletePriceModifier']);
            Route::get('/{order_id}/export/{template_id}/{type}', [OrderController::class, 'export']);
            Route::get('/{order_id}/export/report', [OrderController::class, 'exportReport'])
              ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                  UserRole::owner()->getIndex()  . ',' .
                  UserRole::admin()->getIndex()  . ',' .
                  UserRole::super_admin()->getIndex()  . ',' .
                  UserRole::accountant()->getIndex()]);
            Route::put('/{order_id}/share', [OrderController::class, 'shareOrder']);
            Route::get('/{order_id}/check-sharing-permissions', [OrderController::class, 'checkSharingPermissions']);
        });
    });
});
Route::group(['namespace' => 'Orders', 'prefix' => 'orders', 'as' => 'orders.'], function () {
    Route::get('/', [OrderController::class, 'all']);
    Route::get('/export', [OrderController::class, 'exportDataTable']);
    Route::get('/suggest/{value?}', [OrderController::class, 'suggest']);
});
