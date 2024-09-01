<?php

/*
|--------------------------------------------------------------------------
| Purchase Orders Routes
|--------------------------------------------------------------------------
| This file defines the routes related to purchase orders for a project.
| Purchase orders are a vital part of our application, and this file contains
| all the route definitions for managing and handling purchase orders.
|
| The routes in this file encompass various aspects of purchase orders, including:
| - Listing purchase orders
| - Creating new purchase orders
| - Updating existing purchase orders
| - Deleting purchase orders
| - Viewing individual purchase orders
|
| Please ensure that this file is well-organized and that clear route naming
| conventions are followed to maintain consistency throughout the project.
|--------------------------------------------------------------------------
*/

use App\Enums\UserRole;
use App\Http\Controllers\PurchaseOrder\PurchaseOrderController;
use Illuminate\Support\Facades\Route;

Route::namespace('Projects')->prefix('projects')->group(function () {
    Route::middleware('project.id')->prefix('/{project_id}')->group(function () {
        Route::namespace('PurchaseOrders')->prefix('purchase_orders')->group(function () {
            Route::get('/', [PurchaseOrderController::class, 'getAllFromProject']);
            Route::get('/{purchase_order_id}', [PurchaseOrderController::class, 'getSingleFromProject']);
            Route::post('/', [PurchaseOrderController::class, 'createPurchaseOrder'])
                ->name('create')
                ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                    UserRole::owner()->getIndex()  . ',' .
                    UserRole::pm()->getIndex()  . ',' .
                    UserRole::accountant()->getIndex() . ',' .
                    UserRole::pm_restricted()->getIndex()]);
            Route::post('/{purchase_order_id}/items', [PurchaseOrderController::class, 'createItem']);
            Route::post('/{purchase_order_id}/price_modifiers', [PurchaseOrderController::class, 'createPriceModifier']);
            Route::post('/{purchase_order_id}/clone', [PurchaseOrderController::class, 'clone']);
            Route::put('/{purchase_order_id}', [PurchaseOrderController::class, 'updatePurchaseOrder']);
            Route::put('/{purchase_order_id}/status', [PurchaseOrderController::class, 'updatePurchaseOrderStatus']);
            Route::put('/{purchase_order_id}/items/{item_id}', [PurchaseOrderController::class, 'updateItem']);
            Route::put('/{purchase_order_id}/price_modifiers/{price_modifier_id}', [PurchaseOrderController::class, 'updatePriceModifier']);
            Route::delete('/{purchase_order_id}/items', [PurchaseOrderController::class, 'deleteItems']);
            Route::delete('/{purchase_order_id}/price_modifiers/{price_modifier_id}', [PurchaseOrderController::class, 'deletePriceModifier']);
            Route::get('/{purchase_order_id}/export/{template_id}/{type}', [PurchaseOrderController::class, 'export']);
        });
    });
});

Route::namespace('PurchaseOrders')->prefix('purchase_orders')->group(function () {
    Route::get('/', [PurchaseOrderController::class, 'getAll']);
    Route::get('/export', [PurchaseOrderController::class, 'exportDataTable']);
    Route::post('/', [PurchaseOrderController::class, 'createIndependentPurchaseOrder']);
});
