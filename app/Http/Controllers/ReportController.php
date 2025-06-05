<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\GrupoReserva;
use App\Models\BlockedSlot;
use App\Models\Client;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReportController extends Controller
{
    public function index(Request $request)
    {
        $fechaInicio = $request->input('desde') ?? now()->startOfMonth()->toDateString();
        $fechaFin = $request->input('hasta') ?? now()->endOfMonth()->toDateString();

        $totales = [
            'reservaciones_activas' => Reservation::where('estado', 'activa')
                ->whereBetween('fecha', [$fechaInicio, $fechaFin])->count(),

            'reservaciones_canceladas' => Reservation::where('estado', 'cancelada')
                ->whereBetween('fecha', [$fechaInicio, $fechaFin])->count(),

            'check_in' => Reservation::where('check_in', true)
                ->whereBetween('fecha', [$fechaInicio, $fechaFin])->count(),

            'check_out' => Reservation::where('check_out', true)
                ->whereBetween('fecha', [$fechaInicio, $fechaFin])->count(),

            'clientes_atendidos' => Client::whereHas('reservaciones', function ($q) use ($fechaInicio, $fechaFin) {
                $q->whereBetween('fecha', [$fechaInicio, $fechaFin]);
            })->distinct('clients.id')->count(),

            'grupos' => GrupoReserva::whereBetween('created_at', [$fechaInicio, $fechaFin])->count(),

            'bloqueos' => BlockedSlot::whereBetween('fecha', [$fechaInicio, $fechaFin])->count(),

            'ventas_total' => Sale::whereBetween('created_at', [$fechaInicio, $fechaFin])->sum('total'),

            'ventas_propina' => Sale::whereBetween('created_at', [$fechaInicio, $fechaFin])->sum('propina'),
        ];

        $gananciasPorDia = Sale::selectRaw('DATE(created_at) as fecha, SUM(total) as total')
            ->whereBetween('created_at', [$fechaInicio, $fechaFin])
            ->groupBy('fecha')
            ->orderBy('fecha', 'desc')
            ->limit(7)
            ->get()
            ->reverse();

        $topExperiencias = Reservation::select('experiencia_id', DB::raw('count(*) as total'))
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->where('estado', 'activa')
            ->groupBy('experiencia_id')
            ->with('experiencia')
            ->orderByDesc('total')
            ->limit(5)
            ->get();

        $diasFrecuentes = Reservation::select('fecha', DB::raw('count(*) as total'))
            ->whereBetween('fecha', [$fechaInicio, $fechaFin])
            ->where('estado', 'activa')
            ->groupBy('fecha')
            ->orderByDesc('total')
            ->limit(5)
            ->get();




        return view('reservations.reports.index', compact('totales', 'topExperiencias', 'diasFrecuentes', 'fechaInicio', 'fechaFin', 'gananciasPorDia'));
    }

    public function export(Request $request)
{
    $fechaInicio = $request->input('desde') ?? now()->startOfMonth()->toDateString();
    $fechaFin = $request->input('hasta') ?? now()->endOfMonth()->toDateString();

    $reservaciones = Reservation::with('cliente', 'experiencia')
        ->whereBetween('fecha', [$fechaInicio, $fechaFin])
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
                <td>' . htmlspecialchars($reserva->cliente->nombre ?? 'N/D') . '</td>
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

    $datos = [];
    $filename = "reporte_" . $tipo . "_" . now()->format('Ymd_His') . ".xls";
    $content = '<table border="1"><thead><tr>';

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
            $datos = Reservation::with('cliente', 'experiencia')
                ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                ->where($estadoField[0], $estadoField[1])
                ->get();

            $content .= '<th>Cliente</th><th>Experiencia</th><th>Fecha</th><th>Hora</th><th>Estado</th></tr></thead><tbody>';
            foreach ($datos as $r) {
                $cliente = $r->cliente;
                $nombreCompleto = $cliente ? $cliente->nombre . ' ' . $cliente->apellido_paterno . ' ' . $cliente->apellido_materno : '-';
                $content .= "<tr>
                    <td>{$nombreCompleto}</td>
                    <td>" . ($r->experiencia->nombre ?? '-') . "</td>
                    <td>{$r->fecha}</td>
                    <td>{$r->hora}</td>
                    <td>{$r->estado}</td>
                </tr>";
            }
            break;

        case 'clientes':
            $datos = Client::whereHas('reservaciones', fn($q) =>
                $q->whereBetween('fecha', [$fechaInicio, $fechaFin])
            )->get();

            $content .= '<th>Nombre</th><th>Apellido Paterno</th><th>Apellido Materno</th><th>Teléfono</th><th>Tipo Visita</th></tr></thead><tbody>';
            foreach ($datos as $c) {
                $content .= "<tr>
                    <td>{$c->nombre}</td>
                    <td>{$c->apellido_paterno}</td>
                    <td>{$c->apellido_materno}</td>
                    <td>{$c->telefono}</td>
                    <td>{$c->tipo_visita}</td>
                </tr>";
            }
            break;

        case 'bloqueos':
            $datos = BlockedSlot::whereBetween('fecha', [$fechaInicio, $fechaFin])->get();
            $content .= '<th>Fecha</th><th>Hora</th><th>Duración</th><th>Motivo</th></tr></thead><tbody>';
            foreach ($datos as $b) {
                $content .= "<tr>
                    <td>{$b->fecha}</td>
                    <td>{$b->hora}</td>
                    <td>{$b->duracion}</td>
                    <td>{$b->motivo}</td>
                </tr>";
            }
            break;

        case 'grupos':
            $datos = GrupoReserva::withCount('reservaciones')
                ->whereBetween('created_at', [$fechaInicio, $fechaFin])
                ->having('reservaciones_count', '>=', 2)
                ->get();

            $content .= '<th>ID</th><th>Fecha de creación</th><th>Reservaciones</th></tr></thead><tbody>';
            foreach ($datos as $g) {
                $content .= "<tr>
                    <td>{$g->id}</td>
                    <td>{$g->created_at->format('d/m/Y')}</td>
                    <td>{$g->reservaciones_count}</td>
                </tr>";
            }
            break;

        case 'experiencias':
            $datos = Reservation::select('experiencia_id', DB::raw('count(*) as total'))
                ->whereBetween('fecha', [$fechaInicio, $fechaFin])
                ->where('estado', 'activa')
                ->groupBy('experiencia_id')
                ->with('experiencia')
                ->get();

            $content .= '<th>Experiencia</th><th>Total</th></tr></thead><tbody>';
            foreach ($datos as $e) {
                $content .= "<tr>
                    <td>" . ($e->experiencia->nombre ?? 'N/D') . "</td>
                    <td>{$e->total}</td>
                </tr>";
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
