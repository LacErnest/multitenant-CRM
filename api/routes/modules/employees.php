<?php

/*
|--------------------------------------------------------------------------
| Employee Management Routes
|--------------------------------------------------------------------------
| This file defines the routes related to employee management fro a company.
| Employee management is a critical aspect of our application, and this file contains
| all the route definitions for managing and handling employee-related functionality.
|
| The routes in this file encompass various aspects of employee management, including:
| - Listing employees
| - Creating, updating, and deleting employee profiles
| - Viewing individual employee details
| - Managing employee roles and permissions
|
| Maintaining proper organization and adhering to clear route naming conventions
| within this file is essential for consistency throughout the project.
|--------------------------------------------------------------------------
*/

use App\Enums\UserRole;
use App\Http\Controllers\Employee\EmployeeController;
use App\Http\Controllers\Employee\EmployeeHistoryController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'employees', 'as' => 'employees', 'middleware' => 'not.sales'], function () {
    Route::get('/', [EmployeeController::class, 'all'])->name('all');
    Route::get('/active', [EmployeeController::class, 'getActiveEmployees']);
    Route::get('/export', [EmployeeController::class, 'exportDataTable']);
    Route::get('/suggest/{value?}', [EmployeeController::class, 'suggest'])->name('suggest');
    Route::get('/suggest_role/{value?}', [EmployeeController::class, 'suggestRole'])->name('suggest.role');
    Route::get('/{employee_id}', [EmployeeController::class, 'index'])->name('index');
    Route::get('/{employee_id}/histories', [EmployeeHistoryController::class, 'getEmployeeHistories']);
    Route::post('/', [EmployeeController::class, 'create'])
        ->name('create')
        ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
            UserRole::owner()->getIndex()  . ',' .
            UserRole::pm()->getIndex()  . ',' .
            UserRole::hr()->getIndex()  . ',' .
            UserRole::accountant()->getIndex()]);
    Route::post('/edit_hours', [EmployeeController::class, 'editProjectHours']);
    Route::put('/{employee_id}', [EmployeeController::class, 'update'])
        ->name('update')
        ->middleware(['access:' . UserRole::admin()->getIndex() . ',' .
            UserRole::owner()->getIndex()  . ',' .
            UserRole::pm()->getIndex()  . ',' .
            UserRole::hr()->getIndex()  . ',' .
            UserRole::accountant()->getIndex()]);
    Route::post('/import', [EmployeeController::class, 'uploadImportFile'])->name('upload.import.file');
    Route::post('/import/finalize', [EmployeeController::class, 'finalizeImportFile'])->name('finalize.import.file');
    Route::post('/{employee_id}/histories', [EmployeeHistoryController::class, 'createEmployeeHistory']);
    Route::post('/{employee_id}/upload', [EmployeeController::class, 'fileUpload']);
    Route::put('/{employee_id}/histories/{history_id}', [EmployeeHistoryController::class, 'updateEmployeeHistory']);
    Route::delete('/delete_hours', [EmployeeController::class, 'deleteProjectHours']);
    Route::delete('/{employee_id}/histories/{history_id}', [EmployeeHistoryController::class, 'deleteEmployeeHistory']);
    Route::get('/{employee_id}/download/file/{file_id}', [EmployeeController::class, 'fileDownload']);
    Route::get('/{employee_id}/export/{type}/{extension}', [EmployeeController::class, 'export'])->name('export');
    Route::delete('/{employee_id}/delete_file/{file_id}', [EmployeeController::class, 'fileDelete']);
});
