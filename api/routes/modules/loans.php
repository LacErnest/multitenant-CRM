<?php

/*
|--------------------------------------------------------------------------
| Loans Routes
|--------------------------------------------------------------------------
| This file defines the routes related to loans.
| Managing loans is a crucial component of our application, and this file
| contains all the route definitions for managing and handling loan-related
| functionality.
|
| The routes in this file encompass various aspects of loans, including:
| - Applying for loans
| - Approving and processing loan applications
| - Tracking loan repayment and history
| - Managing loan products and terms
|
| Maintaining proper organization and adhering to clear route naming conventions
| within this file is essential for consistency throughout the project.
|--------------------------------------------------------------------------
*/

use App\Http\Controllers\Loan\CompanyLoanController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'loans', 'as' => 'loans.', 'middleware' => 'admin'], function () {
    Route::get('/', [CompanyLoanController::class, 'index'])->name('index');
    Route::get('/{loan_id}', [CompanyLoanController::class, 'view'])->name('view');
    Route::post('/', [CompanyLoanController::class, 'create'])->name('create');
    Route::patch('/{loan_id}', [CompanyLoanController::class, 'update'])->name('update');
    Route::delete('/{loan_id}', [CompanyLoanController::class, 'delete'])->name('delete');
});
