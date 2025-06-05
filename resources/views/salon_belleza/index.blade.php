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
        @vite('resources/css/menus/' . $spaCss . '/menu_styles.css')
        @vite('resources/css/general_styles.css')
        @vite('resources/css/boutique/boutique_reporteo_styles.css')
        @vite('resources/css/componentes/autoComplete.css')
        @vite('resources/css/componentes/modal.css')
    @endif
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
@endsection

@section('decorativo')
    @php
        $spasFolder = session('current_spa') ?? strtolower(optional(Auth::user()->spa)->nombre);
        $linDecorativa = asset("images/$spasFolder/decorativo.png");
    @endphp
    <div class="sidebar-decoration" style="background-image: url('{{ $linDecorativa }}');"></div>
@endsection

@section('content')
    <div class="main-container">
        <div class="formulario">
            <h2>Salón de Belleza</h2>
        </div>

        <!-- Tabla de inventario -->
        <div class="table-margin">
            <table class="table" id="tabla-pago">
                <thead>
                    <tr>
                        <th>Etiqueta</th>
                        <th>Nombre</th>
                        <th>Precio/Unidad</th>
                        <th>Cantidad</th>
                        <th>Fecha de Vencimiento</th>
                        <th>Descripción</th>
                        <th></th> <!-- Botones de acción -->
                    </tr>
                </thead>
                <tbody>
                    
                </tbody>
            </table>
        </div>
    </div>
@endsection
