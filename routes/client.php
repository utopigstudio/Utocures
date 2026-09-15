<?php

use App\Http\Controllers\AnnouncementController;
use App\Http\Controllers\MessageController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'is_client'])->prefix('client')->name('client.')->group(function () {
    Route::get('announcement-board', [AnnouncementController::class, 'clientIndex'])->name('announcements');

    Route::prefix('messages')->name('messages.')->group(function () {
        Route::get('/', [MessageController::class, 'clientIndex'])->name('index');
        Route::post('/', [MessageController::class, 'clientStore'])->middleware('throttle:30,1')->name('store');
        Route::patch('read', [MessageController::class, 'clientRead'])->name('read');
    });
});
