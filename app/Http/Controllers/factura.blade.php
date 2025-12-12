<!DOCTYPE html>
<html>
<head>
    <title>Factura de Compra</title>
</head>
<body>
    <h1>Gracias por su compra</h1>
    <p>Estimado/a {{ $invoice->razon_social }},</p>
    <p>Adjunto encontrará la factura correspondiente a su reciente compra con folio de venta <strong>{{ $sale->id }}</strong>.</p>
    <p>Gracias por su preferencia.</p>
    <p>Atentamente,<br>ELAN SPA & WELLNESS EXPERIENCE</p>
</body>
</html>