<?php

/*
|--------------------------------------------------------------------------
| Quotes Routes
|--------------------------------------------------------------------------
| This file defines the routes related to quotes for a project.
| Quotes are an essential part of our application, and this file contains
| all the route definitions for managing and displaying quotes.
|
| Routes in this file include but are not limited to:
| - Listing quotes
| - Creating new quotes
| - Updating existing quotes
| - Deleting quotes
| - Viewing individual quotes
| - cloning individual quote
| - managing quote price modifiers
|
| Please make sure to keep this file organized and maintain clear route
| naming conventions for consistency throughout the project.
|--------------------------------------------------------------------------
*/

use App\Enums\UserRole;
use App\Http\Controllers\Quote\QuoteController;
use Illuminate\Support\Facades\Route;

Route::namespace('Projects')->prefix('projects')->as('projects.')->group(function () {
    Route::middleware('project.id')->prefix('/{project_id}')->group(function () {
        Route::namespace('Quotes')->prefix('quotes')->as('quotes.')->group(function () {
            Route::get('/', [QuoteController::class, 'getAllFromProject']);
            Route::get('/{quote_id}', [QuoteController::class, 'singleFromProject'])->name('show');
            Route::post('/', [QuoteController::class, 'createQuoteForProject'])
                ->name('create')
                ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                    UserRole::owner()->getIndex()  . ',' .
                    UserRole::sales()->getIndex()  . ',' .
                    UserRole::accountant()->getIndex()]);
            Route::post('/{quote_id}/items', [QuoteController::class, 'createItem'])->name('items.create');
            Route::post('/{quote_id}/price_modifiers', [QuoteController::class, 'createPriceModifier']);
            Route::post('/{quote_id}/clone', [QuoteController::class, 'clone']);
            Route::post('/{quote_id}/document', [QuoteController::class, 'addDocument']);
            Route::put('/{quote_id}', [QuoteController::class, 'updateQuote'])->name('update');
            Route::put('/{quote_id}/status', [QuoteController::class, 'updateQuoteStatus']);
            Route::put('/{quote_id}/items/{item_id}', [QuoteController::class, 'updateItem']);
            Route::put('/{quote_id}/price_modifiers/{price_modifier_id}', [QuoteController::class, 'updatePriceModifier']);
            Route::delete('/{quote_id}/items', [QuoteController::class, 'deleteItems']);
            Route::delete('/{quote_id}/document', [QuoteController::class, 'deleteDocument']);
            Route::delete('/{quote_id}/price_modifiers/{price_modifier_id}', [QuoteController::class, 'deletePriceModifier']);
            Route::get('/{quote_id}/export/{template_id}/{type}', [QuoteController::class, 'export']);
        });
    });
});
Route::namespace('Quotes')->prefix('quotes')->as('quotes.')->group(function () {
    Route::get('/', [QuoteController::class, 'all']);
    Route::get('/export', [QuoteController::class, 'exportDataTable']);
    Route::post('/', [QuoteController::class, 'createQuote'])
        ->name('create')
        ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
            UserRole::owner()->getIndex()  . ',' .
            UserRole::sales()->getIndex()  . ',' .
            UserRole::accountant()->getIndex()]);
});
