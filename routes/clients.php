<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ClientController;

// Rutas protegidas para gestión clientes
Route::middleware(['auth', 'role:master,administrador'])->group(function () {
    Route::resource('cliente', ClientController::class)->except(['show']);

    // Exportar clientes a Excel
    Route::get('/clientes/exportar', [ClientController::class, 'export'])->name('clientes.export');

    // Importar clientes desde CSV
    Route::post('/clientes/importar-csv', [ClientController::class, 'importCSV'])->name('clientes.import.csv');
});
