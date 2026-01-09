<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Reservation;
use App\Models\GrupoReserva;
use App\Models\Client;
use App\Models\Sale;
use App\Models\Invoice;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Log;
use App\Mail\FacturaEnviadaMail;

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
            $sale = Sale::where('reservacion_id', $reservation->id)->first();

            // Para compatibilidad con ventas de grupo antiguas que no tienen reservacion_id
            if (!$sale && $reservation->grupoReserva) {
                $sale = Sale::where('grupo_reserva_id', $reservation->grupo_reserva_id)
                            ->whereNull('reservacion_id')
                            ->first();
            }
        }

        return view('reservations.sales.checkout', compact('reservation', 'sale'));
    }

    // Registrar el pago de una reservación o grupo de reservaciones
    public function store(Request $request)
    {
        $rules = [
            'spa_id' => 'required|exists:spas,id',
            'cliente_id' => 'required|exists:clients,id',
            'grupo_reserva_id' => 'nullable|exists:grupo_reservas,id',
            'subtotal' => 'required|numeric|min:0',
            'impuestos' => 'required|numeric|min:0',
            'propina' => 'nullable|numeric|min:0',
            'total' => 'required|numeric|min:0',
            'forma_pago' => 'required|string',
            'referencia_pago' => 'nullable|string|max:255',
            'reservacion_id' => 'required|exists:reservations,id',
        ];

        // Si se solicita factura, agregar reglas de validación para datos fiscales
        if ($request->boolean('solicita_factura')) {
            $rules = array_merge($rules, [
                'tipo_persona' => 'required|in:fisica,moral',
                'razon_social' => 'required|string|max:255',
                'rfc' => 'required|string|max:50',
                'direccion_fiscal' => 'required|string|max:500',
                'correo_factura' => 'required|email|max:255',
            ]);
        }

        $request->validate($rules);

        $reservation = Reservation::findOrFail($request->reservacion_id);

        if ($reservation->check_out) {
            return redirect()->route('reservations.index')->with('error', 'Esta reservación ya fue cobrada.');
        }

        $reservaciones = collect([$reservation->load('experiencia')]);

        $calculatedAmounts = $this->calculateSaleAmounts($reservaciones);

        $sale = Sale::create([
            'spa_id' => $request->spa_id,
            'cliente_id' => $request->cliente_id,
            'grupo_reserva_id' => $reservation->grupo_reserva_id,
            'reservacion_id' => $reservation->id,
            'subtotal' => $calculatedAmounts['subtotal'],
            'impuestos' => $calculatedAmounts['impuestos'],
            'propina' => $request->propina ?? 0,
            'total' => $calculatedAmounts['total_sin_propina'] + ($request->propina ?? 0),
            'forma_pago' => $request->forma_pago,
            'referencia_pago' => $request->referencia_pago,
            'cobrado' => true,
        ]);

        // Si se solicitó factura, crear registro en invoices
        if ($request->boolean('solicita_factura')) {
            $invoice = Invoice::create([
                'sale_id' => $sale->id,
                'reservacion_id' => $request->reservacion_id,
                'cliente_id' => $request->cliente_id,
                'tipo_persona' => $request->tipo_persona,
                'razon_social' => $request->razon_social,
                'rfc' => $request->rfc,
                'direccion_fiscal' => $request->direccion_fiscal,
                'correo_factura' => $request->correo_factura,
            ]);

            // --- INICIO LÓGICA DE ENVÍO DE FACTURA ---
            try {
                // Cargar relaciones necesarias para el PDF
                $sale->load('grupoReserva.reservaciones.experiencia', 'reservacion.experiencia');

                // Generar el PDF
                $pdf = app('dompdf.wrapper')->loadView('invoices.pdf', compact('sale', 'invoice'));
                $pdfData = $pdf->output();

                // Enviar el correo con el PDF adjunto
                Mail::to($invoice->correo_factura)->send(new FacturaEnviadaMail($sale, $invoice, $pdfData));
            } catch (\Exception $e) {
                // Si el envío falla, no detener el flujo, solo registrar el error.
                Log::error("Error al enviar factura por correo para la venta ID: {$sale->id}. Error: {$e->getMessage()}");
            }
            // --- FIN LÓGICA DE ENVÍO DE FACTURA ---
        }

        // Marcar reservaciones del grupo como pagadas (check_out)
        $reservation->update(['check_out' => true]);

        return redirect()->route('reservations.index')->with('success', 'Pago registrado correctamente.');
    }

    // Actualizar datos de un pago existente
    public function update(Request $request, Sale $sale)
    {
        $rules = [
            'propina' => 'nullable|numeric|min:0',
            'forma_pago' => 'required|string',
            'referencia_pago' => 'nullable|string|max:255',
        ];

        // Si se solicita factura, agregar reglas de validación para datos fiscales
        if ($request->boolean('solicita_factura')) {
            $rules = array_merge($rules, [
                'tipo_persona' => 'required|in:fisica,moral',
                'razon_social' => 'required|string|max:255',
                'rfc' => 'required|string|max:50',
                'direccion_fiscal' => 'required|string|max:500',
                'correo_factura' => 'required|email|max:255',
            ]);
        }

        $request->validate($rules);

        // Recalcular totales para garantizar la integridad de los datos, usando la misma lógica que en store()
        if ($sale->reservacion_id) {
            $reservaciones = collect([$sale->reservacion()->with('experiencia')->first()]);
        } elseif ($sale->grupo_reserva_id) {
            // Mantener lógica para ventas de grupo antiguas
            $reservaciones = Reservation::where('grupo_reserva_id', $sale->grupo_reserva_id)
                                        ->where('estado', 'activa')
                                        ->with('experiencia')->get();
        } else {
            $reservaciones = collect([]);
        }
        $calculatedAmounts = $this->calculateSaleAmounts($reservaciones);

        $sale->update([
            'subtotal' => $calculatedAmounts['subtotal'],
            'impuestos' => $calculatedAmounts['impuestos'],
            'propina' => $request->propina ?? 0,
            'total' => $calculatedAmounts['total_sin_propina'] + ($request->propina ?? 0),
            'forma_pago' => $request->forma_pago,
            'referencia_pago' => $request->referencia_pago,
        ]);

        // Si se solicitó factura, crear o actualizar registro en invoices y enviar correo
        if ($request->boolean('solicita_factura')) {
            $invoice = Invoice::updateOrCreate(
                ['sale_id' => $sale->id], // Condición para buscar
                [ // Datos para crear o actualizar
                    'reservacion_id' => $sale->reservacion_id ?? $request->reservacion_id,
                    'cliente_id' => $sale->cliente_id,
                    'tipo_persona' => $request->tipo_persona,
                    'razon_social' => $request->razon_social,
                    'rfc' => $request->rfc,
                    'direccion_fiscal' => $request->direccion_fiscal,
                    'correo_factura' => $request->correo_factura,
                ]
            );

            // --- INICIO LÓGICA DE ENVÍO DE FACTURA ---
            try {
                // Cargar relaciones necesarias para el PDF
                $sale->load('grupoReserva.reservaciones.experiencia', 'reservacion.experiencia');

                // Generar el PDF
                $pdf = app('dompdf.wrapper')->loadView('invoices.pdf', compact('sale', 'invoice'));
                $pdfData = $pdf->output();

                // Enviar el correo con el PDF adjunto
                Mail::to($invoice->correo_factura)->send(new FacturaEnviadaMail($sale, $invoice, $pdfData));
            } catch (\Exception $e) {
                Log::error("Error al enviar factura (en actualización) por correo para la venta ID: {$sale->id}. Error: {$e->getMessage()}");
            }
            // --- FIN LÓGICA DE ENVÍO DE FACTURA ---
        }

        return redirect()->route('reservations.index')->with('success', 'Pago actualizado correctamente.');
    }

    private function calculateSaleAmounts(iterable $reservations)
    {
        $subtotal_final = 0;
        $total_final = 0;
 
        // Obtener las tasas desde el archivo de configuración.
        $tasaIva = config('finance.tax_rates.iva', 0.16);
        $tasaServicio = config('finance.tax_rates.service_charge', 0.20);
        $divisor = 1 + $tasaIva + $tasaServicio; // e.g., 1 + 0.16 + 0.20 = 1.36

        foreach ($reservations as $res) {
            if (!$res || !$res->experiencia) continue;

            // El precio de la experiencia se considera el total (antes de propina opcional).
            $precioTotalUnitario = $res->experiencia->precio;
            
            // Acumular el total y el subtotal sin redondear para máxima precisión
            $total_final += $precioTotalUnitario;
            $subtotal_final += $precioTotalUnitario / $divisor;
        }

        // Redondear el subtotal final a 2 decimales.
        $subtotal_redondeado = round($subtotal_final, 2);
        
        // Calcular los impuestos como la diferencia para asegurar que la suma sea exacta.
        // El total_final es la suma de los precios, que es el valor de referencia.
        $impuestos_calculados = $total_final - $subtotal_redondeado;

        return [
            'subtotal' => $subtotal_redondeado,
            'impuestos' => $impuestos_calculados,
            'total_sin_propina' => $total_final, // Este es el total real, la suma de precios.
        ];
    }
}
