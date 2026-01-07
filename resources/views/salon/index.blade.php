@extends('layouts.spa_menu')

@section('logo_img')
@php
    // Carpeta del spa activa para cargar logo dinámico
    $spasFolder = session('current_spa') ?? strtolower(optional(Auth::user()->spa)->nombre);
@endphp
<img src="{{ asset("images/$spasFolder/logo.png") }}" alt="Logo de {{ ucfirst($spasFolder) }}">
@endsection
@section('css')
    @php
        $spaCss = session('current_spa') ?? strtolower(optional(Auth::user()->spa)->nombre);
    @endphp
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite('resources/css/menus/' . $spaCss . '/menu_styles.css')
        @vite('resources/css/general_styles.css')
        @vite('resources/css/salon/salon.css')
        @vite('resources/css/componentes/autoComplete.css')
        @vite('resources/css/componentes/selectDropdown.css')
        @vite('resources/css/componentes/modal.css')
        @vite('resources/css/componentes/tooltip.css')
    @endif
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
@endsection

@section('content')
<div class="main-container">
  <div class="header">
    <h2 class="header-title">Reporte del Salón de Belleza</h2>
  </div>
        

    {{--  Filtro por fecha --}}
    <form method="GET" action="{{ route('salon.index') }}" class="filters-container">
        <div class="filter">
            <label for="fecha_inicio">Desde</label>
            <input type="date" id="fecha_inicio" name="fecha_inicio" value="{{ $fechaInicio }}">
        </div>
        <div class="filter">
            <label for="fecha_fin">Hasta</label>
            <input type="date" id="fecha_fin" name="fecha_fin" value="{{ $fechaFin }}">
        </div>
       <div class="filter filter-button">
          <button type="submit" class="btn">Filtrar</button>
       </div>
    </form>

    {{--  Tarjetas de resumen --}}
    <div class="cards-container">
        <div class="card">
            <div class="card-header">Total Reservaciones</div>
            <div class="card-body">
                <span>{{ $totales['reservaciones_totales'] }}</span>
            </div>
            
        </div>
        <div class="card">
            <div class="card-header">Pagadas</div>
            <div class="card-body">
                <span class="text-success fw-bold">{{ $totales['reservaciones_pagadas'] }}</span>
            </div>
        </div>
        <div class="card">
            <div class="card-header">No Pagadas</div>
            <div class="card-body">
                <span class="text-danger fw-bold">{{ $totales['reservaciones_no_pagadas'] }}</span>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Promedio Diario</div>
            <div class="card-body">
                <span>{{ $promedios['promedio_diario'] }}</span>
            </div>
        </div>
    </div>
    

    {{--  Tabla principal --}}
    <div class="table-responsive">
        <table class="table table-striped table-bordered align-middle">
            <thead class="table-dark">
                <tr>
                    <th>Fecha</th>
                    <th>Día</th>
                    <th>Total Reservas</th>
                    <th>Pagadas</th>
                    <th>No Pagadas</th>
                    <th>Mañana (8-12)</th>
                    <th>Mediodía (12-16)</th>
                    <th>Tarde (16-20)</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($reporteDias as $r)
                    <tr>
                        <td>{{ $r['fecha'] }}</td>
                        <td>{{ ucfirst($r['dia_semana']) }}</td>
                        <td>{{ $r['total'] }}</td>
                        <td class="text-success fw-bold">{{ $r['pagadas'] }}</td>
                        <td class="text-danger fw-bold">{{ $r['no_pagadas'] }}</td>
                        <td>{{ $r['manana'] }}</td>
                        <td>{{ $r['medio_dia'] }}</td>
                        <td>{{ $r['tarde'] }}</td>
                    </tr>
                @empty
                    <tr><td colspan="8" class="text-center">No hay reservaciones en este rango de fechas.</td></tr>
                @endforelse
            </tbody>
        </table>
    </div>
    <div class="cards-container mt-4"> {{-- Agregamos un pequeño margen superior (mt-4) para separarlas de la tabla --}}
        <div class="card">
            <div class="card-header">Ventas Totales</div>
            <div class="card-body">
                <span>${{ number_format($totales['ventas_total'], 2) }}</span>
            </div>
        </div>
        <div class="card">
            <div class="card-header">Total Propinas</div>
            <div class="card-body">
                <span>${{ number_format($totales['ventas_propina'], 2) }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
