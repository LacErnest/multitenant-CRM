<?php

/*
|--------------------------------------------------------------------------
| Legal Entity Management Routes
|--------------------------------------------------------------------------
| This file defines the routes related to legal entity management for a
| company. Managing legal entities is a critical component of our
| application, and this file contains all the route definitions for
| managing and handling legal entity-related functionality.
|
| The routes in this file encompass various aspects of legal entity management,
| including:
| - Listing legal entities
| - Creating, updating, and deleting legal entity profiles
| - Viewing individual legal entity details
| - Managing legal entity ownership and relationships
|
| Maintaining proper organization and adhering to clear route naming conventions
| within this file is essential for consistency throughout the project.
|--------------------------------------------------------------------------
*/

use App\Enums\UserRole;
use App\Http\Controllers\LegalEntity\CompanyLegalEntityController;
use App\Http\Controllers\LegalEntity\LegalEntityController;
use App\Http\Controllers\Settings\LegalEntityNotificationSettingController;
use App\Http\Controllers\Settings\LegalEntitySettingController;
use App\Http\Controllers\TaxRate\TaxRateController;
use App\Http\Controllers\Templates\LegalEntityTemplateController;
use App\Http\Controllers\Xero\XeroController;
use Illuminate\Support\Facades\Route;

Route::group(['as' => 'companies.', 'middleware' => ['company.id', 'company.xero.binding'], 'prefix' => '/{company_id}'], function () {

    Route::group(['prefix' => 'legal_entities', 'as' => 'legal_entities.'], function () {
        Route::get('/', [LegalEntityController::class, 'index'])
            ->name('index')
            ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                UserRole::accountant()->getIndex()]);
        Route::get('/{legal_entity_id}', [LegalEntityController::class, 'view'])
            ->name('view')
            ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                UserRole::accountant()->getIndex()]);
        Route::get('/{legal_entity_id}/xero', [LegalEntityController::class, 'xeroLinked'])
            ->name('xero.linked')
            ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                UserRole::accountant()->getIndex()]);
        Route::post('/', [LegalEntityController::class, 'create'])
            ->name('create')
            ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                UserRole::accountant()->getIndex()]);
        Route::patch('/{legal_entity_id}', [LegalEntityController::class, 'update'])
            ->name('update')
            ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                UserRole::accountant()->getIndex()]);
        Route::delete('/{legal_entity_id}', [LegalEntityController::class, 'delete'])
            ->name('delete')
            ->middleware(['access:' . UserRole::admin()->getIndex()]);
    });
    Route::group(['prefix' => 'company_legal_entities', 'as' => 'company_legal_entities.'], function () {
        Route::get('/', [CompanyLegalEntityController::class, 'index'])->name('index');
        Route::get('/suggest/{value}', [CompanyLegalEntityController::class, 'suggest'])
            ->name('suggest')
            ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                UserRole::accountant()->getIndex()]);
        Route::post('/{legal_entity_id}', [CompanyLegalEntityController::class, 'link'])
            ->name('link')
            ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                UserRole::accountant()->getIndex()]);
        Route::patch('/{legal_entity_id}/default', [CompanyLegalEntityController::class, 'setDefault'])
            ->name('set.default')
            ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                UserRole::accountant()->getIndex()]);
        Route::patch('/{legal_entity_id}/local', [CompanyLegalEntityController::class, 'setLocal'])
            ->name('set.local')
            ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                UserRole::accountant()->getIndex()]);
        Route::delete('/{legal_entity_id}', [CompanyLegalEntityController::class, 'unlink'])
            ->name('unlink')
            ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                UserRole::accountant()->getIndex()]);
    });
});
Route::group(['as' => 'legal_entities.', 'middleware' => ['legal.entity.id'], 'prefix' => '/legal_entities/{legal_entity_id}'], function () {

    Route::group(['middleware' => 'auth:api'], function () {
        Route::group(['prefix'     => 'rates', 'as' => 'rates.'], function () {
            Route::get('/', [TaxRateController::class, 'index'])
                ->name('index')
                ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                    UserRole::accountant()->getIndex()]);
            Route::get('/current_rate', [TaxRateController::class, 'currentRate'])
                ->name('current.rate');
            Route::get('/{tax_rate_id}', [TaxRateController::class, 'view'])
                ->name('view')
                ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                    UserRole::accountant()->getIndex()]);
            Route::post('/', [TaxRateController::class, 'create'])
                ->name('create')
                ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                    UserRole::accountant()->getIndex()]);
            Route::patch('/{tax_rate_id}', [TaxRateController::class, 'update'])
                ->name('update')
                ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                    UserRole::accountant()->getIndex()]);
            Route::delete('/{tax_rate_id}', [TaxRateController::class, 'delete'])
                ->name('delete')
                ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                    UserRole::accountant()->getIndex()]);
        });

        Route::group(['prefix'     => 'xero', 'as' => 'xero.'], function () {
            Route::get('/tax_rates', [XeroController::class, 'taxRates']);
            Route::post('/auth', [XeroController::class, 'auth']);
        });

        Route::group(['prefix' => 'settings', 'as' => 'settings.'], function () {
            Route::get('/', [LegalEntitySettingController::class, 'view'])
                ->name('view')
                ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                    UserRole::accountant()->getIndex()]);
            Route::patch('/', [LegalEntitySettingController::class, 'update'])
                ->name('update')
                ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                    UserRole::accountant()->getIndex()]);
        });

        Route::group(['prefix' => 'notifications/settings', 'as' => 'notifications.settings.'], function () {
            Route::get('/', [LegalEntityNotificationSettingController::class, 'view'])
                ->name('view')
                ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                    UserRole::accountant()->getIndex()]);
            Route::patch('/', [LegalEntityNotificationSettingController::class, 'update'])
                ->name('update')
                ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                    UserRole::accountant()->getIndex()]);

            Route::post('/', [LegalEntityNotificationSettingController::class, 'create'])
                ->name('create')
                ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                    UserRole::accountant()->getIndex()]);
        });

        Route::group([
            'prefix'     => 'templates',
            'as'         => 'templates.',
        ], function () {
            Route::get('/', [LegalEntityTemplateController::class, 'index'])
                ->name('index')
                ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                    UserRole::accountant()->getIndex()]);
            Route::get('/{entity}/{type}', [LegalEntityTemplateController::class, 'download'])
                ->name('download')
                ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                    UserRole::accountant()->getIndex()]);
            Route::patch('/{entity}', [LegalEntityTemplateController::class, 'update'])
                ->name('update')
                ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
                    UserRole::accountant()->getIndex()]);
        });
    });
});
