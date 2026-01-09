<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

@extends('layouts.spa_menu')

@section('roleValidation')
@if (!in_array(Auth::user()->rol, ['master', 'administrador']))
    <script>
        alert('Acceso denegado.');
        window.location.href = "{{ route('dashboard') }}";
    </script>
    @php exit; @endphp
@endif
@section('logo_img')
@php
    $spasFolder = session('current_spa') ?? strtolower(optional(Auth::user()->spa)->nombre);
@endphp
<img src="{{ asset("images/$spasFolder/logo.png") }}" alt="Logo de {{ ucfirst($spasFolder) }}">
@endsection

@section('css')
@php
    $spaCss = session('current_spa') ?? strtolower(optional(Auth::user()->spa)->nombre);
    if (!$spaCss) {
        $spaCss = 'palacio'; 
    }
@endphp
@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite([
        'resources/css/menus/themes/' . $spaCss . '.css',
        'resources/css/gestores/g_cabinas_styles.css',
        'resources/css/ModalAviso/modal_aviso.css',

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
    <div class="sidebar-decoration" style="background-image: url('{{ $linDecorativa}}');"></div>
@endsection

@section('content')
<header class="main-header">
    <h2>GESTIONAR CABINAS</h2>
</header>
    <div class="table-container">
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#cabinaModal">
            Nueva Cabina
        </button>

        <form method="GET" action="{{ route('cabinas.index') }}" class="search-form d-flex mb-3">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control search-input" placeholder="Buscar cabina...">
            <button type="submit" class="btn btn-buscar">
                <i class="fas fa-search"></i>
            </button>
        </form>

        <table class="table-responsive custom-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Tipo de cabinas</th>
                    <th>Especialidades</th>
                    <th>Activo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($cabinas as $cabina)
                    <tr>
                        <td>{{ $cabina->nombre}}</td>
                        <td>{{ $cabina->clase }}</td>
                        <td>{{ is_array($cabina->clases_actividad) ? implode(', ', $cabina->clases_actividad) : '' }}</td>
                        <td>
                       
                            <span 
                                class="badge toggle-estado {{ $cabina->activo ? 'bg-success' : 'bg-danger' }}"
                                data-id="{{ $cabina->id }}"
                                data-estado="{{ $cabina->activo }}"
                            >
                                {{ $cabina->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>                       
                        <td>
                       
                            <button type="button" class="btn btn-warning btn-edit" data-bs-toggle="modal" 
                                data-bs-target="#editCabinaModal"
                                data-id="{{ $cabina->id }}"
                                data-nombre="{{ $cabina->nombre}}"
                                data-clase="{{ $cabina->clase}}"
                                data-clases-actividad='@json($cabina->clases_actividad)'
                                data-activo="{{ $cabina->activo }}">
                                <i class="fa-solid fa-pen-to-square icono-accion-pequeno"></i>
                            </button>

                            <form action="{{ route('cabinas.destroy', $cabina) }}" method="POST" class="d-inline" onsubmit="return confirmarEliminacion(event)">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger">
                                <i class="fa-solid fa-delete-left icono-accion-pequeno" style="color: #ac0505;"></i> 
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="modal fade" id="cabinaModal" tabindex="-1" aria-labelledby="cabinaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="cabinaModalLabel">Nueva Cabina</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('cabinas.store') }}" method="POST">
                        @csrf
                        @php $fromEdit = old('_from_edit') == '1'; @endphp
    
                        <div class="mb-3">
                            <label for="nombre" class="form-label">Nombre de la cabina</label>
                            <input type="text" class="form-control {{ $errors->create->has('nombre') ? 'is-invalid' : '' }}" id="nombre" name="nombre" value="{{ $fromEdit ? '' : old('nombre') }}" required>
                            @error('nombre', 'create')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
    
                        <div class="mb-3">
                            <label for="clase" class="form-label">Tipo de cabinas</label>
                            <input type="text" class="form-control {{ $errors->create->has('clase') ? 'is-invalid' : '' }}" id="clase" name="clase" value="{{ $fromEdit ? '' : old('clase') }}">
                            @error('clase', 'create')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
    
                        <div class="mb-3">
                            <label class="form-label">Especialidades</label>

                            @forelse (($experienciasPorClase ?? []) as $claseName => $tipos)
                                @php $safeClassId = str_replace(' ', '_', strtolower($claseName)); @endphp
                                <div class="form-check">
                                    <input class="form-check-input main-cat" type="checkbox" value="{{ $claseName }}" id="cat_{{ $safeClassId }}" data-target="subtipo_{{ $safeClassId }}">
                                    <label class="form-check-label" for="cat_{{ $safeClassId }}">{{ ucfirst($claseName) }}</label>
                                </div>
                                <div id="subtipo_{{ $safeClassId }}" class="ps-3 mt-2 subtipo-container" style="display:none;">
                                    @foreach ($tipos as $idx => $tipo)
                                        <div class="form-check">
                                            <input class="form-check-input subtype-checkbox" type="checkbox" name="clases_actividad[]" value="{{ $tipo }}" id="{{ $safeClassId }}_tipo_{{ $idx }}">
                                            <label class="form-check-label" for="{{ $safeClassId }}_tipo_{{ $idx }}">{{ $tipo }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            @empty
                                <p class="text-muted">No hay especialidades disponibles.</p>
                            @endforelse
                        </div>

                        <div class="mb-3">
                            <label for="activo" class="form-label">Estado</label>
                            <select class="form-select {{ $errors->create->has('activo') ? 'is-invalid' : '' }}" id="activo" name="activo">
                                <option value="1" {{ (!$fromEdit && old('activo') == '1') ? 'selected' : '' }}>Activo</option>
                                <option value="0" {{ (!$fromEdit && old('activo') == '0') ? 'selected' : '' }}>Inactivo</option>
                            </select>
                            @error('activo', 'create')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
    
                        <input type="hidden" name="spa_id" value="{{ session('current_spa_id') }}">
                        <input type="hidden" name="_from_edit" value="0">
    
                        <button type="submit" class="btn btn-success">Guardar Cabina</button>
                    </form>
                </div>
            </div>
        </div>
    </div> 

   
    <div class="modal fade" id="editCabinaModal" tabindex="-1" aria-labelledby="editCabinaModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="editCabinaModalLabel">Editar Cabina</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    <form id="editCabinaForm" method="POST" action="">
                        @csrf
                        @method('PUT')
                        <input type="hidden" id="edit_id" name="id">
    
                        <div class="mb-3">
                            <label for="edit_nombre_cabina" class="form-label">Nombre</label>
                            <input type="text" class="form-control {{ $errors->edit->has('nombre') ? 'is-invalid' : '' }}" id="edit_nombre_cabina" name="nombre" required>
                            @if ($errors->edit->has('nombre'))
                                <div class="invalid-feedback">{{ $errors->edit->first('nombre') }}</div>
                            @endif
                        </div>
    
                        <div class="mb-3">
                            <label for="edit_clase_cabina" class="form-label">tipo</label>
                            <input type="text" class="form-control {{ $errors->edit->has('clase') ? 'is-invalid' : '' }}" id="edit_clase_cabina" name="clase">
                            @if ($errors->edit->has('clase'))
                                <div class="invalid-feedback">{{ $errors->edit->first('clase') }}</div>
                            @endif
                        </div>
 
                        <div class="mb-3">
                            <label class="form-label">Especialidades</label>

                            @forelse (($experienciasPorClase ?? []) as $claseName => $tipos)
                                @php $safeClassId = str_replace(' ', '_', strtolower($claseName)); @endphp
                                <div class="form-check">
                                    <input class="form-check-input edit-main-cat" type="checkbox" value="{{ $claseName }}" id="edit_cat_{{ $safeClassId }}" data-target="edit_subtipo_{{ $safeClassId }}">
                                    <label class="form-check-label" for="edit_cat_{{ $safeClassId }}">{{ ucfirst($claseName) }}</label>
                                </div>
                                <div id="edit_subtipo_{{ $safeClassId }}" class="ps-3 mt-2 edit-subtipo-container" style="display:none;">
                                    @foreach ($tipos as $idx => $tipo)
                                        <div class="form-check">
                                            <input class="form-check-input edit-subtype-checkbox" type="checkbox" name="clases_actividad[]" value="{{ $tipo }}" id="edit_{{ $safeClassId }}_tipo_{{ $idx }}">
                                            <label class="form-check-label" for="edit_{{ $safeClassId }}_tipo_{{ $idx }}">{{ $tipo }}</label>
                                        </div>
                                    @endforeach
                                </div>
                            @empty
                                <p class="text-muted">No hay especialidades disponibles.</p>
                            @endforelse
                        </div>
                        
                        <div class="mb-3">
                            <label for="edit_activo" class="form-label">Estado</label>
                            <select class="form-select {{ $errors->edit->has('activo') ? 'is-invalid' : '' }}" id="edit_activo" name="activo">
                                <option value="1">Activo</option>
                                <option value="0">Inactivo</option>
                            </select>
                            @if ($errors->edit->has('activo'))
                                <div class="invalid-feedback">{{ $errors->edit->first('activo') }}</div>
                            @endif
                        </div>
    
                        <input type="hidden" id="edit_spa_id" name="spa_id" value="{{ session('current_spa_id') }}">
                        <input type="hidden" name="_from_edit" value="1">
    
                        <button type="submit" class="btn btn-success">Guardar Cambios</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
@include('components.alert-modal')
@endsection

@section('scripts')
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/js/gestores/cabinas.js'])
    @endif
    @include('components.session-alert')
@endsection

<script>
    // Datos de experiencias agrupadas por clase (desde el controlador)
    window.ExperienciasPorClase = @json($experienciasPorClase ?? []);
    window.CabinaOldSelected = @json(old('clases_actividad', []));

    function populateSubtypes(category, targetSelectId, selected = []) {
        const select = document.getElementById(targetSelectId);
        if (!select) return;
        // Limpiar opciones
        select.innerHTML = '';

        if (!category || !window.ExperienciasPorClase[category]) return;

        const tipos = window.ExperienciasPorClase[category];
        tipos.forEach(function (tipo) {
            const opt = document.createElement('option');
            opt.value = tipo;
            opt.text = tipo;
            if (selected.includes(tipo)) opt.selected = true;
            select.appendChild(opt);
        });
    }

    document.addEventListener('DOMContentLoaded', function () {
        const claseSelect = document.getElementById('clase_select');
        const subtipoSelect = document.getElementById('subtipo_select');

        // Manejo para checkboxes de categorías principales
        const mainCats = document.querySelectorAll('.main-cat');
        mainCats.forEach(function (chk) {
            chk.addEventListener('change', function () {
                const targetId = this.getAttribute('data-target');
                const container = document.getElementById(targetId);
                if (!container) return;
                container.style.display = this.checked ? 'block' : 'none';
            });
        });

        // Si existen subtipo-checkboxes marcar la categoría padre cuando se seleccionan
        document.querySelectorAll('.subtipo-container .subtype-checkbox').forEach(function (sub) {
            sub.addEventListener('change', function () {
                // encontrar contenedor padre
                const container = this.closest('.subtipo-container');
                if (!container) return;
                const id = container.id; // p.ej. subtipo_masajes
                const catId = 'cat_' + id.replace('subtipo_', '');
                const parent = document.getElementById(catId);
                if (!parent) return;
                // si al menos un checkbox en container está marcado, marcar parent
                const anyChecked = Array.from(container.querySelectorAll('.subtype-checkbox')).some(c => c.checked);
                parent.checked = anyChecked;
            });
        });

        // Listeners para edit modal checkboxes (mismo comportamiento)
        document.querySelectorAll('.edit-main-cat').forEach(function (chk) {
            chk.addEventListener('change', function () {
                const targetId = this.getAttribute('data-target');
                const container = document.getElementById(targetId);
                if (!container) return;
                container.style.display = this.checked ? 'block' : 'none';
            });
        });

        document.querySelectorAll('.edit-subtipo-container .edit-subtype-checkbox').forEach(function (sub) {
            sub.addEventListener('change', function () {
                const container = this.closest('.edit-subtipo-container');
                if (!container) return;
                const id = container.id;
                const catId = 'edit_cat_' + id.replace('edit_subtipo_', '');
                const parent = document.getElementById(catId);
                if (!parent) return;
                const anyChecked = Array.from(container.querySelectorAll('.edit-subtype-checkbox')).some(c => c.checked);
                parent.checked = anyChecked;
            });
        });

        // Si vienen valores old (por validación fallida), preseleccionar en creación
        if (Array.isArray(window.CabinaOldSelected) && window.CabinaOldSelected.length) {
            window.CabinaOldSelected.forEach(function (val) {
                const chk = Array.from(document.querySelectorAll('.subtype-checkbox')).find(c => c.value === val);
                if (chk) {
                    chk.checked = true;
                    const container = chk.closest('.subtipo-container');
                    if (container) container.style.display = 'block';
                    const parentId = 'cat_' + (container?.id.replace('subtipo_', '') || '');
                    const parent = document.getElementById(parentId);
                    if (parent) parent.checked = true;
                }
            });
        }

        // Edit modal: cuando se abre, preseleccionar checkboxes y mostrar contenedores
        const editModal = document.getElementById('editCabinaModal');
        if (editModal) {
            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                if (!button) return;
                const id = button.getAttribute('data-id');
                let clasesActividad = [];
                try {
                    clasesActividad = JSON.parse(button.getAttribute('data-clases-actividad') || '[]');
                } catch (e) { clasesActividad = []; }

                const editNombre = document.getElementById('edit_nombre_cabina');
                const editForm = document.getElementById('editCabinaForm');

                if (editNombre) editNombre.value = button.getAttribute('data-nombre') || '';
                if (editForm) editForm.action = '/cabinas/' + id;

                // Limpiar selección previa
                document.querySelectorAll('.edit-subtype-checkbox').forEach(ch => ch.checked = false);
                document.querySelectorAll('.edit-subtipo-container').forEach(c => c.style.display = 'none');
                document.querySelectorAll('.edit-main-cat').forEach(c => c.checked = false);

                // Marcar checkboxes que coincidan con clasesActividad
                clasesActividad.forEach(function (val) {
                    const checkbox = Array.from(document.querySelectorAll('.edit-subtype-checkbox')).find(ch => ch.value === val);
                    if (checkbox) checkbox.checked = true;
                });

                // Mostrar contenedores y marcar padres según lo seleccionado
                document.querySelectorAll('.edit-subtipo-container').forEach(function (container) {
                    const anyChecked = Array.from(container.querySelectorAll('.edit-subtype-checkbox')).some(c => c.checked);
                    if (anyChecked) {
                        container.style.display = 'block';
                        const catId = 'edit_cat_' + container.id.replace('edit_subtipo_', '');
                        const parent = document.getElementById(catId);
                        if (parent) parent.checked = true;
                    }
                });
            });
        }
    });
</script>

