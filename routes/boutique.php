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
    Route::get('/boutique/inventario/excel', [BoutiqueController::class, 'exportarExcel'])->name('boutique.inventario.excel');
    Route::get('/boutique/inventario/eliminaciones/excel', [BoutiqueController::class, 'exportarEliminacionesExcel'])->name('boutique.inventario.eliminaciones.excel');
    Route::post('/boutique/articulo/guardar_venta', [BoutiqueController::class, 'guardarVenta'])->name('boutique.articulo.guardar_venta');
    // ... (otras rutas que ya tengas)
    
    // --- RUTA PARA GESTIONAR FAMILIAS ---
    Route::get('/boutique/familias', [BoutiqueController::class, 'gestionar_familias'])->name('familias.index');

    // --- NUEVAS RUTAS PARA LA CONTRASEÑA DE DESCUENTO ---
    Route::post('/boutique/venta/verificar-password', [BoutiqueController::class, 'verificarPasswordDescuento'])->name('boutique.venta.verificarPassword');
    Route::post('/boutique/venta/cambiar-password', [BoutiqueController::class, 'cambiarPasswordDescuento'])->name('boutique.venta.cambiarPassword');

    // --- RUTAS DE INVENTARIO (AJAX) ---
    Route::post('/boutique/inventario/nuevo_articulo', [BoutiqueController::class, 'nuevoArticulo'])->name('boutique.inventario.nuevo_articulo');
    Route::post('/boutique/inventario/nueva_compra', [BoutiqueController::class, 'nuevaCompra'])->name('boutique.inventario.nueva_compra');
    Route::post('/boutique/inventario/editar_compra', [BoutiqueController::class, 'editarCompra'])->name('boutique.inventario.editar_compra');
    Route::post('/boutique/inventario/eliminar_compra', [BoutiqueController::class, 'eliminarCompra'])->name('boutique.inventario.eliminar_compra');
    Route::post('/boutique/inventario/editar_articulo', [BoutiqueController::class, 'editarArticulo'])->name('boutique.inventario.editar_articulo');
    Route::post('/boutique/inventario/eliminar_articulo', [BoutiqueController::class, 'eliminarArticulo'])->name('boutique.inventario.eliminar_articulo');

    // --- RUTAS DE FAMILIAS (AJAX) ---
    Route::post('/boutique/familias/agregar', [BoutiqueController::class, 'agregar_familia'])->name('boutique.familias.agregar');
    Route::get('/boutique/familias/{id}/articulos', [BoutiqueController::class, 'obtener_articulos_familia'])->name('boutique.familias.articulos');
    Route::put('/boutique/familias/{id}/editar', [BoutiqueController::class, 'editar_familia'])->name('boutique.familias.editar');
    Route::delete('/boutique/familias/{id}/eliminar', [BoutiqueController::class, 'eliminar_familia'])->name('boutique.familias.eliminar');
});