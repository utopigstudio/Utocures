<?php

use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\BudgetTemplateController;
use App\Http\Controllers\CharacteristicController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractTemplateController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\EmployeeAssignedHourController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeStatusPeriodController;
use App\Http\Controllers\EmployeeTimeRecordController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'is_admin'])->group(function () {

    Route::get('dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('clients', ClientController::class);

    Route::prefix('clients')->name('clients.')->group(function () {
        Route::get('{client}/contracts', [ClientController::class, 'contracts'])->name('contracts');
        Route::get('{client}/budgets', [ClientController::class, 'budgets'])->name('budgets');
        Route::get('{client}/invoices', [ClientController::class, 'invoices'])->name('invoices');
        Route::get('{client}/notes', [ClientController::class, 'notes'])->name('notes');
        Route::post('{client}/assigned-hours-templates', [ClientController::class, 'storeAssignedHourTemplate'])->name('assigned-hours-templates.store');
        Route::put('{client}/assigned-hours-templates/{template}', [ClientController::class, 'updateAssignedHourTemplate'])->name('assigned-hours-templates.update');
        Route::delete('{client}/assigned-hours-templates/{template}', [ClientController::class, 'destroyAssignedHourTemplate'])->name('assigned-hours-templates.destroy');
    });

    Route::resource('employees', EmployeeController::class);

    Route::prefix('employees')->name('employees.')->group(function () {
        Route::post('{employee}/assigned-hours', [EmployeeController::class, 'storeAssignedHour'])->name('assigned-hours.store');
        Route::put('{employee}/assigned-hours/{hour}', [EmployeeController::class, 'editAssignedHour'])->name('assigned-hours.update');
        Route::delete('{employee}/assigned-hours/{hour}', [EmployeeController::class, 'destroyAssignedHour'])->name('assigned-hours.destroy');
        Route::put('{employee}/assigned-hour-events/{assignedHour}', [EmployeeAssignedHourController::class, 'update'])->name('assigned-hour-events.update');
        Route::delete('{employee}/assigned-hour-events/{assignedHour}', [EmployeeAssignedHourController::class, 'destroy'])->name('assigned-hour-events.destroy');
        Route::post('{employee}/status-periods', [EmployeeStatusPeriodController::class, 'store'])->name('status-periods.store');
        Route::put('{employee}/status-periods/{statusPeriod}', [EmployeeStatusPeriodController::class, 'update'])->name('status-periods.update');
        Route::delete('{employee}/status-periods/{statusPeriod}', [EmployeeStatusPeriodController::class, 'destroy'])->name('status-periods.destroy');
    });

    Route::resource('users', UserController::class)->except(['show']);

    Route::resource('contracts', ContractController::class);
    Route::get('contracts/{contract}/download-pdf', [ContractController::class, 'downloadPdf'])->name('contracts.download-pdf');
    Route::get('contracts/{contract}/send-email', [ContractController::class, 'sendByEmail'])->name('contracts.send-email');

    Route::resource('budgets', BudgetController::class);
    Route::get('budgets/{budget}/download-pdf', [BudgetController::class, 'downloadPdf'])->name('budgets.download-pdf');
    Route::get('budgets/{budget}/send-email', [BudgetController::class, 'sendByEmail'])->name('budgets.send-email');

    Route::resource('invoices', InvoiceController::class)->except(['show']);

    Route::resource('services', ServiceController::class)->except(['show']);

    Route::resource('announcements', AnnouncementController::class)->except(['show']);

    Route::resource('budget-templates', BudgetTemplateController::class)->except(['show']);

    Route::resource('contract-templates', ContractTemplateController::class)->except(['show']);

    Route::resource('characteristics', CharacteristicController::class)->except(['show']);

    Route::resource('audits', AuditController::class)->only(['index', 'show']);

    Route::resource('employee-time-records', EmployeeTimeRecordController::class)->only(['index', 'update']);

    Route::prefix('files')->name('files.')->group(function () {
        Route::post('/', [FileController::class, 'store'])->name('store');
        Route::get('{file}/download', [FileController::class, 'download'])->name('download');
        Route::delete('{file}', [FileController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('notes')->name('notes.')->group(function () {
        Route::delete('{note}', [NoteController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('admin-notifications')->name('admin-notifications.')->group(function () {
        Route::patch('mark-all-read', [AdminNotificationController::class, 'markAllAsRead'])->name('mark-all-read');
        Route::patch('{adminNotification}', [AdminNotificationController::class, 'update'])->name('update');
    });

    Route::resource('configuration', ConfigurationController::class)->only(['index', 'update']);
});
