<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\UnidadController;
 

/**
 * Rutas para la gestión de Unidades Personalizadas.
 * Protegidas por el middleware de autenticación.
 */
Route::middleware(['auth'])->group(function () {
    // Ruta para mostrar el formulario de creación de unidades
    Route::get('/unidades/crear', function () {
        return view('modulos.create'); // Corregido para apuntar a la vista correcta
    })->name('unidades.create');
 
    // Ruta para procesar el formulario de creación
    Route::post('/unidades', [UnidadController::class, 'store'])->name('unidades.store');

    // Ruta para mostrar el formulario de edición
    Route::get('/unidad/{unidad}/edit', [UnidadController::class, 'edit'])->name('unidad.edit');

    // Ruta para procesar la actualización de una unidad
    Route::put('/unidad/{unidad}', [UnidadController::class, 'update'])->name('unidad.update');

    // Ruta para eliminar una unidad
    Route::delete('/unidades/{unidad}', [UnidadController::class, 'destroy'])->name('unidades.destroy');

    // Ruta para establecer la unidad seleccionada en la sesión
    Route::post('/set-unidad/{unidad}', [UnidadController::class, 'select'])->name('unidades.select');
});
