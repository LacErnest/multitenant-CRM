<?php

use Illuminate\Support\Facades\Route;
/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
  if (config('app.env') != 'production') {
      /*$users = \App\Models\User::get()->random(10) ?? [];
      return view('welcome', ['users' => $users]);*/
      echo phpinfo();
  }
    return view('welcome', ['users' => []]);
});
