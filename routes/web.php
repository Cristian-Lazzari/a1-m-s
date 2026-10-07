<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Admin\DateController;
use App\Http\Controllers\Admin\PostController;
use App\Http\Controllers\Admin\OrderController;
use App\Http\Controllers\Webhooks\WaController;
use App\Http\Controllers\Whatsapp\OnboardingController;
use App\Http\Controllers\Admin\MessageController;

Route::get('/', function () {
    return view('guests/home');
});

Route::middleware(['auth', 'verified'])
    ->name('admin.')
    ->prefix('admin')
    ->group(function () {

        Route::resource('messages',    MessageController::class);
    });

Route::middleware('auth')
    ->name('admin.')
    ->prefix('admin')
    ->group(function () {
        Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
        Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
        Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    });


require __DIR__ . '/auth.php';


Route::post('/webhook/wa', [WaController::class, 'handle']);

// Collegamento del numero WhatsApp di un ristorante (link firmato dal suo gestionale).
Route::get('/whatsapp/connect', [OnboardingController::class, 'show'])->name('whatsapp.connect');
Route::post('/whatsapp/connect/complete', [OnboardingController::class, 'complete'])
    ->middleware(\Illuminate\Routing\Middleware\ThrottleRequests::class . ':10,1')
    ->name('whatsapp.connect.complete');
Route::get('/webhook/wa', [WaController::class, 'verify']); 


