<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;

Route::middleware(['auth'])->group(function () {
    Route::get('/dashboard', function () {
        $user = Auth::user();

        $spaNombre = optional($user->spa)->nombre;
        $accesos = is_array($user->accesos) ? $user->accesos : json_decode($user->accesos, true) ?? [];

        $tieneAccesosMultiples = !empty($accesos);

        if ($user->rol === 'master' || $tieneAccesosMultiples) {
            return redirect()->route('modulos');
        }

        if (in_array($user->rol, ['administrador', 'recepcionista', 'anfitrion'])) {
            session(['current_spa' => $spaNombre]);
            return redirect()->route('reservations.index');
        }        

        return redirect()->route('login');

    })->name('dashboard');

    Route::get('/modulos/modulos', function () {
        return view('modulos.modulos');
    })->name('modulos');

    
});
