<?php

use App\Core\Http\Controllers\UserController;
use App\Customers\Http\Controllers\ContactController;
use App\Inventory\Http\Controllers\VehicleController;
use App\Sales\Http\Controllers\DealController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'Welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    Route::resource('contacts', ContactController::class)
        ->only(['index', 'create', 'store', 'edit', 'update']);

    Route::resource('contacts.vehicles', VehicleController::class)
        ->only(['store']);

    Route::resource('deals', DealController::class)
        ->only(['index', 'create', 'store', 'update']);

    Route::resource('users', UserController::class)
        ->only(['index', 'create', 'store', 'edit', 'update', 'destroy']);
});

require __DIR__.'/settings.php';
