<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\SalonController;

// Archivos de rutas divididas para modularidad
require __DIR__.'/auth.php';          
require __DIR__.'/dashboard.php';     
require __DIR__.'/spas.php';          
require __DIR__.'/reservations.php';  
require __DIR__.'/anfitriones.php';     
require __DIR__.'/sales.php';     
require __DIR__.'/cabinas.php';  
require __DIR__.'/experiences.php';
require __DIR__.'/clients.php';
require __DIR__.'/boutique.php';
require __DIR__.'/gimnasio.php';
require __DIR__.'/unidad.php';
require __DIR__.'/areas.php';
/**
 * Ruta para asignar el spa actual en sesión.
 * Recibe el nombre del spa, busca su modelo y guarda id y nombre en sesión.
 */
Route::post('/set-spa/{spa}', function ($spa) {
    $spaModel = \App\Models\Spa::where('nombre', $spa)->first();
    if ($spaModel) {
        session([
            'current_spa' => strtolower($spaModel->nombre),
            'current_spa_id' => $spaModel->id
        ]);
        // Al seleccionar un spa principal, debemos limpiar cualquier tema de unidad personalizada
        // que pudiera estar en la sesión para que se apliquen los estilos CSS correctos del spa.
        session()->forget('current_unidad_theme'); // Nueva variable de tema (array).
        session()->forget('current_unidad_color'); // Variable antigua (por si acaso).
    }
    return response()->noContent();
});

/**
 * Ruta raíz: redirige a la página de login.
 */
Route::get('/', function () {
    return redirect()->route('login');
});

/**
 * Ruta protegida para la sección del salón de belleza,
 * accesible solo para usuarios autenticados.
 */
Route::middleware(['auth'])->group(function () {
    Route::get('/salon', [SalonController::class, 'index'])->name('salon.index');
});

// La ruta para la página de módulos ya existe en routes/dashboard.php, por lo que no es necesario duplicarla aquí.
// Las rutas de 'unidad' se gestionarán en routes/unidad.php para mantener el orden.
