<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BoutiqueController;

Route::middleware(['auth', 'role:master,administrador'])->group(function () {
    // ----- Ventas ----- 
    Route::get('boutique/venta', [BoutiqueController::class, 'venta'])->name('boutique.venta');
    Route::get('boutique/venta/historial', [BoutiqueController::class, 'venta_historial'])->name('boutique.venta.historial');
    Route::get('boutique/gestionar/familias', [BoutiqueController::class, 'gestionar_familias'])->name('familias.index');
    Route::post('boutique/articulo/guardar_venta', [BoutiqueController::class, 'guardarVenta']);

    // ----- Reporteo -----
    Route::get('boutique/reporteo', [BoutiqueController::class, 'reporteo'])->name('boutique.reporteo');

    // ----- Inventario -----
    Route::get('boutique/inventario', [BoutiqueController::class, 'inventario'])->name('boutique.inventario');
    Route::get('boutique/inventario/historial', [BoutiqueController::class, 'inventario_historial'])->name('boutique.inventario.historial');
    Route::get('boutique/inventario/compras_eliminadas', [BoutiqueController::class, 'inventario_eliminaciones'])->name('boutique.inventario.eliminaciones');
    Route::post('boutique/inventario/nuevo_articulo', [BoutiqueController::class, 'nuevoArticulo']);
    Route::post('boutique/inventario/nueva_compra', [BoutiqueController::class, 'nuevaCompra']);
    Route::prefix('boutique/inventario')->group(function () {
        // Rutas para compras
        Route::post('/editar_compra', [BoutiqueController::class, 'editarCompra'])->name('boutique.editar_compra');
        Route::post('/eliminar_compra', [BoutiqueController::class, 'eliminarCompra'])->name('boutique.eliminar_compra');

        // Rutas para artículos
        Route::post('/editar_articulo', [BoutiqueController::class, 'editarArticulo'])->name('boutique.editar_articulo');
        Route::post('/eliminar_articulo', [BoutiqueController::class, 'eliminarArticulo'])->name('boutique.eliminar_articulo');
    });
    
    // ----- Familias -----
    Route::prefix('boutique/familias')->group(function () {
        Route::post('agregar', [BoutiqueController::class, 'agregar_familia']);
        Route::get('{familiaId}/articulos', [BoutiqueController::class, 'obtener_articulos_familia']);
        Route::put('{familiaId}/editar', [BoutiqueController::class, 'editar_familia']);
        Route::delete('{familiaId}/eliminar', [BoutiqueController::class, 'eliminar_familia']);
    });
});
