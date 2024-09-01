<?php

use App\Http\Controllers\Company\CompanyController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\Order\OrderController;
use App\Http\Controllers\Profile\ProfileController;
use App\Http\Controllers\SystemController;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Route;


/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::group(['as' => 'v1.'], function () {
    /*
    |--------------------------------------------------------------------------
    | Importing Routes from Users Authentication
    |--------------------------------------------------------------------------
    */
    Route::get('home', [HomeController::class, 'index']);
    require __DIR__ . '/modules/auth.php';
    Route::middleware(['jwt.refresh_token'])->group(function () {

        Route::post('enum', [SystemController::class, 'getEnumValues']);

        Route::middleware(['auth:api'])->group(function () {
            Route::namespace('Profile')->prefix('profile')->group(function () {
                Route::get('/2fa', [ProfileController::class, 'getTwoFactor']);
                Route::put('/2fa', [ProfileController::class, 'activateTwoFactor']);
            });
        });

        Route::middleware(['2fa'])->group(function () {
            /*
            |--------------------------------------------------------------------------
            | Importing Routes from Analytics Management
            |--------------------------------------------------------------------------
            */
            require __DIR__ . '/modules/analytics.php';
            /*
            |--------------------------------------------------------------------------
            | Importing Routes from Sales Persons
            |--------------------------------------------------------------------------
            */
            require __DIR__ . '/modules/sales-persons.php';
            Route::get('/orders-parsed', [OrderController::class, 'getAllParsed']);

            Route::group(['as' => 'companies.', 'middleware' => ['company.id', 'company.xero.binding'], 'prefix' => '/{company_id}'], function () {
                
                Route::get('/suggest/{value}', [CompanyController::class, 'suggest'])->name('suggest');
                
                Route::group(['middleware' => 'auth:api'], function () {
                    /*
                    |--------------------------------------------------------------------------
                    | Importing Routes from Users Management
                    |--------------------------------------------------------------------------
                    */
                    require __DIR__ . '/modules/users.php';
                    /*
                    |--------------------------------------------------------------------------
                    | Importing Routes from Projects
                    |--------------------------------------------------------------------------
                    */
                    require __DIR__ . '/modules/projects.php';
                    /*
                    |--------------------------------------------------------------------------
                    | Importing Routes from Resource Invoices
                    |--------------------------------------------------------------------------
                    */
                    require __DIR__ . '/modules/resource-invoices.php';
                    /*
                    |--------------------------------------------------------------------------
                    | Importing Routes from Comments Management
                    |--------------------------------------------------------------------------
                    */
                    require __DIR__ . '/modules/comments.php';
                    /*
                    |--------------------------------------------------------------------------
                    | Importing Routes from Customers and their contacts
                    |--------------------------------------------------------------------------
                    */
                    require __DIR__ . '/modules/customers.php';
                    /*
                    |--------------------------------------------------------------------------
                    | Importing Routes from Services
                    |--------------------------------------------------------------------------
                    */
                    require __DIR__ . '/modules/services.php';
                    /*
                    |--------------------------------------------------------------------------
                    | Importing Routes from Employees
                    |--------------------------------------------------------------------------
                    */
                    require __DIR__ . '/modules/employees.php';
                });
                /*
                |--------------------------------------------------------------------------
                | Importing Routes from Settings
                |--------------------------------------------------------------------------
                */
                require __DIR__ . '/modules/settings.php';
                /*
                |--------------------------------------------------------------------------
                | Importing Routes from Resources
                |--------------------------------------------------------------------------
                */
                require __DIR__ . '/modules/resources.php';
                /*
                |--------------------------------------------------------------------------
                | Importing Routes from Resources
                |--------------------------------------------------------------------------
                */
                require __DIR__ . '/modules/loans.php';
                /*
                |--------------------------------------------------------------------------
                | Importing Routes from Rents
                |--------------------------------------------------------------------------
                */
                require __DIR__ . '/modules/rents.php';
            });
            /*
            |--------------------------------------------------------------------------
            | Importing Routes from Legal Entities
            |--------------------------------------------------------------------------
            */
            require __DIR__ . '/modules/legal-entities.php';

            /*
            |--------------------------------------------------------------------------
            | Importing Routes from developers
            |--------------------------------------------------------------------------
            */
          if (env('APP_ENV') !== 'production') {
              require __DIR__ . '/modules/dev.php';
          }
        });
    });
});
