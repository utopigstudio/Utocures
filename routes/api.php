<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\IconController;
use App\Http\Controllers\Api\ClientController;
use App\Http\Controllers\Api\EmployeeController;
use App\Http\Controllers\Api\BudgetTemplateController;
use App\Http\Controllers\Api\ContractTemplateController;

Route::middleware(['auth', 'verified'])->group(function () {

    Route::get('api/icons', [IconController::class, 'index'])->name('api.icon.index');

    Route::get('api/budget-templates', [BudgetTemplateController::class, 'index'])->name('api.budget-templates.index');
    Route::get('api/contract-templates', [ContractTemplateController::class, 'index'])->name('api.contract-templates.index');

    Route::prefix('api/clients')->name('api.clients.')->group(function () {
        Route::get('/options', [ClientController::class, 'options'])->name('options');
    });

    Route::prefix('api/employees')->name('api.employees.')->group(function () {
        Route::get('/options', [EmployeeController::class, 'index'])->name('index');
    });

});