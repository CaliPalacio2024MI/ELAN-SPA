@extends('layouts.spa_menu')

@section('logo_img')
@php
    $spasFolder = session('current_spa') ?? strtolower(optional(Auth::user()->spa)->nombre);
@endphp
<img src="{{ asset("images/$spasFolder/logo.png") }}" alt="Logo de {{ ucfirst($spasFolder) }}">
@endsection

@section('css')
    @php
        $spaCss = session('current_spa') ?? strtolower(optional(Auth::user()->spa)->nombre);
    @endphp
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite([
            'resources/css/menus/' . $spaCss . '/menu_styles.css',
            'resources/css/ModalAviso/modal_aviso.css',
            'resources/css/sabana_reservaciones/historial.css'
        ])
    @endif
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
@endsection

@section('decorativo')
    @php
        $spasFolder = session('current_spa') ?? strtolower(optional(Auth::user()->spa)->nombre);
        $linDecorativa = asset("images/$spasFolder/decorativo.png");
    @endphp
    <div class="sidebar-decoration" style="background-image: url('{{ $linDecorativa }}');"></div>
@endsection

@section('content')
<header class="main-header">
    <h2>HISTORIAL DE PAGOS</h2>
</header>
{{-- Formulario de filtros y rango de fechas (dentro de la misma caja visual que la tabla) --}}
<div class="table-container" style="margin-bottom:1rem;">
    <div class="table-responsive">
        <form method="GET" action="{{ route('reservations.historial') }}" class="filtro-form" style="display:flex;gap:0.75rem;flex-wrap:wrap;align-items:end;padding:0.75rem;">
            <div>
                <label for="desde">Desde</label>
                <input type="date" id="desde" name="desde" value="{{ request('desde') }}">
            </div>
            <div>
                <label for="hasta">Hasta</label>
                <input type="date" id="hasta" name="hasta" value="{{ request('hasta') }}">
            </div>

            <div>
                <label for="cliente">Cliente</label>
                <input type="text" id="cliente" name="cliente" placeholder="Nombre o apellido" value="{{ request('cliente') }}">
            </div>

            <div>
                <label for="experiencia">Experiencia</label>
                <input type="text" id="experiencia" name="experiencia" placeholder="Nombre de experiencia" value="{{ request('experiencia') }}">
            </div>

            <div>
                <label for="cabina">Cabina</label>
                <input type="text" id="cabina" name="cabina" placeholder="Cabina" value="{{ request('cabina') }}">
            </div>

            <div>
                <label for="anfitrion">Anfitrión</label>
                <input type="text" id="anfitrion" name="anfitrion" placeholder="Usuario anfitrión" value="{{ request('anfitrion') }}">
            </div>

            <div>
                <label for="pagado">Estado pago</label>
                <select id="pagado" name="pagado">
                    <option value="">Todos</option>
                    <option value="pagado" {{ request('pagado') == 'pagado' ? 'selected' : '' }}>Pagado</option>
                    <option value="pendiente" {{ request('pagado') == 'pendiente' ? 'selected' : '' }}>Pendiente</option>
                </select>
            </div>

            <div style="display:flex;gap:0.5rem;align-items:center;">
                <button type="submit" class="btn btn-primary">Filtrar</button>
                <a href="{{ route('reports.export.tipo', ['tipo' => 'checkouts', 'desde' => request('desde'), 'hasta' => request('hasta')]) }}" class="btn btn-outline-success">
                    <i class="fas fa-file-excel"></i> Exportar
                </a>
            </div>
        </form>
    </div>
</div>

<div class="table-container">
    <table class="table-responsive custom-table">
        <thead>
            <tr>
                <th>Cliente</th>
                <th>Experiencia</th>
                <th>Cabina</th>
                <th>Anfitrión</th>
                <th>Fecha</th>
                <th>Hora</th>
                <th>Grupo Reserva</th>
                <th>Pagado?</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($reservaciones as $reserva)
                <tr>
                    <td>
                        @if($reserva->cliente)
                            {{ $reserva->cliente->nombre }} {{ $reserva->cliente->apellido_paterno }} {{ $reserva->cliente->apellido_materno }}
                        @else
                            Sin cliente
                        @endif
                    </td>
                    <td>{{ $reserva->experiencia?->nombre ?? '—' }}</td>
                    <td>{{ $reserva->cabina?->nombre ?? '—' }}</td>
                    <td>{{ $reserva->anfitrion?->nombre_usuario ?? '—' }}</td>
                    <td>{{ \Carbon\Carbon::parse($reserva->fecha)->format('d/m/Y') }}</td>
                    <td>{{ $reserva->hora }}</td>
                    <td>{{ $reserva->grupo_reserva_id ?? '—' }}</td>
                    <td>
                        @if ($reserva->check_out)
                            <span class="badge bg-success">Pagado</span>
                        @else
                            <span class="badge bg-danger">Pendiente</span>
                        @endif
                    </td>

                    <td class="text-end">
                        <a href="{{ route('sales.checkout', $reserva->id) }}" class="btn btn-info btn-sm">
                            Ver Pago
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection
