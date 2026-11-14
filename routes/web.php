<?php

use App\Core\Http\Controllers\SearchController;
use App\Core\Http\Controllers\UserController;
use App\Customers\Http\Controllers\ContactController;
use App\Http\Controllers\OverviewController;
use App\Inventory\Http\Controllers\VehicleController;
use App\Sales\Http\Controllers\DealController;
use App\Sales\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

Route::redirect('/', '/login')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('search', SearchController::class)->name('search');

    Route::get('overview', OverviewController::class)->name('overview');
    Route::get('dashboard', OverviewController::class)->name('dashboard');

    Route::resource('contacts', ContactController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update']);

    Route::post('contacts/{contact}/vehicles', [VehicleController::class, 'storeForContact'])
        ->name('contacts.vehicles.store');

    Route::post('contacts/{contact}/notes', [ContactController::class, 'storeNote'])
        ->name('contacts.notes.store');

    Route::resource('vehicles', VehicleController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update']);

    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');

    Route::resource('deals', DealController::class)
        ->only(['index', 'create', 'store', 'show', 'update']);

    Route::post('deals/{deal}/sold', [DealController::class, 'sold'])
        ->name('deals.sold');

    Route::post('deals/{deal}/notes', [DealController::class, 'storeNote'])
        ->name('deals.notes.store');

    Route::resource('users', UserController::class)
        ->only(['index', 'create', 'store', 'edit', 'update']);
});

require __DIR__.'/settings.php';
