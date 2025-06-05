<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\SpaController;

// Gestión de spas solo para rol master
Route::middleware(['auth', 'role:master'])->group(function () {
    Route::resource('spas', SpaController::class);
});
