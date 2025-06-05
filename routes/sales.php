<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SaleController;

Route::middleware(['auth', 'role:master,administrador'])->group(function () {
    Route::resource('sales', SaleController::class);

    Route::put('/sales/{sale}', [SaleController::class, 'update'])->name('sales.update');

});

