<?php

/*
|--------------------------------------------------------------------------
| Dev Management Routes
|--------------------------------------------------------------------------
| This file defines the routes related to customer management, including
| customer contacts. Managing customers and their
| contacts is a fundamental part of our application, and this file contains
| all the route definitions for this functionality.

| It is crucial to maintain proper organization and adhere to clear route
| naming conventions within this file for consistency throughout the project.
|--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Artisan;

Route::get('/artisan/commissions/{n}', function ($n = 20) {
    Artisan::call('dev:generate_commissions', ['--n' => $n, '--all' => true]);
});
