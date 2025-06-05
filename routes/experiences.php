<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ExperienceController;

// Rutas para gestión de experiencias: completo CRUD
Route::middleware(['auth', 'role:master,administrador'])->group(function () {
    Route::resource('experiences', ExperienceController::class);
});

// Activar / desactivar experiencia
Route::patch('/experiences/{experience}/toggle-estado', [ExperienceController::class, 'toggleEstado'])->name('experiences.toggle-estado');
