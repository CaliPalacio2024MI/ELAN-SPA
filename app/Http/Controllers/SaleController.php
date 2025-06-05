<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\GrupoReserva;
use App\Models\Client;
use App\Models\Sale;

class SaleController extends Controller
{
    // Mostrar pantalla de cobro (checkout) para una reservación
    public function checkout(Reservation $reservation)
    {
        $sale = null;

        // Cargar solo reservaciones activas del grupo
        $reservation->load([
            'grupoReserva.reservaciones' => function ($query) {
                $query->where('estado', 'activa')->with('cliente', 'experiencia');
            },
            'cliente',
            'experiencia',
            'anfitrion'
        ]);

        if ($reservation->check_out) {
            $sale = $reservation->grupoReserva
                ? Sale::where('grupo_reserva_id', $reservation->grupo_reserva_id)->first()
                : Sale::where('reservacion_id', $reservation->id)->first();
        }

        return view('reservations.sales.checkout', compact('reservation', 'sale'));
    }

    // Registrar el pago de una reservación o grupo de reservaciones
    public function store(Request $request)
    {
        $request->validate([
            'spa_id' => 'required|exists:spas,id',
            'cliente_id' => 'required|exists:clients,id',
            'grupo_reserva_id' => 'nullable|exists:grupo_reservas,id',
            'subtotal' => 'required|numeric|min:0',
            'impuestos' => 'required|numeric|min:0',
            'propina' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'forma_pago' => 'required|string',
            'referencia_pago' => 'nullable|string|max:255',
        ]);

        $reservation = Reservation::with('grupoReserva.sale')->findOrFail($request->reservacion_id);

        if ($reservation->check_out || ($reservation->grupoReserva && $reservation->grupoReserva->sale)) {
            return redirect()->route('reservations.index')->with('error', 'Esta reservación o su grupo ya fue cobrado.');
        }

        $reservaciones = $request->grupo_reserva_id
            ? Reservation::where('grupo_reserva_id', $request->grupo_reserva_id)->with('experiencia')->get()
            : collect([Reservation::with('experiencia')->findOrFail($request->reservacion_id)]);

        $subtotal = 0;
        $iva = 0;
        $servicio = 0;
        $total = 0;

        foreach ($reservaciones as $res) {
            $precio = $res->experiencia->precio;
            $ivaActual = $precio * 0.16;
            $servicioActual = $precio * 0.15;
            $totalActual = $precio + $ivaActual + $servicioActual;

            $subtotal += $precio;
            $iva += $ivaActual;
            $servicio += $servicioActual;
            $total += $totalActual;
        }

        Sale::create([
            'spa_id' => $request->spa_id,
            'cliente_id' => $request->cliente_id,
            'grupo_reserva_id' => $request->grupo_reserva_id,
            'reservacion_id' => $request->grupo_reserva_id ? null : $request->reservacion_id, // <-- Aquí
            'subtotal' => $subtotal,
            'impuestos' => $iva,
            'propina' => $request->propina ?? 0,
            'total' => $total + ($request->propina ?? 0),
            'forma_pago' => $request->forma_pago,
            'referencia_pago' => $request->referencia_pago,
            'cobrado' => true,
        ]);

        // Marcar reservaciones del grupo como pagadas (check_out)
        if ($request->grupo_reserva_id) {
            Reservation::where('grupo_reserva_id', $request->grupo_reserva_id)->update(['check_out' => true]);
        }

        return redirect()->route('reservations.index')->with('success', 'Pago registrado correctamente.');
    }

    // Actualizar datos de un pago existente
    public function update(Request $request, Sale $sale)
    {
        $request->validate([
            'subtotal' => 'required|numeric|min:0',
            'impuestos' => 'required|numeric|min:0',
            'propina' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'forma_pago' => 'required|string',
            'referencia_pago' => 'nullable|string|max:255',
        ]);

        $sale->update([
            'subtotal' => $request->subtotal,
            'impuestos' => $request->impuestos,
            'propina' => $request->propina ?? 0,
            'total' => $request->total,
            'forma_pago' => $request->forma_pago,
            'referencia_pago' => $request->referencia_pago,
        ]);

        return redirect()->route('reservations.index')->with('success', 'Pago actualizado correctamente.');
    }
}
