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
            'resources/css/menus/themes/' . $spaCss . '.css',
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
        <div class="filter">
            <label for="desde">Desde</label>
            <input type="date" id="desde" name="desde" value="{{ request('desde', $fechaInicio) }}">
        </div>
        <div class="filter">
            <label for="hasta">Hasta</label>
            <input type="date" id="hasta" name="hasta" value="{{ request('hasta', $fechaFin) }}">
        </div>
        <div class="filter">
            <label for="servicio">Servicio</label>
            <select id="servicio" name="servicio">
                <option value="">-- Todos --</option> 
                {{-- Itera sobre los servicios pasados desde el controlador --}}
                @foreach ($servicios as $clave => $nombre)
                    <option value="{{ $clave }}" {{ request('servicio') == $clave ? 'selected' : '' }}>{{ $nombre }}</option>
                @endforeach
            </select>
        </div>
        <div class="filter" style="display: flex; justify-content: flex-end; height: 72px; width: 150px;">
            <button type="submit" class="btn" style="margin: 0.25rem 0;">Filtrar</button>
        </div>
    </form>

    <div class="report-main-layout">
        <aside class="report-sidebar">
            {{-- Secciones inline de reportes (lazy-load via AJAX) --}}
            <div class="reports-inline">

                <div class="tarjeta report-section active" id="report-panorama" style="cursor: pointer;" data-tipo="panorama">
                    <div class="contenido-tarjeta">
                        <div class="info">
                            <i class="fas fa-chart-bar"></i>
                            <h3 class="titulo">Panorama General</h3>
                            <div class="report-controls" style="display: none;">
                                <div class="acciones">
                                    {{-- Podríamos agregar un botón de exportar PDF o algo similar en el futuro --}}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tarjeta report-section" id="report-clientes" style="cursor: pointer;" data-tipo="clientes">
                    <div class="contenido-tarjeta">
                        <div class="info">
                            <i class="fas fa-users"></i>
                            <h3 class="titulo">Clientes</h3>
                            <div class="report-controls" style="display: none;">
                                <div class="report-filters">
                                    <div class="search-container">
                                        <input type="text" class="local-filter" name="search" placeholder="Buscar...">
                                        <button class="btn-search"><i class="fas fa-search"></i></button>
                                    </div>
                                    <label>Ordenar</label>
                                    <select class="local-filter" name="order">
                                        <option value="fecha">Fecha</option>
                                        <option value="cliente">Cliente</option>
                                    </select>
                                </div>
                                <div class="acciones">
                                    <button class="btn btn-sm btn-export-section" data-tipo="clientes">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tarjeta report-section" id="report-terapeutas" style="cursor: pointer;" data-tipo="terapeutas">
                    <div class="contenido-tarjeta">
                        <div class="info">
                            <i class="fas fa-hand-sparkles"></i>
                            <h3 class="titulo">Terapeutas</h3>
                            <div class="report-controls" style="display: none;">
                                <div class="report-filters">
                                    <div class="search-container">
                                        <input type="text" class="local-filter" name="search" placeholder="Buscar...">
                                        <button class="btn-search"><i class="fas fa-search"></i></button>
                                    </div>
                                </div>
                                <div class="acciones">
                                    <button class="btn btn-sm btn-export-section" data-tipo="terapeutas">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tarjeta report-section" id="report-propinas" style="cursor: pointer;" data-tipo="propinas">
                    <div class="contenido-tarjeta">
                        <div class="info">
                            <i class="fas fa-hand-holding-usd"></i>
                            <h3 class="titulo">Propinas</h3>
                            <div class="report-controls" style="display: none;">
                                <div class="report-filters">
                                    <div class="search-container">
                                        <input type="text" class="local-filter" name="search" placeholder="Buscar...">
                                        <button class="btn-search"><i class="fas fa-search"></i></button>
                                    </div>
                                </div>
                                <div class="acciones">
                                    <button class="btn btn-sm btn-export-section" data-tipo="propinas">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tarjeta report-section" id="report-servicios" style="cursor: pointer;" data-tipo="servicios">
                    <div class="contenido-tarjeta">
                        <div class="info">
                            <i class="fas fa-concierge-bell"></i>
                            <h3 class="titulo">Servicios</h3>
                            <div class="report-controls" style="display: none;">
                                <div class="report-filters">
                                    <div class="search-container">
                                        <input type="text" class="local-filter" name="search" placeholder="Buscar...">
                                        <button class="btn-search"><i class="fas fa-search"></i></button>
                                    </div>
                                    <label>Detalle</label>
                                    <input type="checkbox" class="local-filter" name="detalle" value="1">
                                </div>
                                <div class="acciones">
                                    <button class="btn btn-sm btn-export-section" data-tipo="servicios">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tarjeta report-section" id="report-no-shows" style="cursor: pointer;" data-tipo="no_shows">
                    <div class="contenido-tarjeta">
                        <div class="info">
                            <i class="fas fa-user-slash"></i>
                            <h3 class="titulo">No Shows</h3>
                            <div class="report-controls" style="display: none;">
                                <div class="report-filters">
                                    <div class="search-container">
                                        <input type="text" class="local-filter" name="search" placeholder="Buscar...">
                                        <button class="btn-search"><i class="fas fa-search"></i></button>
                                    </div>
                                </div>
                                <div class="acciones">
                                    <button class="btn btn-sm btn-export-section" data-tipo="no_shows">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tarjeta report-section" id="report-anfitriones" style="cursor: pointer;" data-tipo="anfitriones">
                    <div class="contenido-tarjeta">
                        <div class="info">
                            <i class="fas fa-user-tie"></i>
                            <h3 class="titulo">Anfitriones</h3>
                            <div class="report-controls" style="display: none;">
                                <div class="report-filters">
                                    <div class="search-container">
                                        <input type="text" class="local-filter" name="search" placeholder="Buscar...">
                                        <button class="btn-search"><i class="fas fa-search"></i></button>
                                    </div>
                                    <label>Activo</label>
                                    <select class="local-filter" name="activo">
                                        <option value="">-- Todos --</option>
                                        <option value="1">Sí</option>
                                        <option value="0">No</option>
                                    </select>
                                </div>
                                <div class="acciones">
                                    <button class="btn btn-sm btn-export-section" data-tipo="anfitriones">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="tarjeta report-section" id="report-detalle-ventas" style="cursor: pointer;" data-tipo="detalle_ventas">
                    <div class="contenido-tarjeta">
                        <div class="info">
                            <i class="fas fa-cash-register"></i>
                            <h3 class="titulo">Detalle de Ventas</h3>
                            <div class="report-controls" style="display: none;">
                                <div class="report-filters">
                                    <div class="search-container">
                                        <input type="text" class="local-filter" name="search" placeholder="Buscar...">
                                        <button class="btn-search"><i class="fas fa-search"></i></button>
                                    </div>
                                    <label>Turno</label>
                                    <select class="local-filter" name="turno">
                                        <option value="">-- Todos --</option>
                                        <option value="manana">Mañana</option>
                                        <option value="tarde">Tarde</option>
                                        <option value="noche">Noche</option>
                                    </select>
                                </div>
                                <div class="acciones">
                                    <button class="btn btn-sm btn-export-section" data-tipo="detalle_ventas">
                                        <i class="fas fa-download"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </aside>

        <main class="report-main-content">
            <div id="panorama-general-content">
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
            </div>
        </main>
    </div>

<script>
        (function(){
            const mainContent = document.querySelector('.report-main-content');
            const panoramaContent = document.getElementById('panorama-general-content');
            const currentSpa = @json(session('current_spa') ?? strtolower(optional(Auth::user()->spa)->nombre));
            if (!currentSpa) {
                console.error("No se pudo determinar la unidad de negocio actual.");
            }

            const openReports = new Set(['panorama']);
            const reportOrder = Array.from(document.querySelectorAll('.report-section[data-tipo]')).map(el => el.dataset.tipo);

            function updatePanoramaExportLinks() {
                const desde = document.getElementById('desde').value;
                const hasta = document.getElementById('hasta').value;
                const servicio = document.getElementById('servicio').value;
                const spaId = @json(optional(Auth::user()->spa)->id);

                document.querySelectorAll('[data-export-type]').forEach(link => {
                    const tipo = link.dataset.exportType;
                    const url = new URL(link.href);
                    const params = new URLSearchParams(url.search);
                    
                    params.set('desde', desde);
                    params.set('hasta', hasta);
                    params.set('servicio', servicio);
                    params.set('lugar', spaId);
                    
                    link.href = `/reportes/exportar/${tipo}?${params.toString()}`;
                });
            }

            async function applyGlobalFilter() {
                const mainFilterForm = document.querySelector('.filtro-form');
                const formData = new FormData(mainFilterForm);
                const params = new URLSearchParams(formData);

                // 1. Update Panorama General
                panoramaContent.innerHTML = '<div>Actualizando...</div>';
                try {
                    const response = await fetch(`{{ route('reports.index') }}?${params.toString()}`);
                    if (!response.ok) throw new Error('Failed to reload general overview');
                    const html = await response.text();
                    const parser = new DOMParser();
                    const doc = parser.parseFromString(html, 'text/html');
                    const newPanoramaContent = doc.getElementById('panorama-general-content');
                    if (newPanoramaContent) {
                        panoramaContent.innerHTML = newPanoramaContent.innerHTML;
                        updatePanoramaExportLinks();
                    }
                } catch (error) {
                    panoramaContent.innerHTML = `<div class="text-danger">Error al actualizar panorama: ${error.message}</div>`;
                }

                // 2. Update all open dynamic reports
                document.querySelectorAll('.report-content').forEach(reportContainer => {
                    const tipo = reportContainer.dataset.tipo;
                    if (tipo && reportContainer.style.display !== 'none') {
                        fetchReport(tipo, reportContainer);
                    }
                });
            }

            document.addEventListener('DOMContentLoaded', function() {
                updatePanoramaExportLinks();
                document.querySelectorAll('.report-content').forEach(el => {
                    el.style.display = 'none';
                });
                
                const filterForm = document.querySelector('.filtro-form');
                if (filterForm) {
                    filterForm.addEventListener('submit', function(event) {
                        event.preventDefault();
                        applyGlobalFilter();
                    });
                }
            });

            function getGlobalParams(){
                const form = document.querySelector('.filtro-form');
                if (!form) return new URLSearchParams();
                return new URLSearchParams(new FormData(form));
            }

            function buildParams(reportContainer){
                const globalParams = getGlobalParams();

                if (currentSpa) {
                    globalParams.set('spa', currentSpa);
                }

                const locals = reportContainer.querySelectorAll('.local-filter');
                locals.forEach(function(el){
                    if (el.type === 'checkbox'){
                        if (el.checked) globalParams.set(el.name, el.value);
                        else globalParams.delete(el.name);
                    } else if (el.tagName === 'SELECT' || el.tagName === 'INPUT'){
                        if (el.value !== '') globalParams.set(el.name, el.value);
                    }
                });
                return globalParams;
            }

            async function fetchReport(tipo, reportContainer){
                const params = buildParams(reportContainer);
                const url = '/reportes/exportar/' + tipo + '?' + params.toString();
                const contentEl = reportContainer.querySelector('.dynamic-report-content');

                contentEl.innerHTML = '<div>Consultando...</div>';

                try{
                    const res = await fetch(url, {credentials: 'same-origin'});
                    if (!res.ok) throw new Error('Error HTTP ' + res.status);
                    const text = await res.text();
                    contentEl.innerHTML = text;
                } catch (e){
                    contentEl.innerHTML = '<div class="text-danger">Error al cargar: '+ e.message +'</div>';
                }
            }

            function exportSection(tipo, reportContainer) {
                const params = buildParams(reportContainer);
                const url = '/reportes/exportar/' + tipo + '?' + params.toString();
                window.open(url, '_blank');
            }

            function createReportContainer(tipo, reportTitle, originalControls) {
                const reportId = `report-container-${tipo}`;
                const newReportContainer = document.createElement('div');
                newReportContainer.id = reportId;
                newReportContainer.dataset.tipo = tipo;
                newReportContainer.classList.add('report-content');
                newReportContainer.style.display = 'block';

                const header = document.createElement('div');
                header.classList.add('dynamic-report-header'); 

                const titleElement = document.createElement('h3');
                titleElement.textContent = reportTitle;
                header.appendChild(titleElement); 

                const actionsContainer = document.createElement('div');
                actionsContainer.classList.add('report-actions-container');
                header.appendChild(actionsContainer);
                
                const filtersContainer = document.createElement('div');
                filtersContainer.classList.add('report-filters-area');

                const content = document.createElement('div');
                content.classList.add('dynamic-report-content');

                newReportContainer.appendChild(header);
                newReportContainer.appendChild(filtersContainer);
                newReportContainer.appendChild(content);

                if (originalControls) {
                    const clonedControls = originalControls.cloneNode(true);
                    clonedControls.style.display = 'flex';
                    
                    const filters = clonedControls.querySelector('.report-filters');
                    const actions = clonedControls.querySelector('.acciones');

                    if (filters) {
                        filtersContainer.appendChild(filters); 
                    }

                    if (actions) {
                        actionsContainer.appendChild(actions);
                    }
                    
                    const newExportBtn = actionsContainer.querySelector('.btn-export-section');
                    if (newExportBtn) {
                        newExportBtn.addEventListener('click', () => exportSection(tipo, newReportContainer));
                    }

                    const localFilters = filtersContainer.querySelectorAll('.local-filter');
                    localFilters.forEach(filter => {
                        if (filter.tagName === 'SELECT' || filter.type === 'checkbox') {
                            filter.addEventListener('change', () => {
                                fetchReport(tipo, newReportContainer);
                            });
                        } else if (filter.type === 'text' || filter.type === 'number') {
                            const triggerSearch = () => {
                                fetchReport(tipo, newReportContainer);
                            };

                            filter.addEventListener('keydown', (event) => {
                                if (event.key === 'Enter') {
                                    event.preventDefault();
                                    triggerSearch();
                                }
                            });

                            const searchButton = filter.closest('.search-container')?.querySelector('.btn-search') ||
                                                 filter.closest('.report-filters')?.querySelector('.btn-search');
                            
                            if (searchButton) {
                                searchButton.addEventListener('click', triggerSearch);
                            } else if (filter.name !== 'comision') {
                                filter.addEventListener('input', triggerSearch);
                            }
                        }
                    });
                }

                return newReportContainer;
            }

            document.querySelectorAll('.report-section[data-tipo]').forEach(function(reportSectionEl){
                reportSectionEl.addEventListener('click', function(){
                    const tipo = reportSectionEl.getAttribute('data-tipo');

                    document.querySelectorAll('.report-content').forEach(el => el.style.display = 'none');
                    panoramaContent.style.display = 'none';

                    document.querySelectorAll('.report-section').forEach(el => el.classList.remove('active'));

                    if (openReports.has(tipo)) {
                        openReports.delete(tipo);
                        panoramaContent.style.display = 'block';
                        document.getElementById('report-panorama').classList.add('active');
                    } else {
                        openReports.clear();
                        reportSectionEl.classList.add('active');

                        if (tipo === 'panorama') {
                            panoramaContent.style.display = 'block';
                        } else {
                            let reportContainer = document.getElementById(`report-container-${tipo}`);
                            if (!reportContainer) {
                                const reportTitle = reportSectionEl.querySelector('.titulo').textContent;
                                const originalControls = reportSectionEl.querySelector('.report-controls');
                                reportContainer = createReportContainer(tipo, reportTitle, originalControls);
                                mainContent.appendChild(reportContainer);
                                fetchReport(tipo, reportContainer);
                            } else {
                                reportContainer.style.display = 'block';
                                // If the report was already created, we might want to refresh its data
                                // in case global filters have changed since it was last viewed.
                                fetchReport(tipo, reportContainer);
                            }
                        }
                        openReports.add(tipo);
                    }
                });
            });

        })();
    </script>

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