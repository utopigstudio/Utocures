<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\NoteController;
use App\Http\Controllers\SetupController;

Route::resource('/setup', SetupController::class)->only(['index', 'store']);

Route::get('/', function () {
    return redirect()->route('dashboard');
})->name('home');

Route::prefix('notes')->name('notes.')->group(function () {
    Route::post('/', [NoteController::class, 'store'])->name('store');
    Route::put('{note}', [NoteController::class, 'update'])->name('update');
});

require __DIR__.'/admin.php';
require __DIR__.'/employee.php';
require __DIR__.'/auth.php';
require __DIR__.'/api.php';
