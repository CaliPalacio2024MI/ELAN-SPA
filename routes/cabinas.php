<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\CabinaController;

// Rutas protegidas: master y administrador para CRUD cabinas (sin show)
Route::middleware(['auth', 'role:master,administrador'])->group(function () {
    Route::resource('cabinas', CabinaController::class)->except(['show']);
});

// Toggle estado cabina
Route::patch('/cabinas/{cabina}/toggle-estado', [CabinaController::class, 'toggleEstado'])->name('cabinas.toggle-estado');
