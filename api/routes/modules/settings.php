<?php

/*
|--------------------------------------------------------------------------
| Application and company Settings Routes
|--------------------------------------------------------------------------
| This file defines the routes related to application or company settings
| project. Managing application settings is essential for configuring and
| customizing our application to meet specific requirements. This file
| contains all the route definitions for managing and handling
| settings-related functionality.
|
| The routes in this file encompass various aspects of application settings,
| including:
| - Viewing and updating system settings
| - Managing user preferences and profile settings
| - Configuring application features and options
| - Customizing application appearance and behavior
|
| Maintaining proper organization and adhering to clear route naming conventions
| within this file is essential for consistency throughout the project.
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\DesignTemplate\DesignTemplateController;
use App\Http\Controllers\Preferences\TablePreferenceController;
use App\Http\Controllers\Settings\CompanyNotificationSettingController;
use App\Http\Controllers\EmailTemplate\EmailTemplateController;
use App\Http\Controllers\Settings\SmtpSettingController;
use App\Http\Controllers\TaxRate\TaxRateController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Templates\TemplateController;
use App\Http\Controllers\Settings\CompanySettingController;

Route::group(['middleware' => 'auth:api'], function () {
    Route::group(['prefix' => 'settings', 'as' => 'settings.'], function () {
        Route::get('/', [CompanySettingController::class, 'view'])
            ->name('view');
        Route::patch('/', [CompanySettingController::class, 'update'])
            ->name('update');
    });
    Route::group(['prefix' => 'settings/notifications', 'as' => 'settings.notifications.'], function () {
        Route::get('/', [CompanyNotificationSettingController::class, 'view'])
            ->name('view');
        Route::post('/', [CompanyNotificationSettingController::class, 'create'])
            ->name('create');
        Route::patch('/', [CompanyNotificationSettingController::class, 'update'])
            ->name('update');
    });

    Route::group(['prefix' => '/settings/smtp', 'as' => 'settings.smtp.'], function () {
        Route::get('/', [SmtpSettingController::class, 'index'])
            ->name('index');
        Route::get('/{id}', [SmtpSettingController::class, 'view'])
            ->name('view');
        Route::post('/', [SmtpSettingController::class, 'create'])
            ->name('create');
        Route::patch('/{id}', [SmtpSettingController::class, 'update'])
            ->name('update');
        Route::delete('/{id}', [SmtpSettingController::class, 'delete'])
            ->name('delete');
        Route::patch('/{id}/default', [SmtpSettingController::class, 'markAsDefault'])
            ->name('delete');
    });

    Route::group(['prefix' => '/settings/email-templates', 'as' => 'email-templates.'], function () {
        Route::get('/', [EmailTemplateController::class, 'index'])
            ->name('index');
        Route::get('/{id}', [EmailTemplateController::class, 'view'])
            ->name('view');
        Route::post('/', [EmailTemplateController::class, 'create'])
            ->name('create');
        Route::patch('/{id}', [EmailTemplateController::class, 'update'])
            ->name('update');
        Route::delete('/{id}', [EmailTemplateController::class, 'delete'])
            ->name('delete');
        Route::patch('/{id}/default', [EmailTemplateController::class, 'markAsDefault']);
        Route::patch('/toggle/status', [EmailTemplateController::class, 'toggleGloballyDisabledStatus'])
            ->name('status');
        Route::get('/get/status', [EmailTemplateController::class, 'getGloballyDisabledStatus'])
            ->name('status');
    });

    Route::group(['prefix' => '/settings/design-templates', 'as' => 'design-templates.'], function () {
        Route::get('/', [DesignTemplateController::class, 'index'])
            ->name('index');
        Route::get('/{id}', [DesignTemplateController::class, 'view'])
            ->name('view');
        Route::post('/', [DesignTemplateController::class, 'create'])
            ->name('create');
        Route::patch('/{id}', [DesignTemplateController::class, 'update'])
            ->name('update');
        Route::delete('/{id}', [DesignTemplateController::class, 'delete'])
            ->name('delete');
        Route::post('/uploads', [DesignTemplateController::class, 'uploadsImages'])
            ->name('uploads');
    });
    
    Route::get('/current_rate', [TaxRateController::class, 'currentRate'])->name('current.rate');

    Route::get('/templatecategories', [TemplateController::class, 'allCategories']);
    Route::post('/templatecategories', [TemplateController::class, 'createCategory']);
    Route::put('/templatecategories/{template_id}', [TemplateController::class, 'updateCategory']);
    Route::delete('/templatecategories/{template_id}', [TemplateController::class, 'deleteCategory']);

    Route::get('/templates/{template_id}', [TemplateController::class, 'all']);
    Route::get('/templates/{template_id}/view', [TemplateController::class, 'view']);
    Route::get('/templates/{template_id}/{entity}/{type}', [TemplateController::class, 'get']);
    Route::put('/templates/{template_id}/{entity}', [TemplateController::class, 'update']);
});
Route::middleware(['auth:api,resources'])->group(function () {
    Route::get('/table_preferences/{key}/{entity}', [TablePreferenceController::class, 'getTablePreferences']);
    Route::put('/table_preferences/{key}', [TablePreferenceController::class, 'updateTablePreferences']);
});
