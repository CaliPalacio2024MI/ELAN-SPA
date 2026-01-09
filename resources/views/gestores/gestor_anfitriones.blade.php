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

@endsection

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
    'resources/css/gestores/g_anfitriones_styles.css',
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

//<script>
    window.errors = {
        create: @json($errors->create->any()),
        edit: @json($errors->edit->any())
    };
</script>

@section('content')
<div id="simpleAlertContainer" class="position-fixed top-0 start-50 translate-middle-x mt-3" style="z-index: 1055; width: 90%; max-width: 500px;"></div>

<header class="main-header">
    <h2>GESTIONAR ANFITRIONES</h2>
</header>
    <div class="table-container">

        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#anfitrionModal">
            Nuevo Anfitrión
        </button>

        <form method="GET" action="{{ route('anfitriones.index') }}" class="search-form d-flex mb-3">
            <input type="text" name="search" value="{{ request('search') }}" class="form-control search-input" placeholder="Buscar por nombre, RFC, rol, departamento o categoría...">
            <button type="submit" class="btn btn-buscar">
                <i class="fas fa-search"></i>
            </button>
        </form>

        <table class="table-responsive custom-table">
            <thead>
                <tr>
                    <th>RFC</th>
                    <th>Nombre</th>
                    <th>Apellido paterno</th>
                    <th>Rol</th>
                    <th>Áreas</th>
                    <th>% Servicio</th>
                    <th>Especialidades</th>
                    <th>Accesos</th>
                    <th>Activo</th>
                    <th>Acciones</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($anfitriones as $anfitrion)
                    <tr>
                        <td>{{ $anfitrion->RFC }}</td>
                        <td>{{ $anfitrion->nombre_usuario }}</td>
                        <td>{{ $anfitrion->apellido_paterno }}</td>
                        <td>{{ ucfirst($anfitrion->rol) }}</td>
                        <td>{{ ucfirst($anfitrion->operativo?->departamento ?? '—') }}</td>
                        <td>{{ $anfitrion->porcentaje_servicio ? $anfitrion->porcentaje_servicio . '%' : '15%' }}</td>
                        <td>
                            @if (!empty($anfitrion->operativo?->clases_actividad))
                                {{ implode(', ', $anfitrion->operativo->clases_actividad) }}
                            @else
                                —
                            @endif
                        </td>
                        <td>
                            @if (!empty($anfitrion->spaNombres))
                                {{ implode(', ', array_map('ucfirst', $anfitrion->spaNombres)) }}
                            @else
                                —
                            @endif
                        </td>                        
                        <td>
                            <span 
                                class="badge toggle-estado {{ $anfitrion->activo ? 'bg-success' : 'bg-danger' }}"
                                data-id="{{ $anfitrion->id }}"
                                style="cursor: pointer;"
                            >                           
                                {{ $anfitrion->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        <td class="text-end">
                            @if ($anfitrion->rol === 'anfitrion')
                            <a href="{{ route('anfitriones.horario.edit', $anfitrion->id) }}" class="btn btn-info">
                                <i class="fa-solid fa-clock icono-accion-pequeno"></i>    
                            </a>
                            @endif

                            <button type="button" class="btn btn-warning btn-edit" data-bs-toggle="modal" 
                                data-bs-target="#editAnfitrionModal"
                                data-id="{{ $anfitrion->id }}"
                                data-rfc="{{ $anfitrion->RFC }}"
                                data-nombre="{{ $anfitrion->nombre_usuario }}"
                                data-apellido-paterno="{{ $anfitrion->apellido_paterno }}"
                                data-apellido-materno="{{ $anfitrion->apellido_materno}}"
                                data-rol="{{ $anfitrion->rol }}"
                                data-departamento="{{($anfitrion->operativo?->departamento ?? '—') }}"
                                data-porcentaje-servicio="{{ $anfitrion->porcentaje_servicio ?? '' }}"
                                data-clases='@json($anfitrion->operativo?->clases_actividad ?? [])'
                                data-accesos='@json($anfitrion->accesos)'
                                data-activo="{{ $anfitrion->activo }}">
                                <i class="fa-solid fa-pen-to-square icono-accion-pequeno"></i>
                            </button>

                            <form action="{{ route('anfitriones.destroy', $anfitrion) }}" method="POST" class="d-inline">
                                @csrf @method('DELETE')
                                <button type="submit" class="btn btn-danger btn-sm btn-delete">
                                    <i class="fa-solid fa-delete-left icono-accion-pequeno" style="color: #ac0505;"></i>  
                                </button>
                            </form>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="modal fade" id="anfitrionModal" tabindex="-1" aria-labelledby="anfitrionModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="anfitrionModalLabel">Nuevo Anfitrión</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form action="{{ route('anfitriones.store') }}" method="POST">
                        @csrf
                        @php $fromEdit = old('_from_edit') == '1'; @endphp
          
                        <div class="mb-3">
                            <label for="RFC" class="form-label">RFC</label>
                            <input type="text" class="form-control {{ $errors->create->has('RFC') ? 'is-invalid' : '' }}" id="RFC" name="RFC" value="{{ $fromEdit ? '' : old('RFC') }}" required>
                            @error('RFC', 'create')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="nombre_usuario" class="form-label">Nombre de Usuario</label>
                            <input type="text" class="form-control {{ $errors->create->has('nombre_usuario') ? 'is-invalid' : '' }}" id="nombre_usuario" name="nombre_usuario" value="{{ $fromEdit ? '' : old('nombre_usuario') }}" required>
                            @error('nombre_usuario', 'create')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="apellido_paterno" class="form-label">Apellido Paterno</label>
                            <input type="text" class="form-control {{ $errors->create->has('apellido_paterno') ? 'is-invalid' : '' }}" id="apellido_paterno" name="apellido_paterno" value="{{ $fromEdit ? '' : old('apellido_paterno') }}" required>
                            @error('apellido_paterno', 'create')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="apellido_materno" class="form-label">Apellido Materno</label>
                            <input type="text" class="form-control {{ $errors->create->has('apellido_materno') ? 'is-invalid' : '' }}" id="apellido_materno" name="apellido_materno" value="{{ $fromEdit ? '' : old('apellido_materno') }}">
                            @error('apellido_materno', 'create')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="rol" class="form-label">Rol</label>
                            <select class="form-select {{ $errors->create->has('rol') ? 'is-invalid' : '' }}" id="rol" name="rol" required>
                                <option value="administrador" {{ (!$fromEdit && old('rol') == 'administrador') ? 'selected' : '' }}>Administrador</option>
                                <option value="recepcionista" {{ (!$fromEdit && old('rol') == 'recepcionista') ? 'selected' : '' }}>Recepcionista</option>
                                <option value="anfitrion" {{ (!$fromEdit && old('rol') == 'anfitrion') ? 'selected' : '' }}>Anfitrión</option>
                                @if (Auth::user()?->rol === 'master')
                                    <option value="master" {{ (!$fromEdit && old('rol') == 'master') ? 'selected' : '' }}>Master</option>
                                @endif
                            </select>
                            @error('rol', 'create')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="departamento" class="form-label">Áreas</label>
                            <select class="form-select {{ $errors->create->has('departamento') ? 'is-invalid' : '' }}" id="departamento" name="departamento">
                                <option value="" disabled selected>Selecciona una área</option>
                                @forelse ($departamentosDisponibles as $departamento)
                                    <option value="{{ $departamento }}" {{ (!$fromEdit && old('departamento') == $departamento) ? 'selected' : '' }}>
                                        {{ $departamento }}
                                    </option>
                                @empty
                                    <option value="" disabled>No hay áreas disponibles.</option>
                                @endforelse
                            </select>
                            @error('departamento', 'create')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="porcentaje_servicio" class="form-label">Porcentaje por cargo del servicio</label>
                            <input type="number" step="0.01" min="0" max="100" class="form-control {{ $errors->create->has('porcentaje_servicio') ? 'is-invalid' : '' }}" id="porcentaje_servicio" name="porcentaje_servicio" value="{{ $fromEdit ? '' : old('porcentaje_servicio') }}">
                            @error('porcentaje_servicio', 'create')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Especialidades</label>

                            @forelse (($experienciasPorClase ?? []) as $claseName => $tipos)
                                @php
                                    $safeClassId = str_replace(' ', '_', strtolower($claseName));
                                @endphp
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
                            <label for="password" class="form-label">Contraseña</label>
                            <input type="password" class="form-control {{ $errors->create->has('password') ? 'is-invalid' : '' }}" id="password" name="password" required>
                            @error('password', 'create')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3 form-check">
                            <input type="checkbox" class="form-check-input" id="accesosCheckbox">
                            <label class="form-check-label" for="accesosCheckbox">Agregar accesos adicionales</label>
                        </div>

                        <div class="mb-3" id="accesosContainer" style="display: none;">
                            <label class="form-label">Selecciona spas adicionales</label>
                            
                            @foreach ($spasDisponibles as $spa)
                                <div class="form-check">
                                    <input class="form-check-input {{ $errors->create->has('accesos') || $errors->create->has("accesos.$loop->index") ? 'is-invalid' : '' }}"
                                           type="checkbox"
                                           name="accesos[]"
                                           value="{{ $spa->id }}"
                                           id="create_spa_{{ $spa->id }}"
                                           {{ (!$fromEdit && in_array($spa->id, old('accesos', []))) ? 'checked' : '' }}>
                                    <label class="form-check-label" for="create_spa_{{ $spa->id }}">
                                        {{ ucfirst($spa->nombre) }}
                                    </label>
                                </div>
                            @endforeach
                        
                            @if ($errors->create->has('accesos'))
                                <div class="text-danger small">{{ $errors->create->first('accesos') }}</div>
                            @elseif ($errors->create->has('accesos.*'))
                                <div class="text-danger small">{{ $errors->create->first('accesos.*') }}</div>
                            @endif
                        </div>
                                                    
                        <input type="hidden" name="spa_id" value="{{ session('current_spa_id') }}">
                        <button type="submit" class="btn btn-success" onclick="this.disabled = true; this.innerText='Guardando...'; this.form.submit();">Guardar</button>
                        <input type="hidden" name="_from_edit" value="0">
                    </form>
                </div>
            </div>
        </div>
    </div>

   
<div class="modal fade" id="editAnfitrionModal" tabindex="-1" aria-labelledby="editAnfitrionModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editAnfitrionModalLabel">Editar Anfitrión</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="editAnfitrionForm" method="POST" action="">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit_id" name="id">

                    <div class="mb-3">
                        <label for="edit_RFC" class="form-label">RFC</label>
                        <input type="text" class="form-control {{ $errors->edit->has('RFC') ? 'is-invalid' : '' }}" id="edit_RFC" name="RFC" required>
                        @if ($errors->edit->has('RFC'))
                            <div class="invalid-feedback">{{ $errors->edit->first('RFC') }}</div>
                        @endif
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_nombre_usuario" class="form-label">Nombre de Usuario</label>
                        <input type="text" class="form-control {{ $errors->edit->has('nombre_usuario') ? 'is-invalid' : '' }}" id="edit_nombre_usuario" name="nombre_usuario" required>
                        @if ($errors->edit->has('nombre_usuario'))
                            <div class="invalid-feedback">{{ $errors->edit->first('nombre_usuario') }}</div>
                        @endif
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_apellido_paterno" class="form-label">Apellido Paterno</label>
                        <input type="text" class="form-control {{ $errors->edit->has('apellido_paterno') ? 'is-invalid' : '' }}" id="edit_apellido_paterno" name="apellido_paterno" required>
                        @if ($errors->edit->has('apellido_paterno'))
                            <div class="invalid-feedback">{{ $errors->edit->first('apellido_paterno') }}</div>
                        @endif
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_apellido_materno" class="form-label">Apellido Materno</label>
                        <input type="text" class="form-control {{ $errors->edit->has('apellido_materno') ? 'is-invalid' : '' }}" id="edit_apellido_materno" name="apellido_materno">
                        @if ($errors->edit->has('apellido_materno'))
                            <div class="invalid-feedback">{{ $errors->edit->first('apellido_materno') }}</div>
                        @endif
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_rol" class="form-label">Rol</label>
                        <select class="form-select {{ $errors->edit->has('rol') ? 'is-invalid' : '' }}" id="edit_rol" name="rol" required>
                            <option value="administrador">Administrador</option>
                            <option value="recepcionista">Recepcionista</option>
                            <option value="anfitrion">Anfitrion</option>
                            @if (Auth::user()?->rol === 'master')
                            <option value="master" >Master</option>
                            @endif
                        </select>
                        @if ($errors->edit->has('rol'))
                            <div class="invalid-feedback">{{ $errors->edit->first('rol') }}</div>
                        @endif
                    </div>
                    
                    <div class="mb-3">
                        <label for="edit_departamento" class="form-label">Departamento</label>
                        <select class="form-select {{ $errors->edit->has('departamento') ? 'is-invalid' : '' }}" id="edit_departamento" name="departamento">
                            <option value="" disabled>Selecciona un departamento</option>
                            @forelse ($departamentosDisponibles as $departamento)
                                <option value="{{ $departamento }}">
                                    {{ $departamento }}
                                </option>
                            @empty
                                <option value="" disabled>No hay departamentos disponibles.</option>
                            @endforelse
                        </select>
                        @if ($errors->edit->has('departamento'))
                            <div class="invalid-feedback">{{ $errors->edit->first('departamento') }}</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="edit_porcentaje_servicio" class="form-label">Porcentaje por cargo del servicio</label>
                        <input type="number" step="0.01" min="0" max="100" class="form-control {{ $errors->edit->has('porcentaje_servicio') ? 'is-invalid' : '' }}" id="edit_porcentaje_servicio" name="porcentaje_servicio">
                        @if ($errors->edit->has('porcentaje_servicio'))
                            <div class="invalid-feedback">{{ $errors->edit->first('porcentaje_servicio') }}</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="edit_clases_actividad" class="form-label">Categoria</label>
                        <div class="mb-3">
                            @php
                                $mapCategorias = ['masajes' => [], 'faciales' => [], 'corporales' => []];
                                foreach (($experienciasPorClase ?? []) as $claseName => $tipos) {
                                    $k = strtolower($claseName);
                                    if (strpos($k, 'masaj') !== false) {
                                        $mapCategorias['masajes'] = $tipos;
                                    } elseif (strpos($k, 'facial') !== false) {
                                        $mapCategorias['faciales'] = $tipos;
                                    } elseif (strpos($k, 'corpor') !== false || strpos($k, 'corp') !== false) {
                                        $mapCategorias['corporales'] = $tipos;
                                    }
                                }
                            @endphp

                            <div class="form-check">
                                <input class="form-check-input edit-main-cat" type="checkbox" value="masajes" id="edit_cat_masajes" data-target="edit_subtipo_masajes">
                                <label class="form-check-label" for="edit_cat_masajes">Masajes</label>
                            </div>
                            <div id="edit_subtipo_masajes" class="ps-3 mt-2 edit-subtipo-container" style="display:none;">
                                @foreach ($mapCategorias['masajes'] as $idx => $tipo)
                                    <div class="form-check">
                                        <input class="form-check-input edit-subtype-checkbox" type="checkbox" name="clases_actividad[]" value="{{ $tipo }}" id="edit_masaje_tipo_{{ $idx }}">
                                        <label class="form-check-label" for="edit_masaje_tipo_{{ $idx }}">{{ $tipo }}</label>
                                    </div>
                                @endforeach
                            </div>

                            <div class="form-check mt-2">
                                <input class="form-check-input edit-main-cat" type="checkbox" value="faciales" id="edit_cat_faciales" data-target="edit_subtipo_faciales">
                                <label class="form-check-label" for="edit_cat_faciales">Faciales</label>
                            </div>
                            <div id="edit_subtipo_faciales" class="ps-3 mt-2 edit-subtipo-container" style="display:none;">
                                @foreach ($mapCategorias['faciales'] as $idx => $tipo)
                                    <div class="form-check">
                                        <input class="form-check-input edit-subtype-checkbox" type="checkbox" name="clases_actividad[]" value="{{ $tipo }}" id="edit_facial_tipo_{{ $idx }}">
                                        <label class="form-check-label" for="edit_facial_tipo_{{ $idx }}">{{ $tipo }}</label>
                                    </div>
                                @endforeach
                            </div>

                            <div class="form-check mt-2">
                                <input class="form-check-input edit-main-cat" type="checkbox" value="corporales" id="edit_cat_corporales" data-target="edit_subtipo_corporales">
                                <label class="form-check-label" for="edit_cat_corporales">Corporales</label>
                            </div>
                            <div id="edit_subtipo_corporales" class="ps-3 mt-2 edit-subtipo-container" style="display:none;">
                                @foreach ($mapCategorias['corporales'] as $idx => $tipo)
                                    <div class="form-check">
                                        <input class="form-check-input edit-subtype-checkbox" type="checkbox" name="clases_actividad[]" value="{{ $tipo }}" id="edit_corporal_tipo_{{ $idx }}">
                                        <label class="form-check-label" for="edit_corporal_tipo_{{ $idx }}">{{ $tipo }}</label>
                                    </div>
                                @endforeach
                            </div>
                    </div>

                    <div class="mb-3">
                        <label for="edit_password" class="form-label">Nueva Contraseña (opcional)</label>
                        <input type="password" class="form-control {{ $errors->edit->has('password') ? 'is-invalid' : '' }}" id="edit_password" name="password">
                        @if ($errors->edit->has('password'))
                            <div class="invalid-feedback">{{ $errors->edit->first('password') }}</div>
                        @endif
                    </div>
                                                      
                    <div class="mb-3 form-check">
                        <input type="checkbox" class="form-check-input" id="edit_accesosCheckbox">
                        <label class="form-check-label" for="edit_accesosCheckbox">Modificar accesos adicionales</label>
                    </div>
                    <div class="mb-3" id="edit_accesosContainer">
                        <label class="form-label">Selecciona spas adicionales</label>
                        @foreach ($spasDisponibles as $spa)
                            <div class="form-check">
                                <input class="form-check-input {{ $errors->edit->has('accesos') || $errors->edit->has("accesos.$loop->index") ? 'is-invalid' : '' }}"
                                       type="checkbox"
                                       name="accesos[]"
                                       value="{{ $spa->id }}"
                                       id="edit_spa_{{ $spa->id }}">
                                <label class="form-check-label" for="edit_spa_{{ $spa->id }}">
                                    {{ ucfirst($spa->nombre) }}
                                </label>
                            </div>
                        @endforeach
                    
                        @if ($errors->edit->has('accesos'))
                            <div class="text-danger small">{{ $errors->edit->first('accesos') }}</div>
                        @elseif ($errors->edit->has('accesos.*'))
                            <div class="text-danger small">{{ $errors->edit->first('accesos.*') }}</div>
                        @endif
                    </div>
                                                         
                    <div class="mb-3">
                        <label for="edit_activo" class="form-label">Estado</label>
                        <select class="form-select" id="edit_activo" name="activo">
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>
                    <input type="hidden" id="edit_spa_id" name="spa_id" value="{{ session('current_spa_id') }}">
                    <button type="submit" class="btn btn-success" onclick="this.disabled = true; this.innerText='Guardando...'; this.form.submit();">Guardar</button>
                    <input type="hidden" name="_from_edit" value="1">
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
@if (session('mensaje_exito'))
<script>
    window.mensaje_exito = @json(session('mensaje_exito'));
</script>
@endif

@section('scripts')
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/js/gestores/anfitriones.js'])
    @endif
@if (session('success'))
<script>
    setTimeout(() => {
        ModalAlerts.show("{{ session('success') }}", { type: 'success' });
    }, 300); // espera a que se cierre cualquier modal previo
</script>
@endif
    
<script>
    window.ExperienciasPorClase = @json($experienciasPorClase ?? []);
    window.AnfitrionOldSelected = @json(old('clases_actividad', []));

    document.addEventListener('DOMContentLoaded', function () {
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

        // auto-checar padre si algún subtipo está marcado
        document.querySelectorAll('.subtipo-container .subtype-checkbox').forEach(function (sub) {
            sub.addEventListener('change', function () {
                const container = this.closest('.subtipo-container');
                if (!container) return;
                const id = container.id; // p.ej. subtipo_masajes
                const catId = 'cat_' + id.replace('subtipo_', '');
                const parent = document.getElementById(catId);
                if (!parent) return;
                const anyChecked = Array.from(container.querySelectorAll('.subtype-checkbox')).some(c => c.checked);
                parent.checked = anyChecked;
            });
        });

        // Edit modal listeners
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
        if (Array.isArray(window.AnfitrionOldSelected) && window.AnfitrionOldSelected.length) {
            window.AnfitrionOldSelected.forEach(function (val) {
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
        const editModal = document.getElementById('editAnfitrionModal');
        if (editModal) {
            editModal.addEventListener('show.bs.modal', function (event) {
                const button = event.relatedTarget;
                if (!button) return;
                const id = button.getAttribute('data-id');
                let clasesActividad = [];
                try {
                    clasesActividad = JSON.parse(button.getAttribute('data-clases') || '[]');
                } catch (e) { clasesActividad = []; }

                const editForm = document.getElementById('editAnfitrionForm');
                if (editForm) editForm.action = '/anfitriones/' + id;

                // Cargar porcentaje servicio
                const porcentaje = button.getAttribute('data-porcentaje-servicio');
                const inputPorcentaje = document.getElementById('edit_porcentaje_servicio');
                if (inputPorcentaje) inputPorcentaje.value = porcentaje || '';

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
@endsection
