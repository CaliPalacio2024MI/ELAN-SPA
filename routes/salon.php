<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SalonController;

Route::middleware(['auth', 'role:master,administrador,anfitrion'])->group(function () {
    Route::resource('salon', SalonController::class);
    // Ruta explícita para el index (opcional, pero útil si la llamas directamente)
    Route::get('/salon/index', [SalonController::class, 'index'])
        ->name('salon.index');
});
