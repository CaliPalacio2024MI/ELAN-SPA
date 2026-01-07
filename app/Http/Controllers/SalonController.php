<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\{
    Reservation,
    Client,
    GrupoReserva,
    BlockedSlot,
    Sale
};
use Carbon\Carbon;
use App\Models\Spa;

class SalonController extends Controller
{
    public function index(Request $request)
    {
        //  Fechas seleccionadas o por defecto (semana actual)
        $fechaInicio = $request->input('fecha_inicio', Carbon::now()->startOfWeek()->toDateString());
        $fechaFin = $request->input('fecha_fin', Carbon::now()->endOfWeek()->toDateString());

        // Resolver SPA actual: preferir current_spa_id en sesión, si no existe intentar resolver por nombre
        $spaId = session('current_spa_id') ?? null;
        if (!$spaId) {
            $spaNombre = session('current_spa') ?? null;
            if ($spaNombre) {
                // Intentar match exacto primero (coincide con ReservationController)
                $spa = Spa::where('nombre', $spaNombre)->first();
                if (!$spa) {
                    // Caer a búsqueda más flexible (LIKE) si no hay match exacto
                    $spa = Spa::where('nombre', 'LIKE', '%' . ucfirst(strtolower($spaNombre)) . '%')->first();
                }

                $spaId = $spa->id ?? null;
            }
        }

        if (!$spaId) {
            return back()->withErrors(['spa_id' => 'No se encontró un spa asignado.']);
        }

        //  Solo reservaciones del departamento "salon de belleza" y del spa actual
        $reservacionesSalon = Reservation::with(['cliente', 'experiencia', 'anfitrion.operativo'])
            ->where('spa_id', $spaId)
            ->whereHas('anfitrion.operativo', function ($q) {
                $q->where('departamento', 'salon de belleza');
            })
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->get();

            // === Calcular el total de dinero y propinas del departamento "salon de belleza" ===
        $reservationIdsSalon = $reservacionesSalon->pluck('id');
 
         // Obtener los IDs ÚNICOS de los grupos de las reservaciones del salón (si existen)
        $grupoReservaIdsSalon = $reservacionesSalon->pluck('grupo_reserva_id')->filter()->unique();

         // Filtrar las ventas (Sale) usando ambos IDs: individuales y de grupo
        $ventasSalon = Sale::query()
             ->whereBetween('created_at', [$fechaInicio, $fechaFin])
             ->where(function ($query) use ($reservationIdsSalon, $grupoReservaIdsSalon) {
               // Condición para ventas individuales
        $query->whereIn('reservacion_id', $reservationIdsSalon)
              // O condición para ventas de grupo
              ->orWhereIn('grupo_reserva_id', $grupoReservaIdsSalon);
            })
            ->get();
        //  Totales generales
        $totales = [
            'reservaciones_totales' => $reservacionesSalon->count(),
            'reservaciones_pagadas' => $reservacionesSalon->whereNotNull('check_out')->count(),
            'reservaciones_no_pagadas' => $reservacionesSalon->whereNull('check_out')->count(),
            'ventas_total' => $ventasSalon->sum('total'),
            'ventas_propina' => $ventasSalon->sum('propina'),
        ];

        //  Agrupar por fecha
        $reporteDias = $reservacionesSalon->groupBy('fecha')->map(function ($reservas, $fecha) {
            // Contar reservas por horario
            $manana = $reservas->filter(fn($r) => $r->hora >= '08:00' && $r->hora < '12:00')->count();
            $medioDia = $reservas->filter(fn($r) => $r->hora >= '12:00' && $r->hora < '16:00')->count();
            $tarde = $reservas->filter(fn($r) => $r->hora >= '16:00' && $r->hora <= '20:00')->count();

            return [
                'fecha' => $fecha,
                'dia_semana' => Carbon::parse($fecha)->locale('es')->isoFormat('dddd'),
                'total' => $reservas->count(),
                'pagadas' => $reservas->whereNotNull('check_out')->count(),
                'no_pagadas' => $reservas->whereNull('check_out')->count(),
                'manana' => $manana,
                'medio_dia' => $medioDia,
                'tarde' => $tarde,
            ];
        })->sortKeys();

        //  Promedios diarios
        $diasContados = max(1, $reporteDias->count());
        $promedios = [
            'promedio_diario' => round($totales['reservaciones_totales'] / $diasContados, 1),
            'promedio_pagadas' => round($totales['reservaciones_pagadas'] / $diasContados, 1),
            'promedio_no_pagadas' => round($totales['reservaciones_no_pagadas'] / $diasContados, 1),
        ];

        return view('salon.index', compact('fechaInicio', 'fechaFin', 'totales', 'promedios', 'reporteDias'));
    }
}
