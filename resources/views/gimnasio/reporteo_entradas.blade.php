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
        @vite('resources/css/gimnasio/gimnasio_reporteo_styles.css')
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
            <h2>Reporteo Gimnasio</h2>
        </div>

        <x-modal-mensaje id="modal-mensaje" />

        <form method="GET" action="{{ route('gimnasio.reporteo') }}" class="filters-container">
            <div class="filter">
                <label for="fecha_inicio">Fecha Inicio</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" value="{{ $fechaInicio }}">
            </div>
            <div class="filter">
                <label for="fecha_fin">Fecha Fin</label>
                <input type="date" id="fecha_fin" name="fecha_fin" value="{{ $fechaFin }}">
            </div>
            <div class="filter" style="display: flex; justify-content: flex-end; height: 72px; width: 150px;">
                <button type="submit" class="btn" style="margin: 0.25rem 0;">Filtrar</button>
            </div>
        </form>

        <div class="cards-container">
            <div class="card">
                <div class="card-header">Total de Visitantes</div>
                <div class="card-body">
                    <span id="total-visitantes">{{ number_format($totales['total_visitantes']) }}</span>
                </div>
            </div>
            <div class="card">
                <div class="card-header">Promedio Diario</div>
                <div class="card-body">
                    <span id="promedio-diario">{{ number_format($totales['promedio_diario'], 1) }}</span>
                </div>
            </div>
            <div class="card">
                <div class="card-header">Promedio Mañana</div>
                <div class="card-body">
                    <span id="promedio-manana">{{ number_format($totales['promedio_manana'], 1) }}</span>
                </div>
            </div>
            <div class="card">
                <div class="card-header">Promedio Medio día</div>
                <div class="card-body">
                    <span id="promedio-mediodia">{{ number_format($totales['promedio_mediodia'], 1) }}</span>
                </div>
            </div>
            <div class="card">
                <div class="card-header">Promedio Tarde</div>
                <div class="card-body">
                    <span id="promedio-tarde">{{ number_format($totales['promedio_tarde'], 1) }}</span>
                </div>
            </div>
        </div>

        <div class="data-container">
            <div class="table-container">
                <table class="table" id="tabla-reporteo">
                    <thead style="position: relative;">
                        <tr>
                            <th>Fecha</th>
                            <th>Día de la Semana</th>
                            <th class="orden-numerico" style="cursor: pointer">Total Visitantes <i class="fa-solid fa-sort"></i></th>
                            <th class="orden-numerico" style="cursor: pointer">Adultos del día <i class="fa-solid fa-sort"></i></th>
                            <th class="orden-numerico" style="cursor: pointer">Menores del día <i class="fa-solid fa-sort"></i></th>
                            <th class="orden-numerico" style="cursor: pointer">Total Internos <i class="fa-solid fa-sort"></i></th>
                            <th class="orden-numerico" style="cursor: pointer">Total Externos <i class="fa-solid fa-sort"></i></th>
                            <th class="orden-numerico" style="cursor: pointer">Visitas<br>Mañana <i class="fa-solid fa-sort"></i></th>
                            <th class="orden-numerico" style="cursor: pointer">Visitas<br>Medio día <i class="fa-solid fa-sort"></i></th>
                            <th class="orden-numerico" style="cursor: pointer">Visitas<br>Tarde <i class="fa-solid fa-sort"></i></th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($reporteDias as $dia)
                            <tr class="fila-dia" 
                                data-total-visitantes="{{ $dia->total_visitantes }}"
                                data-adultos="{{ $dia->adultos_dia }}"
                                data-menores="{{ $dia->menores_dia }}"
                                data-internos="{{ $dia->total_internos }}"
                                data-externos="{{ $dia->total_externos }}"
                                data-manana="{{ $dia->visitas_manana }}"
                                data-mediodia="{{ $dia->visitas_mediodia }}"
                                data-tarde="{{ $dia->visitas_tarde }}">

                                <td>{{ \Carbon\Carbon::parse($dia->fecha)->format('d/m/Y') }}</td>
                                <td>{{ $dia->dia_semana }}</td>
                                <td>{{ number_format($dia->total_visitantes) }}</td>
                                <td>{{ number_format($dia->adultos_dia) }}</td>
                                <td>{{ number_format($dia->menores_dia) }}</td>
                                <td>{{ number_format($dia->total_internos) }}</td>
                                <td>{{ number_format($dia->total_externos) }}</td>
                                <td>{{ number_format($dia->visitas_manana) }}</td>
                                <td>{{ number_format($dia->visitas_mediodia) }}</td>
                                <td>{{ number_format($dia->visitas_tarde) }}</td>
                            </tr>
                        @endforeach
                        
                        <!-- Fila de totales -->
                        <tr class="fila-totales" style="background-color: #f8f9fa; font-weight: bold; border-top: 2px solid #dee2e6;">
                            <td>Total:</td>
                            <td></td>
                            <td>{{ number_format($totales['suma_total_visitantes']) }}</td>
                            <td>{{ number_format($totales['suma_adultos']) }}</td>
                            <td>{{ number_format($totales['suma_menores']) }}</td>
                            <td>{{ number_format($totales['suma_internos']) }}</td>
                            <td>{{ number_format($totales['suma_externos']) }}</td>
                            <td>{{ number_format($totales['suma_manana']) }}</td>
                            <td>{{ number_format($totales['suma_mediodia']) }}</td>
                            <td>{{ number_format($totales['suma_tarde']) }}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
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

    <!-- Ordenar tabla -->
    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const getCellValue = (tr, idx) => tr.children[idx].innerText.trim();

            const prioridadCaducidad = (valor) => {
                if (valor.toLowerCase().includes('caducado')) return 0;
                const match = valor.match(/\d+/);
                if (match) return parseInt(match[0]) + 1;
                return 99999;
            };

            const comparar = (idx, asc, tipo) => (a, b) => {
                let v1 = getCellValue(a, idx);
                let v2 = getCellValue(b, idx);

                if (tipo === 'caducidad') {
                    v1 = prioridadCaducidad(v1);
                    v2 = prioridadCaducidad(v2);
                } else if (tipo === 'numerico') {
                    v1 = parseFloat(v1.replace(/[$,]/g, '')) || 0;
                    v2 = parseFloat(v2.replace(/[$,]/g, '')) || 0;
                }

                return asc ? v1 - v2 : v2 - v1;
            };

            // Objeto para almacenar el estado de ordenamiento de cada columna
            const columnStates = {};

            const thead = document.querySelector('thead');
            if (!thead) return;

            thead.addEventListener('click', (event) => {
                const clickedElement = event.target;
                const th = clickedElement.closest('th');

                if (!th) return;

                const tipo = th.classList.contains('orden-caducidad') ? 'caducidad' :
                    th.classList.contains('orden-numerico') ? 'numerico' :
                    null;

                if (!tipo) return; // Solo aplica a columnas ordenables

                const table = th.closest('table');
                const tbody = table.querySelector('tbody');
                const rows = Array.from(tbody.querySelectorAll('tr'));
                const idx = Array.from(th.parentNode.children).indexOf(th);

                // Obtener el estado actual de esta columna específica
                // Si no existe, empieza como false (siguiente será true/ascendente)
                const currentState = columnStates[idx] || false;
                const asc = !currentState;

                // Actualizar el estado de esta columna
                columnStates[idx] = asc;

                // Limpiar clases visuales de todas las columnas
                document.querySelectorAll('th').forEach(t => t.classList.remove('asc', 'desc'));

                // Agregar clase visual solo a la columna actual
                th.classList.add(asc ? 'asc' : 'desc');

                // Ordenar las filas
                rows.sort(comparar(idx, asc, tipo));
                rows.forEach(row => tbody.appendChild(row));
            });
        });
    </script>
@endsection
