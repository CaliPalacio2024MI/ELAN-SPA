<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <meta http-equiv="X-UA-Compatible" content="ie=edge" />
    @php
        $spaCss = session('current_spa') ?? strtolower(optional(Auth::user()->spa)->nombre);
    @endphp
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        {{-- Estilos específicos para check out --}}
        @vite(['resources/css/sabana_reservaciones/check_out.css'])
    @endif
    {{-- Font Awesome y Google Fonts --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;600;700&display=swap" rel="stylesheet" />
    <title>ELAN SPA & WELLNESS EXPERIENCE</title>
</head>
<body>

<h1 class="mb-4">Check-out de Reservación</h1>

<form action="{{ $sale ? route('sales.update', $sale->id) : route('sales.store') }}" method="POST">
    @csrf
    @if ($sale)
        @method('PUT')
    @endif

    {{-- Campos ocultos con datos esenciales de la reservación --}}
    <input type="hidden" name="reservacion_id" value="{{ $reservation->id }}" />
    <input type="hidden" name="spa_id" value="{{ $reservation->spa_id }}" />
    <input type="hidden" name="anfitrion_id" value="{{ $reservation->anfitrion_id }}" />
    <input type="hidden" name="fecha" value="{{ $reservation->fecha }}" />
    <input type="hidden" name="hora" value="{{ $reservation->hora }}" />
    <input type="hidden" name="grupo_reserva_id" value="{{ $reservation->grupo_reserva_id }}" />
    <input type="hidden" name="cliente_id" value="{{ $reservation->cliente_id }}" />

    @php
        // Cliente principal y grupo de reservaciones para resumen
        $grupo = $reservation->grupoReserva;
        $clientePrincipal = null;
        $reservacionesGrupo = collect();

        if ($grupo) {
            $reservacionesActivas = $grupo->reservaciones->where('estado', 'activa');
            $clientePrincipalReserva = $reservacionesActivas->firstWhere('es_principal', true);
            if ($clientePrincipalReserva) {
                $clientePrincipal = $clientePrincipalReserva->cliente;
            } else {
                // En caso no haya principal activo, tomar la primera activa
                $clientePrincipal = $reservacionesActivas->first()?->cliente;
            }
            $reservacionesGrupo = $reservacionesActivas;
        } else {
            if ($reservation->estado === 'activa') {
                $clientePrincipal = $reservation->cliente;
                $reservacionesGrupo = collect([$reservation]);
            }
        }

    @endphp

    <div class="detalle-row">
        <section>
            <div class="detalle-box">
                <h3>Cliente principal</h3>
                <div>
                    <strong>Nombre:</strong> {{ $clientePrincipal->nombre }} {{ $clientePrincipal->apellido_paterno }}<br />
                    <strong>Correo:</strong> {{ $clientePrincipal->correo }}<br />
                    <strong>Tipo de visita:</strong> {{ ucfirst($clientePrincipal->tipo_visita) }}
                </div>
            </div>
        </section>

        <section>
            <div class="detalle-box">
                <h3>Detalles de reservación</h3>
                <div>
                    <strong>Fecha:</strong> {{ $reservation->fecha }}<br />
                    <strong>Hora:</strong> {{ $reservation->hora }}<br />
                    <strong>Experiencia:</strong> {{ $reservation->experiencia->nombre }}<br />
                    <strong>Anfitrión:</strong> {{ $reservation->anfitrion->nombre_usuario }}
                </div>
            </div>
        </section>
    </div>

    <section>
        <h3>Detalle de cargos</h3>
        <div class="checkout-table-container">
            <table class="checkout-table">
                <thead>
                    <tr>
                        <th>Cliente</th>
                        <th>Experiencia</th>
                        <th>Minutos</th>
                        <th>Neto</th>
                        <th>IVA (16%)</th>
                        <th>Servicio (15%)</th>
                        <th>Total</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $subtotal = 0;
                        $iva = 0;
                        $servicio = 0;
                        $total = 0;
                    @endphp
                    @foreach ($reservacionesGrupo as $res)
                        @php
                            $precio = $res->experiencia->precio;
                            $ivaActual = $precio * 0.16;
                            $servicioActual = $precio * 0.15;
                            $totalActual = $precio + $ivaActual + $servicioActual;

                            $subtotal += $precio;
                            $iva += $ivaActual;
                            $servicio += $servicioActual;
                            $total += $totalActual;
                        @endphp
                        <tr>
                            <td>{{ $res->cliente->nombre }} {{ $res->cliente->apellido_paterno }}</td>
                            <td>{{ $res->experiencia->nombre }}</td>
                            <td>{{ $res->experiencia->duracion }}</td>
                            <td>${{ number_format($precio, 2) }}</td>
                            <td>${{ number_format($ivaActual, 2) }}</td>
                            <td>${{ number_format($servicioActual, 2) }}</td>
                            <td><strong>${{ number_format($totalActual, 2) }}</strong></td>
                        </tr>
                    @endforeach
                </tbody>
                <tfoot>
                    <tr>
                        <td colspan="3" style="text-align:right;"><strong>TOTAL</strong></td>
                        <td>${{ number_format($subtotal, 2) }}</td>
                        <td>${{ number_format($iva, 2) }}</td>
                        <td>${{ number_format($servicio, 2) }}</td>
                        <td><strong>${{ number_format($total, 2) }}</strong></td>
                    </tr>
                </tfoot>
            </table>
        </div>
    </section>

    {{-- Campos ocultos con totales para enviar --}}
    <input type="hidden" name="grupo_reserva_id" value="{{ $reservation->grupo_reserva_id }}" />
    <input type="hidden" name="cliente_id" value="{{ $clientePrincipal->id }}" />
    <input type="hidden" name="spa_id" value="{{ $reservation->spa_id }}" />
    <input type="hidden" name="subtotal" value="{{ $subtotal }}" />
    <input type="hidden" name="impuestos" value="{{ $iva }}" />
    <input type="hidden" name="cargo_experiencia" value="{{ $subtotal }}" />
    <input type="hidden" name="total" value="{{ $total }}" />

    <section>
        <label for="propina">Propina</label>
        <input
            type="number"
            name="propina"
            step="0.01"
            value="{{ old('propina', $sale->propina ?? '') }}"
            placeholder="Ingrese propina si aplica"
        />
    </section>

    <section>
        <label for="forma_pago">Forma de pago</label>
        @php $fp = old('forma_pago', $sale->forma_pago ?? '') @endphp
        <select name="forma_pago" id="formaPagoSelect">
            <option value="">Seleccione</option>
            <option value="efectivo" {{ $fp == 'efectivo' ? 'selected' : '' }}>Efectivo</option>
            <option value="tarjeta_debito" {{ $fp == 'tarjeta_debito' ? 'selected' : '' }}>Tarjeta Débito</option>
            <option value="tarjeta_credito" {{ $fp == 'tarjeta_credito' ? 'selected' : '' }}>Tarjeta Crédito</option>
            <option value="habitacion" {{ $fp == 'habitacion' ? 'selected' : '' }}>Cargo a habitación</option>
            <option value="recepcion" {{ $fp == 'recepcion' ? 'selected' : '' }}>Pago en recepción</option>
            <option value="transferencia" {{ $fp == 'transferencia' ? 'selected' : '' }}>Transferencia</option>
            <option value="otro" {{ $fp == 'otro' ? 'selected' : '' }}>Otro</option>
        </select>
    </section>

    <section>
        <label id="labelReferencia">Referencia del pago</label>
        <input
            type="text"
            name="referencia_pago"
            value="{{ old('referencia_pago', $sale->referencia_pago ?? '') }}"
            placeholder="Número de habitación, voucher, etc."
        />
    </section>

    <div class="btn-guardar">
        <button type="submit" class="btn-cobro">Marcar como cobrado</button>
        <a href="{{ route('reservations.index') }}" class="btn-cobro-cancelar">Cancelar</a>
    </div>
</form>

<script>
    // Cambia etiqueta de referencia según forma de pago
    document.getElementById('formaPagoSelect').addEventListener('change', function () {
        const label = document.getElementById('labelReferencia');
        const value = this.value;

        const opciones = {
            tarjeta_credito: 'Boucher de tarjeta',
            tarjeta_debito: 'Boucher de tarjeta',
            habitacion: 'Número de habitación',
            recepcion: 'Folio a cargo a misceláneo',
            transferencia: 'Folio/ID de transferencia',
            otro: 'Descripción de la referencia',
        };

        label.textContent = opciones[value] || 'Referencia del pago';
    });
</script>

</body>
</html>
