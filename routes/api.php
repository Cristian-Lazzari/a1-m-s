<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;



Route::post('messages', [MessageController::class, 'getNewMex'])->name('api.message.getNewMex');




