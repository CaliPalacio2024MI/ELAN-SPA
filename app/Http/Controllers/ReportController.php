<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\GrupoReserva;
use App\Models\BlockedSlot;
use App\Models\Client;
use App\Models\Sale;
use App\Models\Anfitrion;
use App\Models\BoutiqueVenta;
use App\Models\BoutiqueVentaDetalle;
use App\Models\Experience;
use App\Models\Spa;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    /**
     * Define los servicios que aparecerán en el menú y filtros.
     */
    private function getServiciosDisponibles($spaId)
    {
        // Obtener todas las experiencias para el spa actual y convertirlas a un formato de array para el filtro.
        return Experience::where('spa_id', $spaId)
            ->orderBy('nombre')
            ->get()
            ->pluck('nombre', 'id') // 'id' será la clave, 'nombre' será el valor visible
            ->toArray();
    }
    public function index(Request $request)
    {
        // --- Filtros y contexto ---
        $spaNombre = session('current_spa');
        $spa = Spa::where('nombre', $spaNombre)->firstOrFail();
        $spaId = $spa->id;

        // Obtener los servicios (experiencias) disponibles para el SPA actual
        $servicios = $this->getServiciosDisponibles($spaId);

        $servicio = $request->input('servicio'); // Ahora será el ID de una experiencia
        $fechaInicio = $request->input('desde') ?? now()->toDateString();
        $fechaFin = $request->input('hasta') ?? now()->toDateString();        

        // --- Inicialización de variables de reporte ---
        $totales = [];
        $gananciasPorDia = collect();
        $topExperiencias = collect();
        $diasFrecuentes = collect();

        // --- Lógica de filtrado por servicio ---
        $queryReservations = Reservation::where('spa_id', $spaId)->whereBetween('fecha', [$fechaInicio, $fechaFin]);
        $querySales = Sale::where('spa_id', $spaId);

        // Si se ha seleccionado una experiencia específica, filtramos por ella.
        if ($servicio && is_numeric($servicio)) {
            $queryReservations->where('experiencia_id', $servicio);
        }

        // Se filtra por el rango de fechas y servicio (si existe), pero basándose en la fecha de la reservación asociada a la venta.
        // Esto unifica el criterio de fecha para todo el dashboard.
        $querySales->where(function ($q) use ($fechaInicio, $fechaFin, $servicio) {
            $q->whereHas('reservacion', function ($rq) use ($fechaInicio, $fechaFin, $servicio) {
                $rq->whereBetween('fecha', [$fechaInicio, $fechaFin])
                   ->when($servicio, fn($subq) => $subq->where('experiencia_id', $servicio));
            })->orWhereHas('grupoReserva.reservaciones', function ($grq) use ($fechaInicio, $fechaFin, $servicio) {
                $grq->whereBetween('fecha', [$fechaInicio, $fechaFin])
                   ->when($servicio, fn($subq) => $subq->where('experiencia_id', $servicio));
            });
        });

        // --- Cálculos de Totales ---
        // Esta sección ahora calcula los totales basados en las reservaciones filtradas (ya sea por una experiencia o todas)
        $totales = [
            'reservaciones_activas' => (clone $queryReservations)->where('estado', 'activa')->count(),
            'reservaciones_canceladas' => (clone $queryReservations)->where('estado', 'cancelada')->count(),
            'check_in' => (clone $queryReservations)->where('check_in', true)->count(),
            'check_out' => (clone $queryReservations)->where('check_out', true)->count(),
            'clientes_atendidos' => Client::whereHas('reservaciones', fn($q) => $q->whereIn('id', (clone $queryReservations)->pluck('id')))->distinct()->count('clients.id'),
            'grupos' => GrupoReserva::whereHas('reservaciones', fn($q) => $q->whereIn('id', (clone $queryReservations)->pluck('id')))->count(),
            'bloqueos' => BlockedSlot::where('spa_id', $spaId)->whereBetween('fecha', [$fechaInicio, $fechaFin])->count(),
            'ventas_total' => (clone $querySales)->sum('total'),
            'ventas_propina' => (clone $querySales)->sum('propina'),
        ];

        // Lógica para calcular ganancias por día distribuyendo el total de ventas de grupo.
        $salesForChart = (clone $querySales)->with([
            'reservacion:id,fecha',
            'grupoReserva:id',
            'grupoReserva.reservaciones:id,grupo_reserva_id,fecha'
        ])->get();
        
        $dailyGains = $salesForChart->reduce(function ($carry, $sale) {
            if ($sale->reservacion) {
                $date = $sale->reservacion->fecha;
                $carry[$date] = ($carry[$date] ?? 0) + $sale->total;
            } elseif ($sale->grupoReserva && $sale->grupoReserva->reservaciones->isNotEmpty()) {
                $reservationsInGroup = $sale->grupoReserva->reservaciones;
                $count = $reservationsInGroup->count();
                if ($count > 0) {
                    $perResTotal = $sale->total / $count;
                    foreach ($reservationsInGroup as $res) {
                        $date = $res->fecha;
                        $carry[$date] = ($carry[$date] ?? 0) + $perResTotal;
                    }
                }
            }
            return $carry;
        }, []);
        
        ksort($dailyGains);
        
        $gananciasPorDia = collect(array_slice($dailyGains, -7, 7, true))->map(function ($total, $fecha) {
            return (object)['fecha' => $fecha, 'total' => $total];
        })->values();
        
        $topExperiencias = (clone $queryReservations)->select('experiencia_id', DB::raw('count(*) as total'))
            ->where('estado', 'activa')->groupBy('experiencia_id')->with('experiencia')->orderByDesc('total')->limit(5)->get();

        $diasFrecuentes = (clone $queryReservations)->select('fecha', DB::raw('count(*) as total'))
            ->where('estado', 'activa')->groupBy('fecha')->orderByDesc('total')->limit(5)->get();


        return view('reservations.reports.index', compact('totales', 'topExperiencias', 'diasFrecuentes', 'fechaInicio', 'fechaFin', 'gananciasPorDia', 'servicios', 'spaId'));
    }

    public function export(Request $request)
    {
        $fechaInicio = $request->input('desde') ?? now()->toDateString();
        $fechaFin = $request->input('hasta') ?? now()->toDateString();
        $lugar = $request->input('lugar');

        $reservaciones = Reservation::with('cliente', 'experiencia')
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->when($lugar, fn($q) => $q->where('spa_id', $lugar))
            ->get();
    

        $filename = "reporte_reservaciones_" . now()->format('Ymd_His') . ".xls";

        $headers = [
            "Content-type" => "application/vnd.ms-excel",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Expires" => "0",
        ];

        $content = '
        <table border="1">
            <thead>
                <tr>
                    <th>Cliente</th>
                    <th>Experiencia</th>
                    <th>Fecha</th>
                    <th>Hora</th>
                    <th>Check-in</th>
                    <th>Check-out</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>';

        foreach ($reservaciones as $reserva) {
            $content .= '
                <tr>
                    <td>' . htmlspecialchars($reserva->cliente ? $reserva->cliente->nombre . ' ' . $reserva->cliente->apellido_paterno . ' ' . $reserva->cliente->apellido_materno : 'N/D') . '</td>
                    <td>' . htmlspecialchars($reserva->experiencia->nombre ?? 'N/D') . '</td>
                    <td>' . $reserva->fecha . '</td>
                    <td>' . substr($reserva->hora, 0, 5) . '</td>
                    <td>' . ($reserva->check_in ? 'Sí' : 'No') . '</td>
                    <td>' . ($reserva->check_out ? 'Sí' : 'No') . '</td>
                    <td>' . ucfirst($reserva->estado) . '</td>
                </tr>';
        }

        $content .= '
            </tbody>
        </table>';

        return response($content, 200, $headers);
    }

    public function exportTipo(Request $request, $tipo)
    {
        $fechaInicio = $request->input('desde') ?? now()->startOfMonth()->toDateString();
        $fechaFin = $request->input('hasta') ?? now()->endOfMonth()->toDateString();
        $search = $request->input('search');
        $servicio = $request->input('servicio');

        $spaId = $request->input('lugar');
        $spaName = $request->input('spa'); 

        if (!$spaId && $spaName) {
            $spa = Spa::where('nombre', $spaName)->first();
            if ($spa) {
                $spaId = $spa->id;
            }
        }

        $datos = [];
        $filename = "reporte_" . $tipo . "_" . now()->format('Ymd_His') . ".xls";
        $content = '<table><thead><tr>';

        $searchTerm = $search ? mb_strtolower($search, 'UTF-8') : null;

        switch ($tipo) {
            case 'activos':
            case 'cancelados':
            case 'checkins':
            case 'checkouts':
                $estadoField = match($tipo) {
                    'activos'     => ['estado', 'activa'],
                    'cancelados'  => ['estado', 'cancelada'],
                    'checkins'    => ['check_in', true],
                    'checkouts'   => ['check_out', true],
                };

                $query = Reservation::with('cliente', 'experiencia')
                    ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                    // Aplicar la condición por tipo sólo si el filtro 'pagado' NO fue enviado desde la vista.
                    ->when($request->input('pagado') === null, fn($q) => $q->where($estadoField[0], $estadoField[1]))
                    ->when($spaId, fn($q) => $q->where('spa_id', $spaId))
                    ->when($servicio, fn($q) => $q->where('experiencia_id', $servicio));

                // Aplicar filtros que vienen desde la vista de historial
                if ($request->filled('cliente')) {
                    $clienteFiltro = trim($request->input('cliente'));
                    $query->whereHas('cliente', function ($cq) use ($clienteFiltro) {
                        $cq->where('nombre', 'like', "%{$clienteFiltro}%")
                           ->orWhere('apellido_paterno', 'like', "%{$clienteFiltro}%")
                           ->orWhere('apellido_materno', 'like', "%{$clienteFiltro}%");
                    });
                }

                if ($request->filled('experiencia')) {
                    $expFiltro = trim($request->input('experiencia'));
                    $query->whereHas('experiencia', function ($eq) use ($expFiltro) {
                        $eq->where('nombre', 'like', "%{$expFiltro}%");
                    });
                }

                if ($request->filled('cabina')) {
                    $cabFiltro = trim($request->input('cabina'));
                    $query->whereHas('cabina', function ($cbq) use ($cabFiltro) {
                        $cbq->where('nombre', 'like', "%{$cabFiltro}%");
                    });
                }

                if ($request->filled('anfitrion')) {
                    $anfiFiltro = trim($request->input('anfitrion'));
                    $query->whereHas('anfitrion', function ($aq) use ($anfiFiltro) {
                        $aq->where('nombre_usuario', 'like', "%{$anfiFiltro}%");
                    });
                }

                if ($request->filled('pagado')) {
                    $pagadoFiltro = $request->input('pagado');
                    if ($pagadoFiltro === 'pagado') {
                        $query->where('check_out', true);
                    } elseif ($pagadoFiltro === 'pendiente') {
                        $query->where('check_out', false);
                    }
                }

                if ($searchTerm) {
                    $query->where(function($q) use ($searchTerm) {
                        $q->whereHas('cliente', function ($cq) use ($searchTerm) {
                            $cq->where(DB::raw("LOWER(CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno))"), 'like', "%{$searchTerm}%");
                        })
                        ->orWhereHas('experiencia', function ($eq) use ($searchTerm) {
                            $eq->where(DB::raw('LOWER(nombre)'), 'like', "%{$searchTerm}%");
                        })
                        ->orWhere('fecha', 'like', "%{$searchTerm}%")
                        ->orWhere('hora', 'like', "%{$searchTerm}%")
                        ->orWhere('estado', 'like', "%{$searchTerm}%");
                    });
                }
                $datos = $query->get();

                $content .= '<th>Cliente</th><th>Experiencia</th><th>Fecha</th><th>Hora</th><th>Estado</th></tr></thead><tbody>';
                foreach ($datos as $r) {
                    $cliente = $r->cliente;
                    $nombreCompleto = $cliente ? trim($cliente->nombre . ' ' . $cliente->apellido_paterno . ' ' . $cliente->apellido_materno) : '-';
                    $content .= "<tr>
                        <td>" . htmlspecialchars($nombreCompleto) . "</td>
                        <td>" . htmlspecialchars($r->experiencia->nombre ?? '-') . "</td>
                        <td>" . htmlspecialchars($r->fecha) . "</td>
                        <td>" . htmlspecialchars(substr($r->hora, 0, 5)) . "</td>
                        <td>" . htmlspecialchars(ucfirst($r->estado)) . "</td>
                    </tr>";
                }
                break;

            case 'clientes':
                $orderBy = $request->input('order', 'fecha');
                $orderDir = $request->input('order_dir', 'asc');

                $q = Reservation::with('cliente', 'experiencia', 'anfitrion', 'sale', 'grupoReserva.reservaciones')
                    ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                    ->when($spaId, fn($q) => $q->where('spa_id', $spaId))
                    ->when($servicio, fn($q) => $q->where('experiencia_id', $servicio));

                if ($searchTerm) {
                    $q->where(function ($query) use ($searchTerm) {
                        $query->where('fecha', 'like', "%{$searchTerm}%")
                              ->orWhere('hora', 'like', "%{$searchTerm}%")
                              ->orWhereHas('cliente', function ($cq) use ($searchTerm) {
                                  $cq->where(DB::raw("LOWER(CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno))"), 'like', "%{$searchTerm}%")
                                     ->orWhere('telefono', 'like', "%{$searchTerm}%")
                                     ->orWhere('tipo_visita', 'like', "%{$searchTerm}%")
                                     ->orWhere(DB::raw('LOWER(correo)'), 'like', "%{$searchTerm}%");
                              })
                              ->orWhereHas('experiencia', fn($eq) => 
                                  $eq->where(DB::raw('LOWER(nombre)'), 'like', "%{$searchTerm}%")
                              )
                              ->orWhereHas('anfitrion', fn($aq) => 
                                  $aq->where(DB::raw("LOWER(CONCAT_WS(' ', nombre_usuario, apellido_paterno, apellido_materno))"), 'like', "%{$searchTerm}%")
                              )
                              ->orWhere(function ($saleQuery) use ($searchTerm) {
                                  // Búsqueda para ventas individuales (monto total)
                                  $saleQuery->whereHas('sale', function ($sq) use ($searchTerm) {
                                      $sq->where(DB::raw("CAST(total AS CHAR)"), 'like', "%{$searchTerm}%");
                                  })
                                  // Búsqueda para ventas de grupo (monto proporcional)
                                  ->orWhereHas('grupoReserva.sale', function ($gsq) use ($searchTerm) {
                                      $gsq->where(DB::raw("CAST(total / GREATEST((SELECT COUNT(*) FROM reservations r_inner WHERE r_inner.grupo_reserva_id = sales.grupo_reserva_id), 1) AS CHAR)"), 'like', "%{$searchTerm}%");
                                  });
                              });
                    });
                }
                if ($orderBy === 'cliente') {
                    $q->join('clients', 'reservations.cliente_id', '=', 'clients.id')
                        ->orderBy(DB::raw("CONCAT(clients.nombre, ' ', clients.apellido_paterno, ' ', clients.apellido_materno)"), $orderDir)
                        ->select('reservations.*');
                } else {
                    $q->orderBy('reservations.' . $orderBy, $orderDir);
                }

                $q->orderBy('reservations.id', 'desc');

                $reservas = $q->get();

                $groupIds = $reservas->whereNotNull('grupo_reserva_id')->whereNull('sale')->pluck('grupo_reserva_id')->unique();
                $groupSales = Sale::whereIn('grupo_reserva_id', $groupIds)->get()->keyBy('grupo_reserva_id');

                $content .= '<th>Cliente</th><th>Teléfono</th><th>Email</th><th>Tipo Visita</th><th>Experiencia</th><th>Terapeuta</th><th>Fecha</th><th>Hora</th><th>Monto Pagado</th></tr></thead><tbody>';

                foreach ($reservas as $r) {
                    $cliente = $r->cliente;
                    $nombre = $cliente ? ($cliente->nombre . ' ' . ($cliente->apellido_paterno ?? '') . ' ' . ($cliente->apellido_materno ?? '')) : 'N/D';
                    $telefono = $cliente->telefono ?? '';
                    $email = $cliente->correo ?? '';
                    $tipoVisita = $cliente->tipo_visita ?? '';
                    $experiencia = $r->experiencia->nombre ?? '';
                    $terapeuta = $r->anfitrion ? ($r->anfitrion->nombre_usuario . ' ' . ($r->anfitrion->apellido_paterno ?? '') . ' ' . ($r->anfitrion->apellido_materno ?? '')) : '';
                    $fecha = $r->fecha;
                    $hora = $r->hora;

                    $sale = $r->sale ?? $groupSales->get($r->grupo_reserva_id);
                    $monto = 0.0;
                    if ($sale) {
                        if ($sale->grupo_reserva_id && $r->grupoReserva && $r->grupoReserva->reservaciones->count() > 0) {
                            $monto = floatval($sale->total) / $r->grupoReserva->reservaciones->count();
                        } else {
                            $monto = floatval($sale->total);
                        }
                    }

                    $content .= "<tr>";
                    $content .= "<td>" . htmlspecialchars($nombre) . "</td>";
                    $content .= "<td>" . htmlspecialchars($telefono) . "</td>";
                    $content .= "<td>" . htmlspecialchars($email) . "</td>";
                    $content .= "<td>" . htmlspecialchars($tipoVisita) . "</td>";
                    $content .= "<td>" . htmlspecialchars($experiencia) . "</td>";
                    $content .= "<td>" . htmlspecialchars($terapeuta) . "</td>";
                    $content .= "<td>" . $fecha . "</td>";
                    $content .= "<td>" . $hora . "</td>";
                    $content .= "<td>$" . number_format($monto, 2) . "</td>";
                    $content .= "</tr>";
                }
                break;

            case 'bloqueos':
                $query = BlockedSlot::whereBetween('fecha', [$fechaInicio, $fechaFin])
                    ->when($spaId, fn($q) => $q->where('spa_id', $spaId));

                if ($searchTerm) {
                    $query->where(function($q) use ($searchTerm) {
                        $q->where('fecha', 'like', "%{$searchTerm}%")
                          ->orWhere('hora', 'like', "%{$searchTerm}%")
                          ->orWhere('duracion', 'like', "%{$searchTerm}%")
                          ->orWhere(DB::raw('LOWER(motivo)'), 'like', "%{$searchTerm}%");
                    });
                }
                $datos = $query->get();

                $content .= '<th>Fecha</th><th>Hora</th><th>Duración</th><th>Motivo</th></tr></thead><tbody>';
                foreach ($datos as $b) {
                    $content .= "<tr>
                        <td>" . htmlspecialchars($b->fecha) . "</td>
                        <td>" . htmlspecialchars(substr($b->hora, 0, 5)) . "</td>
                        <td>" . htmlspecialchars($b->duracion) . "</td>
                        <td>" . htmlspecialchars($b->motivo) . "</td>
                    </tr>";
                }
                break;

            case 'grupos':
                $query = GrupoReserva::withCount('reservaciones')
                    ->with('reservaciones:id,grupo_reserva_id,fecha,hora,es_principal')
                    ->whereHas('reservaciones', function ($q) use ($fechaInicio, $fechaFin, $spaId, $servicio) {
                        $q->whereBetween('fecha', [$fechaInicio, $fechaFin])
                          ->when($spaId, fn($subq) => $subq->where('spa_id', $spaId))
                          ->when($servicio, fn($subq) => $subq->where('experiencia_id', $servicio));
                    })
                    ->orderBy('created_at', 'desc');

                if ($searchTerm) {
                    $query->where(function($q) use ($searchTerm) {
                        $q->where('id', 'like', "%{$searchTerm}%")
                          ->orWhere('created_at', 'like', "%{$searchTerm}%");
                    });
                }
                $datos = $query->get();
                $content .= '<th>Fecha de creación</th><th>Fecha de Reserva</th><th>Reservaciones</th></tr></thead><tbody>';
                foreach ($datos as $g) {
                    $reservaPrincipal = $g->reservaciones->firstWhere('es_principal', true)
                        ?? $g->reservaciones->sortBy('fecha')->sortBy('hora')->first();
                    
                    $fechaReserva = $reservaPrincipal ? Carbon::parse($reservaPrincipal->fecha)->format('d/m/Y') : 'N/D';

                    $content .= "<tr>
                        <td>" . htmlspecialchars($g->created_at->format('d/m/Y H:i')) . "</td>
                        <td>" . htmlspecialchars($fechaReserva) . "</td>
                        <td>" . htmlspecialchars($g->reservaciones_count) . "</td>
                    </tr>";
                }
                break;

            case 'experiencias':
                $query = Reservation::select('experiencia_id', DB::raw('count(*) as total'))
                    ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                    ->when($spaId, fn($q) => $q->where('spa_id', $spaId))
                    ->when($servicio, fn($q) => $q->where('experiencia_id', $servicio))
                    ->where('estado', 'activa')
                    ->groupBy('experiencia_id')
                    ->with('experiencia');

                if ($searchTerm) {
                    $query->whereHas('experiencia', function ($eq) use ($searchTerm) {
                        $eq->where(DB::raw('LOWER(nombre)'), 'like', "%{$searchTerm}%");
                    });
                }
                $datos = $query->orderByDesc('total')->get();

                $content .= '<th>Experiencia</th><th>Total</th></tr></thead><tbody>';
                foreach ($datos as $e) {
                    $content .= "<tr>
                        <td>" . htmlspecialchars($e->experiencia->nombre ?? 'N/D') . "</td>
                        <td>" . htmlspecialchars($e->total) . "</td>
                    </tr>";
                }
                break;

            case 'terapeutas':
                $query = Reservation::with('anfitrion', 'experiencia')
                ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                ->when($spaId, fn($q) => $q->where('spa_id', $spaId))
                ->when($servicio, fn($q) => $q->where('experiencia_id', $servicio))
                ->where('check_out', true)
                ->with('sale', 'grupoReserva.reservaciones');
                $reservas = $query->get();
                $porTerapeuta = [];
                
                $groupIds = $reservas->whereNotNull('grupo_reserva_id')->whereNull('sale')->pluck('grupo_reserva_id')->unique();
                $groupSales = Sale::whereIn('grupo_reserva_id', $groupIds)->get()->keyBy('grupo_reserva_id');
                
                foreach ($reservas as $r) {
                    $anf = $r->anfitrion;
                    $terId = $anf ? $anf->id : 0;
                    $terName = $anf ? ($anf->nombre_usuario . ' ' . ($anf->apellido_paterno ?? '') . ' ' . ($anf->apellido_materno ?? '')) : 'Sin terapeuta';
                
                    $exp = $r->experiencia;
                    $expId = $exp ? $exp->id : 0;
                    $expName = $exp ? $exp->nombre : 'Sin experiencia';
                    $sale = $r->sale ?? $groupSales->get($r->grupo_reserva_id);
                
                    if (!$sale) {
                        continue;
                    }

                    $numReservationsInSale = 1;
                    if ($sale->grupo_reserva_id && $r->grupoReserva && $r->grupoReserva->reservaciones->count() > 0) {
                        $numReservationsInSale = $r->grupoReserva->reservaciones->count();
                    }
                
                    $subtotal = floatval($sale->subtotal) / $numReservationsInSale;
                    $impuestos = floatval($sale->impuestos) / $numReservationsInSale;
                    $total = $subtotal + $impuestos;
                
                    if (!isset($porTerapeuta[$terId])) {
                        $porTerapeuta[$terId] = [
                            'nombre' => $terName,
                            'total_vendido' => 0,
                            'servicios' => [],
                        ];
                    }
                
                    if (!isset($porTerapeuta[$terId]['servicios'][$expId])) {
                        $porTerapeuta[$terId]['servicios'][$expId] = [
                            'nombre' => $expName,
                            'cantidad' => 0,
                            'subtotal' => 0,
                            'impuestos' => 0,
                            'total' => 0,
                        ];
                    }
                
                    $porTerapeuta[$terId]['servicios'][$expId]['cantidad'] += 1;
                    $porTerapeuta[$terId]['servicios'][$expId]['subtotal'] += $subtotal;
                    $porTerapeuta[$terId]['servicios'][$expId]['impuestos'] += $impuestos;
                    $porTerapeuta[$terId]['servicios'][$expId]['total'] += $total;
                
                    $porTerapeuta[$terId]['total_vendido'] += $total;
                }

                // Si hay un término de búsqueda, filtramos el array agregado en PHP para que coincida con lo que ve el usuario.
                if ($searchTerm) {
                    $searchTermLower = mb_strtolower($searchTerm, 'UTF-8');
                    $resultadosFiltrados = [];

                    foreach ($porTerapeuta as $terId => $terData) {
                        $serviciosCoincidentes = [];

                        foreach ($terData['servicios'] as $expId => $servicioData) {
                            // Concatenamos todos los campos visibles en una sola cadena para la búsqueda
                            $cadenaFila = implode('|', [
                                $terData['nombre'],
                                $servicioData['nombre'],
                                $servicioData['cantidad'],
                                number_format($servicioData['subtotal'], 2, '.', ''),
                                number_format($servicioData['impuestos'], 2, '.', ''),
                                number_format($servicioData['total'], 2, '.', '')
                            ]);

                            // Buscamos el término en la cadena de la fila
                            if (str_contains(mb_strtolower($cadenaFila, 'UTF-8'), $searchTermLower)) {
                                $serviciosCoincidentes[$expId] = $servicioData;
                            }
                        }

                        // Si alguna de las filas de servicio de este terapeuta coincide, lo agregamos
                        if (!empty($serviciosCoincidentes)) {
                            $resultadosFiltrados[$terId] = [
                                'nombre' => $terData['nombre'],
                                'total_vendido' => array_sum(array_column($serviciosCoincidentes, 'total')),
                                'servicios' => $serviciosCoincidentes,
                            ];
                        }
                    }
                    $porTerapeuta = $resultadosFiltrados;
                }

                $content .= '<th>Terapeuta</th><th>Servicio</th><th>Cantidad</th><th>Subtotal</th><th>IVA</th><th>Total</th></tr></thead><tbody>';

                foreach ($porTerapeuta as $ter) {
                    $nombreTer = htmlspecialchars($ter['nombre']);                    
                    foreach ($ter['servicios'] as $s) {
                        $content .= "<tr>";
                        $content .= "<td>" . $nombreTer . "</td>";
                        $content .= "<td>" . htmlspecialchars($s['nombre']) . "</td>";
                        $content .= "<td>" . intval($s['cantidad']) . "</td>";
                        $content .= "<td>$" . number_format($s['subtotal'], 2) . "</td>";
                        $content .= "<td>$" . number_format($s['impuestos'], 2) . "</td>";
                        $content .= "<td>$" . number_format($s['total'], 2) . "</td>";
                        $content .= "</tr>";
                    }                    
                
                    $content .= "<tr><td colspan=\"5\" style=\"text-align: right;\"><strong>Total vendido:</strong></td><td style=\"text-align: right;\"><strong>$" . number_format($ter['total_vendido'], 2) . "</strong></td></tr>";
                }
                break;

            case 'propinas': 
                    $salesQuery = Sale::with([
                        'reservacion.anfitrion', 
                        'reservacion.experiencia', 
                        'cliente', 
                        'grupoReserva.reservaciones.anfitrion', 
                        'grupoReserva.reservaciones.experiencia'
                    ])
                    ->where(function ($query) use ($fechaInicio, $fechaFin) {
                        $query->whereHas('reservacion', function ($q) use ($fechaInicio, $fechaFin) {
                            $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
                        })
                        ->orWhereHas('grupoReserva', function ($q) use ($fechaInicio, $fechaFin) {
                            $q->whereHas('reservaciones', function ($rq) use ($fechaInicio, $fechaFin) {
                                $rq->whereBetween('fecha', [$fechaInicio, $fechaFin]);
                            });
                        });
                    })
                    ->when($spaId, fn($q) => $q->where('spa_id', $spaId))
                    ->when($servicio, function ($query) use ($servicio) {
                        $query->where(function ($q) use ($servicio) {
                            $q->whereHas('reservacion', fn($rq) => $rq->where('experiencia_id', $servicio))
                            ->orWhereHas('grupoReserva', fn($grq) => 
                                    $grq->whereHas('reservaciones', fn($r) => 
                                        $r->where('experiencia_id', $servicio)
                                    )
                            );
                        });
                    })
                    ->where('propina', '>', 0);

                    if ($searchTerm) {
                        $tasaIvaPropina = config('finance.tax_rates.tip_iva', 0.16);
                        $salesQuery->where(function ($query) use ($searchTerm, $tasaIvaPropina) {
                            $query->whereHas('cliente', function ($cq) use ($searchTerm) {
                                $cq->where(DB::raw("LOWER(CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno))"), 'like', "%{$searchTerm}%");
                            })
                            ->orWhereHas('reservacion', function ($subQuery) use ($searchTerm) {
                                $subQuery->whereHas('anfitrion', function ($aq) use ($searchTerm) {
                                    $aq->where(DB::raw("LOWER(CONCAT_WS(' ', nombre_usuario, apellido_paterno, apellido_materno))"), "like", "%{$searchTerm}%");
                                })
                                ->orWhereHas('experiencia', function ($eq) use ($searchTerm) {
                                    $eq->where(DB::raw("LOWER(nombre)"), 'like', "%{$searchTerm}%");
                                })
                                ->orWhere('fecha', 'like', "%{$searchTerm}%");
                            })
                            ->orWhereHas('grupoReserva.reservaciones', function ($groupQuery) use ($searchTerm) {
                                $groupQuery->whereHas('anfitrion', function ($aq) use ($searchTerm) {
                                    $aq->where(DB::raw("LOWER(CONCAT_WS(' ', nombre_usuario, apellido_paterno, apellido_materno))"), 'like', "%{$searchTerm}%");
                                })
                                ->orWhereHas('experiencia', function ($eq) use ($searchTerm) {
                                    $eq->where(DB::raw("LOWER(nombre)"), 'like', "%{$searchTerm}%");
                                })
                                ->orWhere('fecha', 'like', "%{$searchTerm}%");
                            })
                            ->orWhere(DB::raw("CAST(propina AS CHAR)"), 'like', "%{$searchTerm}%")
                            ->orWhere(DB::raw("CAST(propina * {$tasaIvaPropina} AS CHAR)"), 'like', "%{$searchTerm}%");
                        });
                    }

                    $sales = $salesQuery->get();

                    $content .= '<th>Terapeuta</th><th>Servicio</th><th>Fecha</th><th>Cliente</th><th>Propina</th><th>IVA Propina</th></tr></thead><tbody>';

                    foreach ($sales as $sale) {
                        $r = $sale->reservacion;
                        if (!$r && $sale->grupo_reserva_id) {
                            $r = $sale->grupoReserva->reservaciones->first();
                        }

                        $anf = $r->anfitrion ?? null;
                        $terName = $anf ? ($anf->nombre_usuario . ' ' . ($anf->apellido_paterno ?? '') . ' ' . ($anf->apellido_materno ?? '')) : 'Sin terapeuta';

                        $exp = $r->experiencia ?? null;
                        $expName = $exp ? $exp->nombre : 'Sin experiencia';

                        $cliente = $sale->cliente 
                                    ? ($sale->cliente->nombre . ' ' . $sale->cliente->apellido_paterno . ' ' . $sale->cliente->apellido_materno)
                                    : 'N/D';

                        $propina = floatval($sale->propina ?: 0);
                        // Obtener la tasa desde el archivo de configuración.
                        $iva_propina = $propina * config('finance.tax_rates.tip_iva', 0.16);

                        $content .= "<tr>";
                        $content .= "<td>" . htmlspecialchars($terName) . "</td>";
                        $content .= "<td>" . htmlspecialchars($expName) . "</td>";
                        $content .= "<td>" . ($r->fecha ?? $sale->created_at->format('Y-m-d')) . "</td>";
                        $content .= "<td>" . htmlspecialchars($cliente) . "</td>";
                        $content .= "<td>$" . number_format($propina, 2) . "</td>";
                        $content .= "<td>$" . number_format($iva_propina, 2) . "</td>";
                        $content .= "</tr>";
                    }
                break;

            case 'servicios':
                $detalle = $request->input('detalle', false);
                $query = Reservation::with('anfitrion', 'experiencia', 'cliente')
                    ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                    ->when($spaId, fn($q) => $q->where('spa_id', $spaId))
                    ->when($servicio, fn($q) => $q->where('experiencia_id', $servicio))
                    ->where('check_out', true);

                if ($detalle) {
                    if ($searchTerm) {
                        $query->where(function ($q) use ($searchTerm) {
                            $q->whereHas('experiencia', fn($eq) => $eq->where(DB::raw('LOWER(nombre)'), 'like', "%{$searchTerm}%"))
                              ->orWhereHas('anfitrion', fn($aq) => $aq->where(DB::raw("LOWER(CONCAT_WS(' ', nombre_usuario, apellido_paterno, apellido_materno))"), 'like', "%{$searchTerm}%"))
                              ->orWhereHas('cliente', fn($cq) => $cq->where(DB::raw("LOWER(CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno))"), 'like', "%{$searchTerm}%"))
                              ->orWhere('fecha', 'like', "%{$searchTerm}%")
                              ->orWhere('hora', 'like', "%{$searchTerm}%")
                              ->orWhereHas('sale', function ($sq) use ($searchTerm) {
                                  $sq->where(DB::raw("(subtotal + impuestos)"), 'like', "%{$searchTerm}%");
                              })
                              ->orWhereHas('grupoReserva.sale', function ($gsq) use ($searchTerm) {
                                  $gsq->where(DB::raw("(subtotal + impuestos)"), 'like', "%{$searchTerm}%");
                              });
                        });
                    }
                    $reservas = $query->get();
                    $groupIds = $reservas->whereNotNull('grupo_reserva_id')->whereNull('sale')->pluck('grupo_reserva_id')->unique();
                    $groupSales = Sale::whereIn('grupo_reserva_id', $groupIds)->get()->keyBy('grupo_reserva_id');

                    $content .= '<th>Servicio</th><th>Cantidad</th><th>Monto</th><th>Terapeuta</th><th>Fecha</th><th>Hora</th><th>Cliente</th></tr></thead><tbody>';
                    foreach ($reservas as $r) {
                        $sale = $r->sale ?? $groupSales->get($r->grupo_reserva_id);
                        $monto = 0;
                        if ($sale) {
                            $numReservationsInSale = ($sale->grupo_reserva_id && $r->grupoReserva && $r->grupoReserva->reservaciones->count() > 0) ? $r->grupoReserva->reservaciones->count() : 1;
                            $monto = (floatval($sale->subtotal) + floatval($sale->impuestos)) / $numReservationsInSale;
                        }

                        $content .= "<tr>";
                        $content .= "<td>" . ($r->experiencia->nombre ?? 'N/D') . "</td>";
                        $content .= "<td>1</td>";
                        $content .= "<td>$" . number_format($monto, 2) . "</td>";
                        $content .= "<td>" . ($r->anfitrion ? ($r->anfitrion->nombre_usuario . ' ' . ($r->anfitrion->apellido_paterno ?? '') . ' ' . ($r->anfitrion->apellido_materno ?? '')) : 'N/D') . "</td>";
                        $content .= "<td>{$r->fecha}</td>";
                        $content .= "<td>{$r->hora}</td>";
                        $content .= "<td>" . ($r->cliente ? ($r->cliente->nombre . ' ' . ($r->cliente->apellido_paterno ?? '') . ' ' . ($r->cliente->apellido_materno ?? '')) : 'N/D') . "</td>";
                        $content .= "</tr>";
                    }
                } else {
                    $reservas = $query->get();
                    $groupIds = $reservas->whereNotNull('grupo_reserva_id')->whereNull('sale')->pluck('grupo_reserva_id')->unique();
                    $groupSales = Sale::whereIn('grupo_reserva_id', $groupIds)->get()->keyBy('grupo_reserva_id');

                    $resumen = [];
                    foreach ($reservas as $r) {
                        $expId = $r->experiencia ? $r->experiencia->id : 0;
                        $expName = $r->experiencia ? $r->experiencia->nombre : 'Sin experiencia';

                        $sale = $r->sale ?? $groupSales->get($r->grupo_reserva_id);
                        $monto = 0;
                        if ($sale) {
                            $numReservationsInSale = ($sale->grupo_reserva_id && $r->grupoReserva && $r->grupoReserva->reservaciones->count() > 0) ? $r->grupoReserva->reservaciones->count() : 1;
                            $monto = (floatval($sale->subtotal) + floatval($sale->impuestos)) / $numReservationsInSale;
                        }

                        if (!isset($resumen[$expId])) {
                            $resumen[$expId] = [
                            'nombre' => $expName, 
                            'cantidad' => 0,
                            'monto_acumulado' => 0,
                            ];
                        }
                        $resumen[$expId]['cantidad'] += 1;
                        $resumen[$expId]['monto_acumulado'] += $monto;
                    }

                    // Si hay un término de búsqueda, filtrar el resumen en PHP
                    if ($searchTerm) {
                        $searchTermLower = mb_strtolower($searchTerm, 'UTF-8');
                        $resumen = array_filter($resumen, function ($item) use ($searchTermLower) {
                            return stripos(mb_strtolower($item['nombre'], 'UTF-8'), $searchTermLower) !== false ||
                                   str_contains((string)$item['cantidad'], $searchTermLower) ||
                                   str_contains((string)$item['monto_acumulado'], $searchTermLower);
                        });
                    }

                    $content .= '<th>Servicio</th><th>Cantidad</th><th>Monto total</th></tr></thead><tbody>';
                    foreach ($resumen as $s) {
                            $content .= "<tr>";
                            $content .= "<td>" . htmlspecialchars($s['nombre']) . "</td>";
                            $content .= "<td>" . intval($s['cantidad']) . "</td>";
                            $content .= "<td>$" . number_format($s['monto_acumulado'], 2) . "</td>";
                            $content .= "</tr>";
                    }
                }
                break;

            case 'no_shows':
                $query = Reservation::with('cliente', 'experiencia', 'anfitrion')
                    ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                    ->when($spaId, fn($q) => $q->where('spa_id', $spaId))
                    ->when($servicio, fn($q) => $q->where('experiencia_id', $servicio))
                    ->where('check_in', false)
                    ->where('estado', 'activa');
                
                if ($searchTerm) {
                    $query->where(function ($q) use ($searchTerm) {
                        $q->whereHas('cliente', fn($cq) => $cq->where(DB::raw("LOWER(CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno))"), 'like', "%{$searchTerm}%"))
                          ->orWhereHas('experiencia', fn($eq) => $eq->where(DB::raw('LOWER(nombre)'), 'like', "%{$searchTerm}%"))
                          ->orWhereHas('anfitrion', fn($aq) => $aq->where(DB::raw("LOWER(CONCAT_WS(' ', nombre_usuario, apellido_paterno, apellido_materno))"), 'like', "%{$searchTerm}%"))
                          ->orWhere('fecha', 'like', "%{$searchTerm}%")
                          ->orWhere('hora', 'like', "%{$searchTerm}%");
                    });
                }
                
                $datos = $query->get();

                $content .= '<th>Cliente</th><th>Experiencia</th><th>Terapeuta</th><th>Fecha</th><th>Hora</th></tr></thead><tbody>';
                foreach ($datos as $r) {
                    $content .= "<tr>";
                    $content .= "<td>" . ($r->cliente ? ($r->cliente->nombre . ' ' . ($r->cliente->apellido_paterno ?? '') . ' ' . ($r->cliente->apellido_materno ?? '')) : 'N/D') . "</td>";
                    $content .= "<td>" . ($r->experiencia->nombre ?? 'N/D') . "</td>";
                    $content .= "<td>" . ($r->anfitrion ? ($r->anfitrion->nombre_usuario . ' ' . ($r->anfitrion->apellido_paterno ?? '') . ' ' . ($r->anfitrion->apellido_materno ?? '')) : 'N/D') . "</td>";
                    $content .= "<td>{$r->fecha}</td>";
                    $content .= "<td>{$r->hora}</td>";
                    $content .= "</tr>";
                }
                break;

            case 'anfitriones':
                $activo = $request->input('activo', null);
                $q = Anfitrion::query();
                if (!is_null($activo)) {
                    $q->where('activo', $activo);
                }
                if ($searchTerm) {
                    $q->where(function($query) use ($searchTerm) {
                        $query->where(DB::raw("LOWER(CONCAT_WS(' ', nombre_usuario, apellido_paterno, apellido_materno))"), 'like', "%{$searchTerm}%");

                        if (in_array($searchTerm, ['sí', 'si', 'activo'])) {
                            $query->orWhere('activo', true);
                        } elseif (in_array($searchTerm, ['no', 'inactivo'])) {
                            $query->orWhere('activo', false);
                        }
                    });
                }
                $anfitriones = $q->with('operativo', 'horario')
                    ->whereHas('operativo', function ($query) {
                        $query->where('departamento', 'SPA');
                    })
                    ->when($spaId, fn($q) => $q->where('spa_id', $spaId))
                    ->get();

                $content .= '<th>Nombre</th><th>Apellido Paterno</th><th>Apellido Materno</th><th>Activo</th></tr></thead><tbody>';
                foreach ($anfitriones as $a) {
                    $content .= "<tr>";
                    $content .= "<td>{$a->nombre_usuario}</td>";
                    $content .= "<td>" . ($a->apellido_paterno ?? '') . "</td>";
                    $content .= "<td>" . ($a->apellido_materno ?? '') . "</td>";
                    $content .= "<td>" . ($a->activo ? 'Sí' : 'No') . "</td>";
                    $content .= "</tr>";
                }
                break;

            case 'detalle_ventas':
                $turno = $request->input('turno');

                $ventasQuery = Sale::with(['reservacion.experiencia', 'reservacion.anfitrion', 'cliente', 'grupoReserva.reservaciones.anfitrion'])
                    ->where(function ($query) use ($fechaInicio, $fechaFin) {
                        $query->whereHas('reservacion', function ($q) use ($fechaInicio, $fechaFin) {
                            $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
                        })->orWhereHas('grupoReserva', function ($q) use ($fechaInicio, $fechaFin) {
                            $q->whereHas('reservaciones', function ($rq) use ($fechaInicio, $fechaFin) {
                                $rq->whereBetween('fecha', [$fechaInicio, $fechaFin]);
                            });
                        });
                    })
                    ->when($spaId, fn($q) => $q->where('spa_id', $spaId))
                    ->when($servicio, function ($query) use ($servicio) {
                        $query->where(function ($q) use ($servicio) {
                            $q->whereHas('reservacion', fn($rq) => $rq->where('experiencia_id', $servicio))
                            ->orWhereHas('grupoReserva', fn($grq) => $grq->whereHas('reservaciones', fn($r) => $r->where('experiencia_id', $servicio)));
                        });
                    })
                    ->when($turno, function ($q) use ($turno) {
                        $timeRanges = [
                            'manana' => ['06:00:00', '11:59:59'],
                            'tarde' => ['12:00:00', '17:59:59'],
                            'noche' => ['18:00:00', '23:59:59'],
                        ];

                        if (isset($timeRanges[$turno])) {
                            $startTime = $timeRanges[$turno][0];
                            $endTime = $timeRanges[$turno][1];

                            $q->where(function ($subQ) use ($startTime, $endTime) {
                                $subQ->whereHas('reservacion', function ($resQ) use ($startTime, $endTime) {
                                    $resQ->whereBetween('hora', [$startTime, $endTime]);
                                })
                                ->orWhereHas('grupoReserva', function ($groupQ) use ($startTime, $endTime) {
                                    $groupQ->whereHas('reservaciones', function ($resInGroupQ) use ($startTime, $endTime) {
                                        $resInGroupQ->whereBetween('hora', [$startTime, $endTime]);
                                    });
                                });
                            });
                        }
                    });

                if ($searchTerm) {
                    $ventasQuery->where(function ($query) use ($searchTerm) {
                        $query->whereHas('reservacion.anfitrion', fn($aq) => $aq->where(DB::raw("LOWER(CONCAT_WS(' ', nombre_usuario, apellido_paterno, apellido_materno))"), 'like', "%{$searchTerm}%"))
                              ->orWhereHas('grupoReserva.reservaciones.anfitrion', fn($aq) => $aq->where(DB::raw("LOWER(CONCAT_WS(' ', nombre_usuario, apellido_paterno, apellido_materno))"), 'like', "%{$searchTerm}%"))
                              ->orWhereHas('reservacion.experiencia', fn($eq) => $eq->where(DB::raw('LOWER(nombre)'), 'like', "%{$searchTerm}%"))
                              ->orWhereHas('grupoReserva.reservaciones.experiencia', fn($eq) => $eq->where(DB::raw('LOWER(nombre)'), 'like', "%{$searchTerm}%"))
                              ->orWhereHas('cliente', fn($cq) => 
                                  $cq->where(DB::raw("LOWER(CONCAT_WS(' ', nombre, apellido_paterno, apellido_materno))"), 'like', "%{$searchTerm}%")
                              )
                              ->orWhere(DB::raw("CAST(subtotal AS CHAR)"), 'like', "%{$searchTerm}%")
                              ->orWhere(DB::raw("CAST(impuestos AS CHAR)"), 'like', "%{$searchTerm}%")
                              ->orWhere(DB::raw("CAST(propina AS CHAR)"), 'like', "%{$searchTerm}%")
                              ->orWhere(DB::raw("CAST(total AS CHAR)"), 'like', "%{$searchTerm}%")
                              ->orWhereHas('reservacion', function ($q) use ($searchTerm) {
                                  $q->where('fecha', 'like', "%{$searchTerm}%");
                              })
                              ->orWhereHas('grupoReserva.reservaciones', function ($q) use ($searchTerm) {
                                  $q->where('fecha', 'like', "%{$searchTerm}%");
                              });
                    });
                }
                
                $ventasExp = $ventasQuery->get();

                $content .= '<th>Fecha</th><th>Tipo</th><th>Vendedor</th><th>Subtotal</th><th>IVA</th><th>Propina</th><th>Total</th></tr></thead><tbody>';

                foreach ($ventasExp as $v) {
                    $reservacion = $v->reservacion;
                    if (!$reservacion && $v->grupo_reserva_id && $v->grupoReserva->reservaciones->isNotEmpty()) {
                        $reservacion = $v->grupoReserva->reservaciones->firstWhere('es_principal', true)
                            ?? $v->grupoReserva->reservaciones->sortBy('fecha')->sortBy('hora')->first();
                    }

                    $fecha = $reservacion ? $reservacion->fecha : $v->created_at->format('Y-m-d');
                    $vendedor = $reservacion && $reservacion->anfitrion ? ($reservacion->anfitrion->nombre_usuario . ' ' . ($reservacion->anfitrion->apellido_paterno ?? '') . ' ' . ($reservacion->anfitrion->apellido_materno ?? '')) : 'N/D';
                    $tipoVenta = $reservacion && $reservacion->experiencia ? $reservacion->experiencia->nombre : 'Experiencia';

                    $content .= "<tr>";
                    $content .= "<td>{$fecha}</td>";
                    $content .= "<td>" . htmlspecialchars($tipoVenta) . "</td>";
                    $content .= "<td>" . htmlspecialchars($vendedor) . "</td>";
                    $content .= "<td>$" . number_format(floatval($v->subtotal ?? 0), 2) . "</td>";
                    $content .= "<td>$" . number_format(floatval($v->impuestos ?? 0), 2) . "</td>";
                    $content .= "<td>$" . number_format(floatval($v->propina ?? 0), 2) . "</td>";
                    $content .= "<td>$" . number_format(floatval($v->total ?? 0), 2) . "</td>";
                    $content .= "</tr>";
                }
                break;

            default:
                abort(404);
        }

        $content .= '</tbody></table>';

        return response($content, 200, [
            "Content-type" => "application/vnd.ms-excel",
            "Content-Disposition" => "attachment; filename=$filename",
            "Pragma" => "no-cache",
            "Expires" => "0",
        ]);
    }
}
