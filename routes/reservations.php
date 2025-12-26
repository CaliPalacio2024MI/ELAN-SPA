<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\ReservationController;
use App\Http\Controllers\ExperienceController;
use App\Http\Controllers\EvaluationFormController;
use App\Http\Controllers\BlockedSlotController;
use App\Http\Controllers\SaleController;
use App\Http\Controllers\ReportController;

Route::middleware(['auth', 'role:master,administrador,recepcionista,anfitrion'])->group(function () {
     Route::get('/reservations/historial', [ReservationController::class, 'historial'])->name('reservations.historial');

    Route::get('/reservations', [ReservationController::class, 'index'])->name('reservations.index');
    Route::get('/reservations/{id}', [ReservationController::class, 'show'])->name('reservations.show');
    Route::get('/reservations/data', [ReservationController::class, 'getReservations']);
});

Route::middleware(['auth', 'role:master,administrador,recepcionista'])->group(function () {
    // CRUD básico
    Route::post('/reservations', [ReservationController::class, 'store'])->name('reservations.store');
    Route::put('/reservations/{id}', [ReservationController::class, 'update'])->name('reservations.update');
    Route::delete('/reservations/{id}', [ReservationController::class, 'destroy'])->name('reservations.destroy');
    Route::get('/reservations/{id}/edit', [ReservationController::class, 'edit'])->name('reservations.edit');

    // Cliente y grupo
    Route::post('/buscar-cliente', [ReservationController::class, 'buscarCliente'])->name('reservations.buscarCliente');
    Route::post('/reservations/grupo', [ReservationController::class, 'storeGrupo'])->name('reservations.storeGrupo');
    Route::post('/reservations/{reservation}/pay', [ReservationController::class, 'markAsPaid'])->name('reservations.markAsPaid');

    // Formulario médico (Check-in)
    Route::get('/reservations/{reservation}/checkin', [EvaluationFormController::class, 'create'])->name('evaluation.create');
    Route::post('/reservations/{reservation}/checkin', [EvaluationFormController::class, 'store'])->name('evaluation.store');
    Route::get('/reservations/{reservation}/checkin/view', [EvaluationFormController::class, 'show'])->name('evaluation.show');
    Route::get('/reservations/{reservation}/evaluation', [EvaluationFormController::class, 'show']);
    
    // Bloqueo de horarios
    Route::post('/blocked-slots', [BlockedSlotController::class, 'store'])->name('blocked-slots.store');

    //Cobro
    Route::get('/reservations/{reservation}/checkout', [SaleController::class, 'checkout'])->name('sales.checkout');
    Route::post('/sales/store', [SaleController::class, 'store'])->name('sales.store');

    Route::get('/reportes', [ReportController::class, 'index'])->name('reports.index');
    Route::get('/reportes/panorama', [ReportController::class, 'panorama'])->name('reports.panorama');
    Route::get('/reportes/exportar', [ReportController::class, 'export'])->name('reports.export');
    Route::get('/reportes/exportar/{tipo}', [ReportController::class, 'exportTipo'])->name('reports.export.tipo');

});



Route::middleware(['auth', 'role:master,administrador'])->group(function () {
    Route::resource('experiences', ExperienceController::class);
});