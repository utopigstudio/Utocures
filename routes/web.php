<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\PushSubscriptionController;
use App\Http\Controllers\SetupController;
use App\Http\Controllers\UserLocaleController;

Route::resource('/setup', SetupController::class)->only(['index', 'store']);

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::middleware('auth')->get('dashboard', [DashboardController::class, 'index'])
    ->name('dashboard');

Route::middleware('auth')->get('help/{article?}', [\App\Http\Controllers\HelpController::class, 'index'])
    ->name('help.index');

Route::middleware('auth')->get('help-content/{article}', [\App\Http\Controllers\HelpController::class, 'content'])
    ->name('help.content');

Route::middleware('auth')->get('changelog-content', [\App\Http\Controllers\ChangelogController::class, 'content'])
    ->name('changelog.content');

Route::middleware('auth')->patch('changelog/seen', [\App\Http\Controllers\ChangelogController::class, 'markSeen'])
    ->name('changelog.seen');

Route::middleware('auth')->patch('/user/locale', [UserLocaleController::class, 'update'])
    ->name('user.locale.update');

Route::middleware('auth')->group(function () {
    Route::post('/push-subscriptions', [PushSubscriptionController::class, 'store'])
        ->name('push-subscriptions.store');
    Route::delete('/push-subscriptions', [PushSubscriptionController::class, 'destroy'])
        ->name('push-subscriptions.destroy');
});

Route::middleware(['auth'])->prefix('notes')->name('notes.')->group(function () {
    Route::post('/', [NoteController::class, 'store'])->name('store');
    Route::put('{note}', [NoteController::class, 'update'])->name('update');
});

require __DIR__.'/admin.php';
require __DIR__.'/employee.php';
require __DIR__.'/client.php';
require __DIR__.'/auth.php';
require __DIR__.'/api.php';
