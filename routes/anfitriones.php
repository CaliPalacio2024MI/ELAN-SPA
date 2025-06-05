<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AnfitrionController;

// Rutas protegidas: solo usuarios con rol master o administrador
Route::middleware(['auth', 'role:master,administrador'])->group(function () {
    // CRUD para anfitriones, sin mostrar
    Route::resource('anfitriones', AnfitrionController::class)->except(['show']);

    // Gestión de horarios específicos de anfitriones
    Route::get('/anfitriones/{anfitrion}/horario', [AnfitrionController::class, 'editHorario'])->name('anfitriones.horario.edit');
    Route::post('/anfitriones/{anfitrion}/horario', [AnfitrionController::class, 'storeHorario'])->name('anfitriones.horario.store');

    // Activar / desactivar anfitrión vía PATCH
    Route::patch('/anfitriones/{anfitrion}/toggle-estado', [AnfitrionController::class, 'toggleEstado'])->name('anfitriones.toggle-estado');
});
