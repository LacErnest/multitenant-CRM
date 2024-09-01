<?php

/*
|--------------------------------------------------------------------------
| Analytics Routes
|--------------------------------------------------------------------------
| This file defines the routes related to analytics.
| Analytics play a crucial role in understanding and improving the
| performance of our application. This file contains all the route
| definitions for managing and handling analytics-related functionality.
|
| The routes in this file encompass various aspects of analytics, including:
| - Generating and viewing reports
| - Collecting and processing data
| - Configuring analytics settings
| - Integrating with third-party analytics services
|
| Maintaining proper organization and adhering to clear route naming conventions
| within this file is essential for consistency throughout the project.
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Analytics\AnalyticsController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth:api'])->group(function () {
    Route::namespace('Analytics')->prefix('dashboard')->group(function () {
        Route::get('/', [AnalyticsController::class, 'get']);
        Route::get('/{entity}/summary', [AnalyticsController::class, 'summary']);
        Route::get('/commission-summary', [AnalyticsController::class, 'commissionSummary']);
    });
});

Route::group(['as' => 'companies.','middleware' => ['company.id', 'company.xero.binding'],'prefix' => '/{company_id}'], function () {
    Route::group(['middleware' => 'auth:api'], function () {
        Route::namespace('Analytics')->prefix('dashboard')->group(function () {
            Route::get('/', [AnalyticsController::class, 'getCompany']);
            Route::get('/earn_out_summary', [AnalyticsController::class, 'earnoutSummary']);
            Route::get('/earn_out_prospection', [AnalyticsController::class, 'earnOutProspection']);
            Route::get('/earn_out_status', [AnalyticsController::class, 'getStatus']);
            Route::get('/earn_out_summary/export', [AnalyticsController::class, 'exportSummary']);
            Route::get('/{entity}/summary', [AnalyticsController::class, 'summaryCompany']);
            Route::post('/earn_out_status/approve', [AnalyticsController::class, 'setAsApproved']);
            Route::patch('/earn_out_status/confirm', [AnalyticsController::class, 'setAsConfirmed']);
            Route::patch('/earn_out_status/received', [AnalyticsController::class, 'setAsReceived']);
        });
    });
});
