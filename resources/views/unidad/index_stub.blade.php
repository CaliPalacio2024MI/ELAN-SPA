<!doctype html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Unidades (stub)</title>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css">
</head>
<body class="p-4">
<div class="container">
    <h1>Unidades (modo stub)</h1>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if(count($unidades ?? []) === 0)
        <p>No hay unidades registradas (modo stub).</p>
    @else
        <table class="table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Color</th>
                    <th>Creado</th>
                </tr>
            </thead>
            <tbody>
                @foreach($unidades as $u)
                    <tr>
                        <td>{{ $u['nombre_unidad'] ?? '-' }}</td>
                        <td style="background: {{ $u['color_unidad'] ?? 'transparent' }};">{{ $u['color_unidad'] ?? '-' }}</td>
                        <td>{{ $u['created_at'] ?? '-' }}</td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif

    <a href="{{ url()->previous() }}" class="btn btn-secondary">Volver</a>
</div>
</body>
</html>
