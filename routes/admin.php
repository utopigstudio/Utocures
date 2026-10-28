<?php

use App\Http\Controllers\AdminNotificationController;
use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\AuditController;
use App\Http\Controllers\BudgetController;
use App\Http\Controllers\BudgetTemplateController;
use App\Http\Controllers\CharacteristicController;
use App\Http\Controllers\ClientAccessController;
use App\Http\Controllers\ClientController;
use App\Http\Controllers\ConfigurationController;
use App\Http\Controllers\ContractController;
use App\Http\Controllers\ContractTemplateController;
use App\Http\Controllers\EmployeeAssignedHourController;
use App\Http\Controllers\EmployeeController;
use App\Http\Controllers\EmployeeDocumentController;
use App\Http\Controllers\EmployeeDocumentFolderController;
use App\Http\Controllers\EmployeeStatusPeriodController;
use App\Http\Controllers\EmployeeTimeRecordController;
use App\Http\Controllers\FileController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\MessageController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\ResourceController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'is_admin'])->group(function () {

    Route::resource('clients', ClientController::class);

    Route::prefix('clients')->name('clients.')->group(function () {
        Route::post('{client}/access', [ClientAccessController::class, 'store'])->name('access.store');
        Route::put('{client}/access', [ClientAccessController::class, 'update'])->name('access.update');
        Route::post('{client}/access/invitation', [ClientAccessController::class, 'invite'])->name('access.invitation');
        Route::post('{client}/access/password-reset', [ClientAccessController::class, 'resetPassword'])->name('access.password-reset');
        Route::patch('{client}/access/activate', [ClientAccessController::class, 'activate'])->name('access.activate');
        Route::delete('{client}/access', [ClientAccessController::class, 'destroy'])->name('access.destroy');
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

    Route::get('resources/geocode', [ResourceController::class, 'geocode'])
        ->middleware('throttle:20,1')
        ->name('resources.geocode');
    Route::resource('resources', ResourceController::class)->except(['show']);

    Route::resource('announcements', AnnouncementController::class)->except(['show']);

    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [MessageController::class, 'adminIndex'])->name('index');
        Route::get('{client}', [MessageController::class, 'adminShow'])->name('show');
        Route::post('{client}', [MessageController::class, 'adminStore'])->middleware('throttle:30,1')->name('store');
        Route::patch('{client}/read', [MessageController::class, 'adminRead'])->name('read');
    });

    Route::resource('budget-templates', BudgetTemplateController::class)->except(['show']);

    Route::resource('contract-templates', ContractTemplateController::class)->except(['show']);

    Route::resource('characteristics', CharacteristicController::class)->except(['show']);

    Route::resource('audits', AuditController::class)->only(['index', 'show']);

    Route::resource('employee-time-records', EmployeeTimeRecordController::class)->only(['index', 'update']);

    Route::resource('employee-document-folders', EmployeeDocumentFolderController::class)
        ->parameters(['employee-document-folders' => 'folder'])->only(['store', 'update', 'destroy']);

    Route::prefix('employee-documents')->name('employee-documents.')->group(function () {
        Route::get('/', [EmployeeDocumentController::class, 'index'])->name('index');
        Route::post('/', [EmployeeDocumentController::class, 'store'])->name('store');
        Route::get('{file}/download', [EmployeeDocumentController::class, 'download'])->name('download');
        Route::delete('{file}', [EmployeeDocumentController::class, 'destroy'])->name('destroy');
    });

    Route::prefix('files')->name('files.')->group(function () {
        Route::post('/', [FileController::class, 'store'])->name('store');
        Route::get('{file}/download', [FileController::class, 'download'])->name('download');
        Route::patch('{file}/folder', [FileController::class, 'move'])->name('move');
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
