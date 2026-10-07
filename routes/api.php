<?php

use App\Http\Controllers\Api\V1\AccountController;
use App\Http\Controllers\Api\V1\MessageController;
use Illuminate\Support\Facades\Route;

// API per i gestionali dei ristoranti: ognuno con la sua chiave (Bearer).
Route::prefix('v1')->middleware('tenant')->group(function () {
    Route::post('messages', [MessageController::class, 'store'])->name('api.v1.messages.store');
    Route::get('account', [AccountController::class, 'show'])->name('api.v1.account.show');
    Route::post('onboarding-links', [AccountController::class, 'onboardingLink'])->name('api.v1.onboarding-links.store');
});
