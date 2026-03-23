<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\MagicLinkController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'show'])
        ->name('login');

    Route::post('login', [AuthenticatedSessionController::class, 'login']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'show'])
        ->name('password.request');

    Route::post('forgot-password', [PasswordResetLinkController::class, 'reset'])
        ->name('password.email');
    
    Route::post('forgot-magic-link', [MagicLinkController::class, 'create'])
        ->name('password.magiclink');

    Route::get('reset-password/{token}', [NewPasswordController::class, 'show'])
        ->name('password.reset');

    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');
});

Route::middleware('auth')->group(function () {
    Route::post('logout', [AuthenticatedSessionController::class, 'logout'])
        ->name('logout');
});
