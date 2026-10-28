<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\EmployeeClockController;
use App\Http\Controllers\EmployeeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'is_employee'])
    ->name('employee.')
    ->group(function () {
        Route::get('announcement-board', [AnnouncementController::class, 'employeeIndex'])->name('announcements');
        Route::get('schedule', [EmployeeController::class, 'schedule'])->name('schedule');
        Route::get('schedule-month', [EmployeeController::class, 'scheduleMonth'])->name('schedule-month');
        Route::get('hours-worked', [EmployeeController::class, 'hoursWorked'])->name('hours-worked');
        Route::get('works/{work}', [EmployeeController::class, 'workShow'])->name('work.show');
        Route::get('clock', [EmployeeClockController::class, 'show'])->name('clock.show');
        Route::post('clock', [EmployeeClockController::class, 'store'])->name('clock.store');
        Route::post('time-record/{work}', [EmployeeController::class, 'storeTimeRecord'])->name('time-record.store');
        Route::get('time-record/{work}', [EmployeeController::class, 'timeRecord'])->name('time-record.check');
    });
