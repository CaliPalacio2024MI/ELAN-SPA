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
        @vite('resources/css/menus/themes/' . $spaCss . '.css')
        @vite('resources/css/general_styles.css')
        @vite('resources/css/boutique/boutique_historial_styles.css')
        @vite('resources/css/componentes/autoComplete.css')
        @vite('resources/css/componentes/selectDropdown.css')
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
        <div class="header">
            <a href="{{ route('boutique.venta') }}" class="btn">
                <i class="fa-solid fa-box" style="padding-right: 10px;"></i>Regresar a Ventas
            </a>
            <h2>Historial de Ventas</h2>
            <div></div>
        </div>

        <form method="GET" action="{{ route('boutique.venta.historial') }}" class="filters-container">
            <div class="filter">
                <label for="fecha_inicio">Fecha Inicio</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" value="{{ $fechaInicio }}">
            </div>
            <div class="filter">
                <label for="fecha_fin">Fecha Fin</label>
                <input type="date" id="fecha_fin" name="fecha_fin" value="{{ $fechaFin }}">
            </div>
            <div class="filter" style="justify-content: flex-end;">
                <button type="submit" class="btn" style="align-self: flex-start; margin: 0.25rem 0;">Filtrar</button>
            </div>
            <div class="search-filter">
                <input type="text" id="filtro-articulo" class="search-filter"
                    placeholder="Buscar por folio, número auxiliar, nombre, anfitrión...">
                <button type="button" class="search-filter btn" onclick="limpiar_filtro()">Limpiar</button>
            </div>
        </form>

        <div class="table-margin" style="overflow-x: auto;">
            <table class="table" id="tabla-ventas">
                <thead>
                    <tr>
                        <th>Folio</th>
                        <th>Fecha</th>
                        <th>Forma de Pago</th>
                        <th>Referencia</th>
                        <th>No. Auxiliar</th>
                        <th>Nombre</th>
                        <th>Anfitrión</th>
                        <th>Familia</th>
                        <th>Cantidad</th>
                        <th>Descuento</th>
                        <th>Subtotal</th>
                        <th>Observaciones</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        $ventasAgrupadas = $ventas->groupBy('folio_venta');
                        $contadorFolio = 0;
                    @endphp

                    @forelse($ventasAgrupadas as $folio => $ventasDelFolio)
                        @php
                            $contadorFolio++;
                            $claseColor = $contadorFolio % 2 === 0 ? 'fila-par' : 'fila-impar';
                            $primeraVenta = $ventasDelFolio->first();
                        @endphp

                        @foreach ($ventasDelFolio as $venta)
                            <tr class="fila-articulo {{ $claseColor }}">
                                <td>{{ $venta->folio_venta ?? 'S/F' }}</td>
                                <td style="text-align: center;">
                                    {{ \Carbon\Carbon::parse($venta->fecha_venta)->format('d/m/Y') }}
                                    <br>
                                    {{ \Carbon\Carbon::parse($venta->hora_venta)->format('H:i') }}
                                </td>
                                <td>{{ $venta->forma_pago }}</td>
                                <td>{{ $venta->referencia_pago ?? '-' }}</td>

                                {{-- Datos del artículo (se repiten en cada fila) --}}
                                <td>{{ str_pad($venta->numero_auxiliar, 10, '0', STR_PAD_LEFT) }}</td>
                                <td>{{ $venta->nombre_articulo }}</td>
                                <td>{{ trim($venta->anfitrion_nombre) ?: '-' }}</td>
                                <td>{{ $venta->familia_nombre ?? '-' }}</td>
                                <td style="text-align: center">{{ $venta->cantidad }}</td>
                                <td>{{ $venta->descuento }}%</td>
                                {{-- <td>${{ number_format($venta->subtotal, 2) }}</td> --}}
                                <td>
                                    @if ($venta->descuento > 0)
                                        @php
                                            // Calcular el total original (sin descuento)
                                            $totalOriginal = $venta->subtotal / (1 - $venta->descuento / 100);
                                        @endphp
                                        <span
                                            style="text-decoration: line-through;">${{ number_format($totalOriginal, 2) }}</span>
                                        <span>${{ number_format($venta->subtotal, 2) }}</span>
                                    @else
                                        ${{ number_format($venta->subtotal, 2) }}
                                    @endif
                                </td>
                                <td>{{ $venta->observaciones ?? '-' }}</td>
                            </tr>
                        @endforeach
                    @empty
                        <tr class="fila-articulo">
                            <td colspan="12" style="text-align: center">Sin artículos</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
@endsection

@section('scripts')
    <!-- Lógica de Fechas -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const fechaInicio = document.getElementById('fecha_inicio');
            const fechaFin = document.getElementById('fecha_fin');

            // Obtener fecha actual en zona horaria local (México)
            const hoy = new Date();
            const fechaHoyLocal = new Date(hoy.getTime() - (hoy.getTimezoneOffset() * 60000))
                .toISOString().split('T')[0];

            // Establecer fecha máxima como hoy para ambos campos
            fechaInicio.setAttribute('max', fechaHoyLocal);
            fechaFin.setAttribute('max', fechaHoyLocal);

            // Función para ajustar restricciones entre fechas
            function ajustarRestricciones() {
                const valorInicio = fechaInicio.value;
                const valorFin = fechaFin.value;

                // Si hay fecha inicio, fecha fin no puede ser menor
                if (valorInicio) {
                    fechaFin.setAttribute('min', valorInicio);
                } else {
                    fechaFin.removeAttribute('min');
                }

                // Si hay fecha fin, fecha inicio no puede ser mayor
                if (valorFin) {
                    fechaInicio.setAttribute('max', valorFin);
                } else {
                    fechaInicio.setAttribute('max', fechaHoyLocal);
                }
            }

            // Event listeners
            fechaInicio.addEventListener('change', ajustarRestricciones);
            fechaFin.addEventListener('change', ajustarRestricciones);

            // Inicializar restricciones al cargar
            ajustarRestricciones();
        });
    </script>

    <script>
        document.getElementById('filtro-articulo').addEventListener('input', function() {
            const filtro = this.value.toLowerCase();
            const filas = document.querySelectorAll('#tabla-ventas tbody tr.fila-articulo');

            filas.forEach(fila => {
                const textoFila = fila.innerText.toLowerCase();
                fila.style.display = textoFila.includes(filtro) ? '' : 'none';
            });
        });

        function limpiar_filtro() {
            document.getElementById('filtro-articulo').value = '';
            document.querySelectorAll('#tabla-ventas tbody tr.fila-articulo').forEach(fila => {
                fila.style.display = '';
            });
        }
    </script>
@endsection
