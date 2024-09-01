<?php
/*
|--------------------------------------------------------------------------
| Authentication Routes
|--------------------------------------------------------------------------
| This file defines the routes related to authentication.
| Authentication is a critical component of our application, and this file
| contains all the route definitions for managing and handling user login,
| registration, and authentication-related functionality.
|
| The routes in this file encompass various aspects of authentication, including:
| - User registration and account creation
| - User login and session management
| - Password reset and recovery
| - Authentication middleware and guards
|
| Maintaining proper organization and adhering to clear route naming conventions
| within this file is essential for consistency throughout the project.
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Auth\ForgotPasswordController;
use App\Http\Controllers\Auth\LoginController;
use App\Http\Controllers\Auth\ResetPasswordController;
use App\Http\Controllers\Auth\SetPasswordController;
use Illuminate\Support\Facades\Route;


Route::namespace('Auth')->prefix('auth')->group(function () {
    Route::post('login', [LoginController::class, 'login'])->name('login');
    Route::post('logout', [LoginController::class, 'logout']);
    Route::post('password/recover', [ForgotPasswordController::class, 'sendResetLinkEmail']);
    Route::post('password/reset', [ResetPasswordController::class, 'reset']);
    Route::post('password/set', [SetPasswordController::class, 'set'])->name('password.set');
});
