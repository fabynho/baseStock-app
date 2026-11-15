<?php

use App\Http\Controllers\BreakageController;
use App\Http\Controllers\CustomerReturnController;
use App\Http\Controllers\DelivererController;
use App\Http\Controllers\DeliveryQueueController;
use App\Http\Controllers\FloorController;
use App\Http\Controllers\ManualAdjustmentController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\SellerController;
use App\Http\Controllers\StockMovementController;
use App\Http\Controllers\StockPositionController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
| Rotas do BaseStock. Incluir no final de routes/web.php:
|     require __DIR__.'/stock.php';
|
| Os nomes dos parâmetros ({floor}, {sale}, {user}...) são usados pelos FormRequests.
*/

Route::middleware(['auth', 'active'])->group(function () {

    // Dashboard / Posição de Estoque (a rota `dashboard` do starter kit passa a apontar para cá)
    // Route::get('dashboard', [StockPositionController::class, 'index'])->name('dashboard');
    Route::inertia('dashboard', 'Dashboard')->name('dashboard');

    // Cadastros
    Route::resource('floors', FloorController::class)->except(['show', 'destroy']);
    Route::resource('sellers', SellerController::class)->only(['index', 'store', 'update']);
    Route::resource('deliverers', DelivererController::class)->only(['index', 'store', 'update']);

    // Entradas
    Route::resource('purchases', PurchaseController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('customer-returns/create', [CustomerReturnController::class, 'create'])->name('customer-returns.create');
    Route::post('customer-returns', [CustomerReturnController::class, 'store'])->name('customer-returns.store');

    // Vendas e entregas
    Route::resource('sales', SaleController::class)->only(['index', 'create', 'store', 'show']);
    Route::get('deliveres', [DeliveryQueueController::class, 'index'])->name('deliveres.index');
    Route::post('sales/{sale}/deliver', [DeliveryQueueController::class, 'store'])->name('sales.deliver');

    // Quebras / avarias
    Route::get('breakages/create', [BreakageController::class, 'create'])->name('breakages.create');
    Route::post('breakages', [BreakageController::class, 'store'])->name('breakages.store');

    // Kardex
    Route::get('movements', [StockMovementController::class, 'index'])->name('movements.index');

    // Somente admin
    Route::middleware('admin')->group(function () {
        Route::resource('users', UserController::class)->except(['show', 'destroy']);

        Route::post('sales/{sale}/cancel', [SaleController::class, 'cancel'])->name('sales.cancel');

        Route::get('adjustments/create', [ManualAdjustmentController::class, 'create'])->name('adjustments.create');
        Route::post('adjustments', [ManualAdjustmentController::class, 'store'])->name('adjustments.store');
    });
});
