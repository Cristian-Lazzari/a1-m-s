<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\MessageController;



Route::post('messages', [MessageController::class, 'getNewMex'])->name('api.message.getNewMex')->withoutMiddleware([
    'throttle:api']);




