<?php

/*
|--------------------------------------------------------------------------
| Salespersons Routes
|--------------------------------------------------------------------------
| This file defines the routes related to salespersons.
| Salespersons play a crucial role in our application's sales and customer
| interactions. This file contains all the route definitions for managing
| and handling salesperson-related functionality.
|
| The routes in this file encompass various aspects of salespersons, including:
| - Listing salespersons
| - Creating, updating, and deleting salesperson profiles
| - Viewing individual salesperson details
| - Managing sales teams and territories
|
| Maintaining proper organization and adhering to clear route naming conventions
| within this file is essential for consistency throughout the project.
|--------------------------------------------------------------------------
*/

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\User\UserController;
use App\Http\Controllers\Commission\CommissionPaymentLogController;
use App\Http\Controllers\Commission\CommissionController;

Route::get('/suggest/sales_persons/{value?}', [UserController::class, 'suggestSalesPersonThroughAllCompanies']);

Route::namespace('Commissions')->prefix('commissions')->group(function () {
    Route::get('/payment_log', [CommissionPaymentLogController::class, 'getPaymentLogs']);
    Route::post('/payment_log', [CommissionPaymentLogController::class, 'createCommissionPaymentLog']);
    Route::get('/get_total_open_amount', [CommissionPaymentLogController::class, 'getTotalOpenAmount']);
    Route::put('/confirm_payment/{paymentLogId}', [CommissionPaymentLogController::class, 'confirmPayment']);
    Route::post('/percentage/{orderId}/{invoiceId}/{salesPersonId}', [CommissionController::class, 'createCommissionPercentage']);
    Route::put('/percentage/{orderId}/{invoiceId}/{salesPersonId}', [CommissionController::class, 'updateCommissionPercentage']);
    Route::delete('/percentage/{orderId}/{salesPersonId}', [CommissionController::class, 'deleteCommissionPercentage']);
    Route::delete('/percentage/{percentageId}', [CommissionController::class, 'deleteCommissionPercentageById']);
    Route::post('/individual_commission_payment', [CommissionController::class, 'createIndividualCommissionPayment']);
    Route::delete('/individual_commission_payment/{orderId}/{invoiceId}/{salesPersonId}', [CommissionController::class, 'cancelIndividualCommissionPayment']);
});
