<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\DateController;
use App\Http\Controllers\Api\PostController;
use App\Http\Controllers\Api\SlotController;
use App\Http\Controllers\Api\TimeController;
use App\Http\Controllers\Api\OrderController;
use App\Http\Controllers\Api\AddressController;
use App\Http\Controllers\Api\PaymentController;
use App\Http\Controllers\Api\ProductController;
use App\Http\Controllers\Api\SettingController;
use App\Http\Controllers\Api\CategoryController;
use App\Http\Controllers\Api\IngredientController;
use App\Http\Controllers\Api\ReservationController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\ReservationController as AdminReservationController;


Route::get('setting',           [SettingController::class, 'index'])->name('api.setting.index');

Route::get('products',          [ProductController::class, 'index'])->name('api.products.index');
Route::get('promoHome',         [ProductController::class, 'promoHome'])->name('api.promoHome.index');

Route::get('categories',        [CategoryController::class, 'index'])->name('api.categories.index');
Route::get('getIngredient',     [IngredientController::class, 'getIngredient'])->name('api.ingredient.getIngredient');

Route::get('post',              [PostController::class, 'index'])->name('api.post.index');
Route::get('postHome',          [PostController::class, 'postHome'])->name('api.postHome.index');

Route::get('dates',             [DateController::class, 'index'])->name('api.dates.index');
Route::get('getDays',           [DateController::class, 'getDays'])->name('api.dates.getDays');

Route::post('reservations',     [ReservationController::class, 'store'])->name('api.reservations.store');
Route::post('orders',           [OrderController::class, 'store'])->name('api.orders.store');

Route::get('/checkout',         [PaymentController::class, 'checkout'])->name('api.payment.checkout');

Route::get('/orders/status',            [AdminOrderController::class, 'status_mail'])->name('orders.status');
Route::post('/reservations/status',     [AdminReservationController::class, 'status'])->name('reservations.status');



