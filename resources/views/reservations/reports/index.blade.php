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
            'resources/css/sabana_reservaciones/reporteo.css'
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
<div class="report-container">
    {{-- Formulario de filtro de fechas --}}
    <form method="GET" action="{{ route('reports.index') }}" class="filtro-form">
        <div>
            <label for="desde">Desde</label>
            <input type="date" id="desde" name="desde" value="{{ request('desde', $fechaInicio) }}">
        </div>
        <div>
            <label for="hasta">Hasta</label>
            <input type="date" id="hasta" name="hasta" value="{{ request('hasta', $fechaFin) }}">
        </div>
        <div>
            <button type="submit" class="filtro-btn">
                <i class="fas fa-search"></i> Filtrar
            </button>
        </div>
    </form>

    <h2 class="titulo-reporte">Reporte General</h2>

    {{-- Fila 1: Tres tarjetas principales --}}
    <div class="reporte-grid cols-3">
        @include('reservations.reports.partials.card', [
            'title' => 'Reservaciones activas',
            'value' => $totales['reservaciones_activas'],
            'class' => 'primary',
            'exportRoute' => 'activos'
        ])
        @include('reservations.reports.partials.card', [
            'title' => 'Reservaciones canceladas',
            'value' => $totales['reservaciones_canceladas'],
            'class' => 'danger',
            'exportRoute' => 'cancelados'
        ])
        @include('reservations.reports.partials.card', [
            'title' => 'Check-ins',
            'value' => $totales['check_in'],
            'class' => 'success',
            'exportRoute' => 'checkins'
        ])
    </div>

    {{-- Fila 2: Tarjeta y gráfico --}}
    <div class="reporte-grid cols-2">
        @include('reservations.reports.partials.card', [
            'title' => 'Check-outs',
            'value' => $totales['check_out'],
            'class' => 'info',
            'exportRoute' => 'checkouts'
        ])
        @include('reservations.reports.partials.chart_experiencias')
    </div>

    {{-- Fila 3: Tres tarjetas adicionales --}}
    <div class="reporte-grid cols-3">
        @include('reservations.reports.partials.card', [
            'title' => 'Clientes únicos',
            'value' => $totales['clientes_atendidos'],
            'class' => 'secondary',
            'exportRoute' => 'clientes'
        ])
        @include('reservations.reports.partials.card', [
            'title' => 'Grupos',
            'value' => $totales['grupos'],
            'class' => 'dark',
            'exportRoute' => 'grupos'
        ])
        @include('reservations.reports.partials.card', [
            'title' => 'Bloqueos',
            'value' => $totales['bloqueos'],
            'class' => 'warning',
            'exportRoute' => 'bloqueos'
        ])
    </div>

    {{-- Fila 4: Dos gráficos --}}
    <div class="reporte-grid cols-2">
        @include('reservations.reports.partials.chart_dias')
        @include('reservations.reports.partials.chart_ganancias')
    </div>

    {{-- Fila 5: Dos tarjetas con resumen monetario --}}
    <div class="reporte-grid cols-2">
        @include('reservations.reports.partials.card', [
            'title' => 'Ingresos $',
            'value' => '$' . number_format($totales['ventas_total'], 2),
            'class' => 'success'
        ])
        @include('reservations.reports.partials.card', [
            'title' => 'Propinas $',
            'value' => '$' . number_format($totales['ventas_propina'], 2),
            'class' => 'success'
        ])
    </div>

    {{-- Botón para exportar a Excel (comentado por ahora) --}}
    {{--
    <div class="exportar-btn">
        <a href="{{ route('reports.export', ['desde' => $fechaInicio, 'hasta' => $fechaFin]) }}" class="btn btn-outline-success">
            <i class="fas fa-file-excel"></i> Exportar a Excel
        </a>
    </div>
    --}}
</div>
@endsection
