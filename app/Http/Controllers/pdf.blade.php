<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Factura {{ $invoice->id }}</title>
    <style>
        body { font-family: 'Helvetica', sans-serif; font-size: 12px; color: #333; }
        .container { width: 100%; margin: 0 auto; }
        .header, .footer { text-align: center; }
        .header h1 { margin: 0; }
        .header p { margin: 5px 0; }
        .content { margin-top: 20px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th, .table td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        .table th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .total-section { margin-top: 20px; float: right; width: 40%; }
        .total-section table { width: 100%; }
        .total-section td { padding: 5px; }
        .section-title { font-size: 14px; font-weight: bold; margin-top: 20px; margin-bottom: 10px; border-bottom: 1px solid #333; padding-bottom: 5px;}
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>ELAN SPA & WELLNESS EXPERIENCE</h1>
            <p>Factura</p>
            <p>Folio Venta: {{ $sale->id }} | Fecha: {{ $sale->created_at->format('d/m/Y') }}</p>
        </div>

        <div class="content">
            <div class="section-title">Datos del Cliente</div>
            <p><strong>Razón Social:</strong> {{ $invoice->razon_social }}</p>
            <p><strong>RFC:</strong> {{ $invoice->rfc }}</p>
            <p><strong>Dirección Fiscal:</strong> {{ $invoice->direccion_fiscal }}</p>
            <p><strong>Correo:</strong> {{ $invoice->correo_factura }}</p>

            <div class="section-title">Detalle de la Venta</div>
            <table class="table">
                <thead>
                    <tr>
                        <th>Descripción</th>
                        <th class="text-right">Precio Unitario</th>
                        <th class="text-right">Cantidad</th>
                        <th class="text-right">Importe</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $reservations = $sale->grupo_reserva_id
                            ? $sale->grupoReserva->reservaciones()->where('estado', 'activa')->with('experiencia')->get()
                            : collect([$sale->reservacion()->with('experiencia')->first()]);
                    @endphp
                    @foreach($reservations as $res)
                        @if($res && $res->experiencia)
                        <tr>
                            <td>Servicio: {{ $res->experiencia->nombre }}</td>
                            <td class="text-right">${{ number_format($res->experiencia->precio, 2) }}</td>
                            <td class="text-right">1</td>
                            <td class="text-right">${{ number_format($res->experiencia->precio, 2) }}</td>
                        </tr>
                        @endif
                    @endforeach
                </tbody>
            </table>

            <div class="total-section">
                <table>
                    <tr>
                        <td>Subtotal:</td>
                        <td class="text-right">${{ number_format($sale->subtotal, 2) }}</td>
                    </tr>
                    <tr>
                        <td>Impuestos (IVA + Servicio):</td>
                        <td class="text-right">${{ number_format($sale->impuestos, 2) }}</td>
                    </tr>
                    @if($sale->propina > 0)
                    <tr>
                        <td>Propina:</td>
                        <td class="text-right">${{ number_format($sale->propina, 2) }}</td>
                    </tr>
                    @endif
                    <tr>
                        <td><strong>Total:</strong></td>
                        <td class="text-right"><strong>${{ number_format($sale->total, 2) }}</strong></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</body>
</html>