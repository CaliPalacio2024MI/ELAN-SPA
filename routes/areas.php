<?php

use App\Http\Controllers\AreasController;

// --- Ruta para el Gestor de Áreas ---
Route::middleware(['auth', 'role:master,administrador'])->group(function () {
    Route::get('/areas', [AreasController::class, 'index'])->name('areas.index');
    Route::post('/areas', [AreasController::class, 'store'])->name('areas.store');
    Route::get('/areas/{departamento}/edit', [AreasController::class, 'edit'])->name('areas.edit');
    Route::put('/areas/{departamento}', [AreasController::class, 'update'])->name('areas.update');
    Route::delete('/areas/{departamento}', [AreasController::class, 'destroy'])->name('areas.destroy');
    Route::patch('/areas/{departamento}/toggle', [AreasController::class, 'toggle'])->name('areas.toggle');
});
Route::get('/debug', function () {
    return [
        'current_spa' => session('current_spa'),
        'current_spa_id' => session('current_spa_id'),
    ];
});