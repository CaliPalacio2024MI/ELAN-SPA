<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BoutiqueController;

Route::middleware(['auth'])->group(function () {
    // Rutas existentes de la boutique
    Route::get('/boutique/venta', [BoutiqueController::class, 'venta'])->name('boutique.venta');
    Route::get('/boutique/inventario', [BoutiqueController::class, 'inventario'])->name('boutique.inventario');
    Route::get('/boutique/reporteo', [BoutiqueController::class, 'reporteo'])->name('boutique.reporteo');
    Route::get('/boutique/venta/historial', [BoutiqueController::class, 'venta_historial'])->name('boutique.venta.historial');
    Route::get('/boutique/inventario/historial', [BoutiqueController::class, 'inventario_historial'])->name('boutique.inventario.historial');
    Route::get('/boutique/inventario/eliminaciones', [BoutiqueController::class, 'inventario_eliminaciones'])->name('boutique.inventario.eliminaciones');
    Route::post('/boutique/articulo/guardar_venta', [BoutiqueController::class, 'guardarVenta'])->name('boutique.articulo.guardar_venta');
    // ... (otras rutas que ya tengas)
    
    // --- RUTA PARA GESTIONAR FAMILIAS ---
    Route::get('/boutique/familias', [BoutiqueController::class, 'gestionar_familias'])->name('familias.index');


    // --- NUEVAS RUTAS PARA LA CONTRASEÑA DE DESCUENTO ---
    Route::post('/boutique/venta/verificar-password', [BoutiqueController::class, 'verificarPasswordDescuento'])->name('boutique.venta.verificarPassword');
    Route::post('/boutique/venta/cambiar-password', [BoutiqueController::class, 'cambiarPasswordDescuento'])->name('boutique.venta.cambiarPassword');
});