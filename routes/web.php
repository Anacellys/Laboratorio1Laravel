<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\TwoFactorAuthController;
use App\Http\Controllers\Api\RegistrationApiController;

Route::get('/', function () {
    return view('welcome');
});

Auth::routes();

Route::get('/home', [App\Http\Controllers\HomeController::class, 'index'])->name('home');

// Rutas para Autenticación de Dos Factores (2FA)
Route::prefix('two-factor')->name('two-factor.')->group(function () {
    // Rutas protegidas (requieren autenticación)
    Route::middleware('auth')->group(function () {
        // Mostrar pantalla de setup con QR code
        Route::get('/setup', [TwoFactorAuthController::class, 'setup'])
            ->name('setup')
            ->withoutMiddleware('verified');

        // Confirmar y guardar 2FA
        Route::post('/confirm', [TwoFactorAuthController::class, 'confirm'])
            ->name('confirm')
            ->withoutMiddleware('verified');

        // Deshabilitar 2FA
        Route::post('/disable', [TwoFactorAuthController::class, 'disable'])
            ->name('disable')
            ->withoutMiddleware('verified');
    });

    // Rutas sin autenticación (durante login)
    Route::middleware('web')->group(function () {
        // Mostrar pantalla de verificación durante login
        Route::get('/verify', [TwoFactorAuthController::class, 'verify'])
            ->name('verify');

        // Validar código TOTP durante login
        Route::post('/verify', [TwoFactorAuthController::class, 'validateCode'])
            ->name('validate-code');
    });
});

// Rutas API para validaciones AJAX
Route::prefix('api')->name('api.')->group(function () {
    // Validar si un email existe (para registro)
    Route::get('/check-email', [RegistrationApiController::class, 'checkEmailExists'])
        ->name('check-email');

    // Validar email en tiempo real
    Route::post('/validate-email', [RegistrationApiController::class, 'validateEmail'])
        ->name('validate-email');
});
