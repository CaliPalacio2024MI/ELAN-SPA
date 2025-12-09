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
        
        @vite('resources/css/ModalAviso/modal_aviso.css')
        @vite('resources/css/sabana_reservaciones/reservaciones_styles.css')
        @vite('resources/css/componentes/tooltip.css')
        @vite('resources/css/gestores/g_areas.css')
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
        <h2 class="header-title">Gestor de Áreas</h2>
    </div>

   <div class="gestor-layout-container">
     <div class="tabla-wrapper">
        {{-- Tabla de Departamentos --}}
        <div class="tabla-container flex-grow-1">
            <table class="tabla-responsive custom-tabla">
                <thead>
                    <tr>
                        <th>Áreas</th>
                        <th>Anfitriones</th>
                        <th>Especialidades</th>
                        <th>Cabinas relacionadas</th>
                        <th>Activo</th>
                        <th>Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse ($departamentos as $d)
                        <tr>
                            <td>{{ ucfirst($d['departamento']) }}</td>
                            <td>
                                @if($d['anfitriones']->isEmpty())
                                    —
                                @else
                                    @foreach($d['anfitriones'] as $a)
                                        {{ $a['nombre'] ?? 'N/D' }}<br>
                                    @endforeach
                                @endif
                            </td>
                            <td>
                                @if($d['especialidades']->isEmpty())
                                    —
                                @else
                                    {{ implode(', ', $d['especialidades']->toArray()) }}
                                @endif
                            </td>
                            <td>
                                @if($d['cabinas']->isEmpty())
                                    —
                                @else
                                    @foreach($d['cabinas'] as $c)
                                        {{ $c['nombre'] }}<br>
                                    @endforeach
                                @endif
                            </td>
                            <td>
                                @php $activo = $d['activo'] ?? true; @endphp
                                <span
                                    class="badge toggle-estado {{ $activo ? 'bg-success' : 'bg-danger' }}"
                                    data-departamento="{{ $d['departamento'] }}"
                                    style="cursor: pointer;"
                                >
                                    {{ $activo ? 'Activo' : 'Inactivo' }}
                                </span>
                            </td>
                            <td>
                                <div class="action-buttons">
                                    <button type="button" class="btn btn-warning btn-edit"
                                        data-bs-toggle="modal"
                                        data-bs-target="#editDepartamentoModal"
                                        data-departamento="{{ $d['departamento'] }}">
                                        <i class="fa-solid fa-pen-to-square icono-accion-pequeno"></i>
                                    </button>

                                    <form action="{{ route('areas.destroy', ['departamento' => $d['departamento']]) }}" method="POST" class="d-inline form-delete" id="delete-form-{{ $d['departamento'] }}">
                                        @csrf @method('DELETE')
                                        {{-- Se cambia type="submit" a type="button" y se añaden data-attributes --}}
                                        <button type="button" class="btn btn-danger btn-delete" data-bs-toggle="modal" data-bs-target="#alertModal"
                                                class="btn btn-danger btn-delete"                                               
                                                data-departamento-nombre="{{ ucfirst($d['departamento']) }}">
                                            <i class="fa-solid fa-delete-left icono-accion-pequeno" style="color: #ac0505;"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="text-center">No hay información disponible para el spa actual.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
     </div>
     <div class="formulario-wrapper">
        <div class="card">
        {{-- Formulario para Nuevo Departamento --}}
        <div class="form-container">
            <h5 class="form-title">Nueva Área</h5>
            <form id="nuevoDepartamentoForm" action="{{ route('areas.store') }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label for="nombre_departamento" class="form-label">Nombre del Área</label>
                    <input type="text" class="form-control" id="nombre_departamento" name="nombre_departamento" placeholder="Ej: Spa, Salon" required>
                </div>

                <div class="mb-3">
                    <label class="form-label">Permisos adicionales</label>
                    @php
                        $currentSpaRaw = session('current_spa') ?? optional(Auth::user()->spa)->nombre ?? '';
                        $currentSpa = strtolower(trim($currentSpaRaw));
                    @endphp

                    {{-- Incluir siempre el SPA actual de forma oculta para asegurar su creación --}}
                    @if($currentSpa !== '')
                        <input type="hidden" name="spas[]" value="{{ $currentSpa }}">
                    @endif
                    {{-- Mostrar checkboxes para los OTROS spas --}}
                    @foreach ($spasDisponibles as $spa)
                        @php $spaKey = strtolower($spa->nombre); @endphp
                        @if ($spaKey === $currentSpa)
                            @continue
                        @endif
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="spas[]" value="{{ $spaKey }}" id="spa_{{ $spaKey }}">
                            <label class="form-check-label" for="spa_{{ $spaKey }}">{{ $spa->nombre }}</label>
                        </div>
                    @endforeach
                </div>
                <button type="submit" class="btn btn-success" onclick="this.disabled = true; this.innerText='Guardando...'; this.form.submit();">Guardar</button>
            </form>
        </div>
        </div>
        </div>
    </div>

    <!-- Modal: Editar Departamento -->
    <div class="modal fade" id="editDepartamentoModal" tabindex="-1" aria-labelledby="editDepartamentoModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editDepartamentoModalLabel">Editar Departamento</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="editDepartamentoForm" method="POST" action="">
                        @csrf
                        @method('PUT') {{-- Laravel simula PUT a través de un campo _method --}}
                        <div class="mb-3">
                            <label for="edit_nombre_departamento" class="form-label">Nombre del Departamento</label>
                            <input type="text" class="form-control" id="edit_nombre_departamento" name="nombre_departamento" required>
                        </div>
                        <button type="submit" class="btn btn-success">Guardar Cambios</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

@include('components.alert-modal')

{{-- Se añade la sección de scripts para cargar el JS necesario --}}
@section('scripts')
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/js/gestores/areas.js'])
    @endif

    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const alertModalEl = document.getElementById('alertModal');
            if (alertModalEl) {
                alertModalEl.addEventListener('show.bs.modal', function (event) {
                    // Botón que activó el modal
                    const button = event.relatedTarget;
                    
                    // Solo actuar si es un botón de eliminar
                    if (!button.classList.contains('btn-delete')) {
                        return;
                    }

                    const nombreDepartamento = button.dataset.departamentoNombre || 'el elemento';
                    const form = button.closest('form');

                    // Elementos del modal
                    const modalTitle = alertModalEl.querySelector('.modal-title');
                    const modalBody = alertModalEl.querySelector('#alertModalContent');
                    const confirmBtn = alertModalEl.querySelector('#alertModalConfirmBtn');

                    // Actualizar contenido del modal con los textos deseados
                    modalTitle.textContent = `¿Estás seguro?`;
                    modalBody.innerHTML = '<p>Esta acción no se puede deshacer.</p>';

                    // Configurar el botón de confirmación
                    confirmBtn.textContent = 'Sí, eliminar';
                    confirmBtn.classList.remove('d-none');
                    confirmBtn.onclick = () => form.submit();
                });
            }
        });
    </script>
    @include('components.session-alert')
@endsection
@endsection