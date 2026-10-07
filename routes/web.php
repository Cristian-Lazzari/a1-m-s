<?php

use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Webhooks\WhatsappWebhookController;
use App\Http\Controllers\Whatsapp\OnboardingController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('guests/home');
});

require __DIR__ . '/auth.php';

Route::get('/admin', DashboardController::class)->middleware('auth')->name('admin.dashboard');

// Webhook di Meta: accettato solo con firma valida (X-Hub-Signature-256).
Route::get('/webhook/whatsapp', [WhatsappWebhookController::class, 'verify']);
Route::post('/webhook/whatsapp', [WhatsappWebhookController::class, 'handle'])->middleware('meta.signature');

// Collegamento del numero WhatsApp di un ristorante (link monouso dal suo gestionale).
Route::get('/whatsapp/connect', [OnboardingController::class, 'show'])->name('whatsapp.connect');
Route::post('/whatsapp/connect/complete', [OnboardingController::class, 'complete'])
    ->middleware('throttle:10,1')
    ->name('whatsapp.connect.complete');
