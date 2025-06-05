<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\GimnasioController;
use App\Http\Controllers\QrController;

Route::get('gimnasio/qr_code/{token?}', [GimnasioController::class, 'qr_code'])->name('gimnasio.qr_code');
Route::get('gimnasio/formulario/{token?}', [GimnasioController::class, 'registro'])->name('gimnasio.registro');
Route::get('gimnasio/formulario/token_invalido', [GimnasioController::class, 'token_invalido'])->name('gimnasio.token_invalido');
Route::get('gimnasio/reglamento', function () {
    return view('gimnasio.reglamento');
})->name('gimnasio.reglamento');
Route::post('gimnasio/formulario/guardar', [GimnasioController::class, 'guardarRegistro'])->name('gimnasio.guardar');
Route::post('gimnasio/verificar-renovar-qr/{tokenInterno}', [GimnasioController::class, 'verificarYRenovarQr'])->name('gimnasio.verificar_renovar_qr');
Route::get('/gimnasio/qr-image', [QrController::class, 'generarQR'])->name('gimnasio.qr-image');

Route::middleware(['auth', 'role:master,administrador'])->group(function () {
    // ----- Entradas ----- 
    Route::get('gimnasio/reporteo', [GimnasioController::class, 'reporteo'])->name('gimnasio.reporteo');
    Route::get('gimnasio/historial', [GimnasioController::class, 'historial'])->name('gimnasio.historial');
});
