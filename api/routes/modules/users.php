<?php
/*
|--------------------------------------------------------------------------
| User Management Routes
|--------------------------------------------------------------------------
| This file defines the routes related to user management.
| User management is a critical component of our application, and this file
| contains all the route definitions for managing and handling user-related
| functionality.
|
| The routes in this file encompass various aspects of user management, including:
| - User registration and authentication
| - User profile management
| - Admin-level user management
| - User roles and permissions
|
| It is essential to maintain proper organization and adhere to clear route
| naming conventions within this file for consistency throughout the project.
|--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\UserController;

Route::group(['namespace' => 'Users', 'prefix' => 'users', 'as' => 'users.'], function () {
    Route::put('/{user_id}/status', [UserController::class, 'toggleStatus']);
    Route::get('/', [UserController::class, 'getAll']);
    Route::get('/mail_preferences', [UserController::class, 'getMailPreferences']);
    Route::get('/suggest/{value?}', [UserController::class, 'suggest']);
    Route::get('/pm_suggest/{value?}', [UserController::class, 'suggestProjectManager']);
    Route::get('/{user_id}', [UserController::class, 'getSingle']);
    Route::post('/', [UserController::class, 'createUser'])->name('create');
    Route::post('/{user_id}/resend_link', [UserController::class, 'resendLink']);
    Route::put('/mail_preferences', [UserController::class, 'updateMailPreferences']);
    Route::put('/{user_id}', [UserController::class, 'updateUser']);
    Route::delete('/', [UserController::class, 'deleteUser']);
});
