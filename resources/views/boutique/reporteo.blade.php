@extends('layouts.spa_menu')

@section('css')
    @php
        $spaCss = session('current_spa') ?? strtolower(optional(Auth::user()->spa)->nombre);
        if ($spaCss === 'newunid') $spaCss = 'palacio';
    @endphp
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite('resources/css/menus/' . $spaCss . '/menu_styles.css')
        @vite('resources/css/general_styles.css')
        @vite('resources/css/boutique/boutique_reporteo_styles.css')
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
            <h2>Reporteo</h2>
        </div>

        <x-modal-mensaje id="modal-mensaje" />

        <form method="GET" action="{{ route('boutique.reporteo') }}" class="filters-container">
            <div class="filter">
                <label for="fecha_inicio">Fecha de Ventas Inicio</label>
                <input type="date" id="fecha_inicio" name="fecha_inicio" value="{{ $fechaInicio }}">
            </div>
            <div class="filter">
                <label for="fecha_fin">Fecha de Ventas Fin</label>
                <input type="date" id="fecha_fin" name="fecha_fin" value="{{ $fechaFin }}">
            </div>
            <div class="filter">
                <label for="select_tipo_compra">Clasificación</label>
                <x-select-dropdown id="select_tipo_compra" class="form-control" placeholder="Tipo de compra"
                    :values="$clasificaciones_opciones" default="{{ $clasificacion_actual }}" />
            </div>
            <div class="filter">
                <label for="select_familia">Familia</label>
                <x-select-dropdown id="select_familia" class="form-control" placeholder="Seleccione familia"
                    :values="$familias_opciones" default="{{ $familia_actual }}" />
            </div>
            <div class="filter" style="display: flex; justify-content: flex-end; height: 72px; width: 150px;">
                <button type="submit" class="btn" style="margin: 0.25rem 0;">Filtrar</button>
            </div>
        </form>

        <div class="cards-container">
            <!-- Estas tarjetas muestran la suma de todos los tr que se muestran en la tabla -->
            <div class="card">
                <div class="card-header">Ventas Totales</div>
                <div class="card-body">
                    <span id="total-ventas">{{ number_format($totales['ventas_totales']) }}</span>
                </div>
            </div>
            <div class="card">
                <div class="card-header">Artículos Vendidos</div>
                <div class="card-body">
                    <span id="total-articulos-vendidos">{{ number_format($totales['articulos_vendidos']) }}</span>
                </div>
            </div>
            <div class="card">
                <div class="card-header">Ingreso Total</div>
                <div class="card-body">
                    <span id="total-ingreso">${{ number_format($totales['ingreso_total'], 2) }}</span>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Valor Inventario</div>
                <div class="card-body">
                    <span id="total-valor-inventario">${{ number_format($totales['valor_inventario'], 2) }}</span>
                </div>
            </div>

            <div class="card">
                <div class="card-header">Utilidad Bruta</div>
                <div class="card-body">
                    <span id="total-utilidad">${{ number_format($totales['utilidad_bruta'], 2) }}</span>
                </div>
            </div>
        </div>

        <div class="data-container">
            <div class="table-container">
                <table class="table" id="tabla-reporteo">
                    <thead style="position: relative;">
                        <tr>
                            <th>No. Auxiliar</th>
                            <th>Nombre</th>
                            <th>Familia</th>
                            <th class="orden-numerico tooltip-container" style="cursor: pointer">Total Artículos <i
                                    class="fa-solid fa-sort"></i></th>
                            <th class="orden-caducidad tooltip-container" style="cursor: pointer">Caducidad <i
                                    class="fa-solid fa-sort"></i>
                                <div class="tooltip tooltip-bottom tooltip-info tooltip-multiline" style="min-width: 300px;">
                                    Caducidad del artículo.
                                    <br><br>
                                    @foreach ($codigosColores as $codigo)
                                        <div style="display: flex; align-items: center; margin: 4px 0;">
                                            <div
                                                style="background-color: {{ $codigo['color'] }}; width: 12px; height: 12px; border: 1px solid rgba(200,200,200,0.5); border-radius: 50%; margin-right: 8px; {{ $codigo['color'] === '#ffffff' ? 'border-color: #ccc;' : '' }}">
                                            </div>
                                            <span style="font-size: 0.9em;">{{ $codigo['texto'] }}</span>
                                        </div>
                                    @endforeach
                                </div>
                            </th>
                            <th class="orden-numerico tooltip-container" style="cursor: pointer">Artículos Vendidos <i
                                    class="fa-solid fa-sort"></i>
                                <div class="tooltip tooltip-bottom tooltip-info tooltip-multiline">
                                    Cantidad de artículos vendidos en el periodo seleccionado.
                                    <br><br>
                                    Nota: La cantidad de artículos vendidos depende del filtro de fechas
                                </div>
                            </th>
                            <th class="orden-numerico tooltip-container" style="cursor: pointer">Ingreso Total <i
                                    class="fa-solid fa-sort"></i>
                                <div class="tooltip tooltip-bottom tooltip-info tooltip-multiline">Ingreso total por ventas
                                    de artículos.<br><br>
                                    Nota: Las ventas dependen del filtro de fechas
                                </div>
                            </th>
                            <th class="orden-numerico tooltip-container" style="cursor: pointer">Valor Inventario <i
                                    class="fa-solid fa-sort"></i>
                                <div class="tooltip tooltip-bottom tooltip-info tooltip-multiline">Valor del inventario si
                                    se vendieran todos los artículos al precio de venta actual.
                                </div>
                            </th>
                            <th class="orden-numerico tooltip-container" style="cursor: pointer">Utilidad Bruta
                                <i class="fa-solid fa-sort"></i>
                                <div class="tooltip tooltip-bottom tooltip-info tooltip-multiline">Diferencia entre el
                                    precio de venta y el costo del producto.
                                    <br><br>
                                    Nota: Las ventas dependen del filtro de fechas
                                </div>
                            </th>
                            <th class="tooltip-container">Clasificación
                                <div class="tooltip tooltip-bottom tooltip-info tooltip-multiline">
                                    Clasificación del artículo según sus ventas los últimos 6 meses.

                                    <br><br>

                                    @if (!empty($clasificaciones_config))
                                        @php
                                            // Ordenar por minimo_ventas descendente
                                            $clasificaciones_ordenadas = collect($clasificaciones_config)->sortByDesc(
                                                'minimo_ventas',
                                            );
                                        @endphp

                                        @foreach ($clasificaciones_ordenadas as $config)
                                            {{ $config['nombre'] }}:
                                            @if ($config['minimo_ventas'] == 0)
                                                @php
                                                    // Para "Obsoleto", encontrar el mínimo de la clasificación anterior
                                                    $clasificacion_anterior = $clasificaciones_ordenadas
                                                        ->where('minimo_ventas', '>', 0)
                                                        ->sortBy('minimo_ventas')
                                                        ->first();
                                                    $limite = $clasificacion_anterior
                                                        ? $clasificacion_anterior['minimo_ventas']
                                                        : 10;
                                                @endphp
                                                Menos de {{ $limite }} ventas.
                                            @else
                                                Mínimo {{ $config['minimo_ventas'] }} ventas.
                                            @endif

                                            @if (!$loop->last)
                                                <br><br>
                                            @endif
                                        @endforeach
                                    @else
                                        Sin datos de clasificación.
                                    @endif
                                </div>
                            </th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($articulos as $articulo)
                            <tr class="fila-articulo" data-total-cantidad="{{ $articulo->total_cantidad }}"
                                data-articulos-vendidos="{{ $articulo->articulos_vendidos }}"
                                data-ingreso-total="{{ $articulo->ingreso_total }}"
                                data-valor-inventario="{{ $articulo->valor_inventario }}"
                                data-utilidad-bruta="{{ $articulo->utilidad_bruta }}"
                                data-dias-restantes="{{ $articulo->dias_restantes_minimo }}">

                                <td>{{ str_pad($articulo->numero_auxiliar, 10, '0', STR_PAD_LEFT) }}</td>
                                <td>{{ $articulo->nombre_articulo }}</td>
                                <td>{{ $articulo->familia }}</td>
                                <td>{{ number_format($articulo->total_cantidad) }}</td>

                                <td style="display: flex; flex-direction: row; align-items: center; height: 3rem;"
                                    class="tooltip-container">
                                    <div
                                        style="background-color: {{ $articulo->color_caducidad }}; width: 1rem; height: 1rem; align-self: center; border: 1px solid rgba(200,200,200,0.5); border-radius: 50%; margin-right: 10px;">
                                    </div>
                                    {{ $articulo->texto_caducidad }}
                                    <div class="tooltip {{ $loop->iteration <= 6 ? 'tooltip-bottom' : 'tooltip-top' }} tooltip-info tooltip-multiline"
                                        style="min-width: 300px;">
                                        {!! $articulo->tooltip_compras !!}
                                    </div>
                                </td>

                                <td>{{ number_format($articulo->articulos_vendidos) }}</td>
                                <td>${{ number_format($articulo->ingreso_total, 2) }}</td>
                                <td>${{ number_format($articulo->valor_inventario, 2) }}</td>
                                <td>${{ number_format($articulo->utilidad_bruta, 2) }}</td>
                                <td>{{ $articulo->clasificacion_actual }}</td>
                            </tr>
                        @endforeach
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
