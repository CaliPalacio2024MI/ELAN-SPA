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
        @vite('resources/css/gimnasio/gimnasio_historial_styles.css')
        @vite('resources/css/componentes/autoComplete.css')
        @vite('resources/css/componentes/selectDropdown.css')
        @vite('resources/css/componentes/modal.css')
        @vite('resources/css/componentes/tooltip.css')
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
            <div></div>
            <h2>Historial Entradas</h2>
            <div></div>
        </div>

        <form method="GET" action="{{ route('gimnasio.historial') }}" class="filters-container">
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
                <input type="text" id="filtro-busqueda" class="search-filter" placeholder="Buscar por nombre...">
                <button type="button" class="search-filter btn" onclick="limpiar_filtro()">Limpiar</button>
            </div>
        </form>

        <div class="table-margin" style="overflow-x: auto;">
            <table class="table" id="tabla-entradas">
                <thead>
                    <tr>
                        <th>Id</th>
                        <th>Tipo</th>
                        <th>Fecha Registro</th>
                        <th>Origen</th>
                        <th>Nombre Principal</th>
                        <th>Edad</th>
                        <th>Tutor</th>
                        <th>Teléfono</th>
                        <th>Anfitrión</th>
                    </tr>
                </thead>
                <tbody>
                    @php
                        // Agrupar registros por fecha y hora para mantener el formato de colores alternados
                        $registrosAgrupados = $todosLosRegistros->groupBy(function ($registro) {
                            return $registro->fecha_registro->format('Y-m-d H:i');
                        });
                        $contadorGrupo = 0;
                    @endphp

                    @forelse($registrosAgrupados as $fechaHora => $registrosDelGrupo)
                        @php
                            $contadorGrupo++;
                            $claseColor = $contadorGrupo % 2 === 0 ? 'fila-par' : 'fila-impar';
                        @endphp

                        @foreach ($registrosDelGrupo as $registro)
                            <tr class="fila-body {{ $claseColor }}">
                                <td>{{ $registro->id }}-{{ strtoupper(substr($registro->tipo, 0, 1)) }}</td>
                                <td>
                                    <span class="badge {{ $registro->tipo === 'adulto' ? 'bg-primary' : 'bg-warning' }}">
                                        {{ ucfirst($registro->tipo) }}
                                    </span>
                                </td>
                                <td style="text-align: center; width: 120px;">
                                    {{ \Carbon\Carbon::parse($registro->fecha_registro)->format('d/m/Y') }}
                                    <br>
                                    {{ \Carbon\Carbon::parse($registro->fecha_registro)->format('H:i:s') }}
                                </td>
                                <td>
                                    <span
                                        class="badge {{ $registro->origen_registro === 'interno' ? 'bg-success' : 'bg-info' }}">
                                        {{ ucfirst($registro->origen_registro) }}
                                    </span>
                                </td>
                                <td>{{ $registro->nombre_principal }}</td>
                                <td>
                                    @if ($registro->edad)
                                        {{ $registro->edad }} años
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($registro->tutor)
                                        {{ $registro->tutor }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($registro->telefono)
                                        {{ $registro->telefono }}
                                    @else
                                        -
                                    @endif
                                </td>
                                <td>
                                    @if ($registro->anfitrion)
                                        {{ $registro->anfitrion }}
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    @empty
                        <tr class="fila-body">
                            <td colspan="9" style="text-align: center">Sin registros de entrada al gimnasio</td>
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

    <!-- Lógica de Filtro de Busqueda -->
    <script>
        document.getElementById('filtro-busqueda').addEventListener('input', function() {
            const filtro = this.value.toLowerCase();
            const filas = document.querySelectorAll('#tabla-entradas tbody tr.fila-body');

            filas.forEach(fila => {
                const textoFila = fila.innerText.toLowerCase();
                fila.style.display = textoFila.includes(filtro) ? '' : 'none';
            });
        });

        function limpiar_filtro() {
            document.getElementById('filtro-busqueda').value = '';
            document.querySelectorAll('#tabla-entradas tbody tr.fila-body').forEach(fila => {
                fila.style.display = '';
            });
        }
    </script>
@endsection
