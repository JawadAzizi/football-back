<?php

use App\Http\Controllers\ReservationController;
use App\Http\Controllers\SlotController;
use App\Http\Controllers\SalonController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;




// User API Routes
Route::get('users', [UserController::class, 'index']);
Route::post('users', [UserController::class, 'store']);
Route::get('users/{user}', [UserController::class, 'show']);
Route::patch('users/{user}', [UserController::class, 'update']);
Route::delete('users/{user}', [UserController::class, 'destroy']);

// Salon API Routes
Route::get('salons', [SalonController::class, 'index']);
Route::post('salons', [SalonController::class, 'store']);
Route::get('salons/{salon}', [SalonController::class, 'show']);
Route::patch('salons/{salon}', [SalonController::class, 'update']);
Route::delete('salons/{salon}', [SalonController::class, 'destroy']);

// Slot API Routes
Route::get('slots', [SlotController::class, 'index']);
Route::post('slots', [SlotController::class, 'store']);
Route::get('slots/{slot}', [SlotController::class, 'show']);
Route::patch('slots/{slot}', [SlotController::class, 'update']);
Route::delete('slots/{slot}', [SlotController::class, 'destroy']);

// Reservation API Routes
Route::get('reservations', [ReservationController::class, 'index']);
Route::post('reservations', [ReservationController::class, 'store']);
Route::get('reservations/{reservation}', [ReservationController::class, 'show']);
Route::patch('reservations/{reservation}', [ReservationController::class, 'update']);
Route::delete('reservations/{reservation}', [ReservationController::class, 'destroy']);
