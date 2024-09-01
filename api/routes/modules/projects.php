<?php
/*
|--------------------------------------------------------------------------
| Projects Routes
|--------------------------------------------------------------------------
| This file defines the routes related to projects for a company.
| Projects are a core component of our application, and this file contains
| all the route definitions for managing and handling projects.
|
| The routes in this file cover various aspects of projects, including:
| - Listing projects
| - Creating new projects
| - Updating existing projects
| - Deleting projects
| - Viewing individual projects
| - managing project quotes
| - managing project orders
| - managing project invoices
| - managing project purchage orders
|
| Maintaining proper organization and adhering to clear route naming
| conventions within this file is essential for consistency throughout
| the project.
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Project\ProjectController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Comment\CommentController;
use App\Http\Controllers\Payment\InvoicePaymentController;

/*
|--------------------------------------------------------------------------
| Importing Routes from Quotes
|--------------------------------------------------------------------------
*/
require __DIR__ . '/quotes.php';
/*
|--------------------------------------------------------------------------
| Importing Routes from Orders
|--------------------------------------------------------------------------
*/
require __DIR__ . '/orders.php';
/*
|--------------------------------------------------------------------------
| Importing Routes from Invoices
|--------------------------------------------------------------------------
*/
require __DIR__ . '/invoices.php';
/*
|--------------------------------------------------------------------------
| Importing Routes from Pruchase Orders
|--------------------------------------------------------------------------
*/
require __DIR__ . '/purchase-orders.php';
/*
|--------------------------------------------------------------------------
| Defining Other Routes from Projects
|--------------------------------------------------------------------------
*/
Route::namespace('Projects')->prefix('projects')->group(function () {

    Route::middleware('project.id')->prefix('/{project_id}')->group(function () {

        Route::namespace('Comment')->prefix('/{entity}/{entity_id}/comments')->group(function () {
            Route::get('/', [CommentController::class, 'getAll']);
            Route::post('/', [CommentController::class, 'create']);
            Route::put('/{comment_id}', [CommentController::class, 'update']);
            Route::delete('/{comment_id}', [CommentController::class, 'delete']);
        });

        Route::namespace('Employees')->prefix('employees')->group(function () {
            Route::get('/{employee_id}', [ProjectController::class, 'getEmployee']);
            Route::post('/{employee_id}', [ProjectController::class, 'assignEmployee']);
            Route::put('/{employee_id}', [ProjectController::class, 'updateEmployee']);
            Route::delete('/', [ProjectController::class, 'deleteEmployee']);
        });
    });

    Route::get('/{project_id}', [ProjectController::class, 'getSingle']);
    Route::get('/{project_id}/payments', [InvoicePaymentController::class, 'getAllFromProject']);
});
