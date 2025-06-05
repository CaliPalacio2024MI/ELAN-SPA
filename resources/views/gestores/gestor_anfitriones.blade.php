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
    'resources/css/menus/' . $spaCss . '/menu_styles.css',
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

        <table class="table-responsive custom-table">
            <thead>
                <tr>
                    <th>RFC</th>
                    <th>Nombre</th>
                    <th>apellido paterno</th>
                    <th>Rol</th>
                    <th>Departamento</th>
                    <th>Categoria</th>
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
                            <label for="departamento" class="form-label">Departamento</label>
                            <select class="form-select {{ $errors->create->has('departamento') ? 'is-invalid' : '' }}" id="departamento" name="departamento" required>
                                <option value="spa" {{ (!$fromEdit && old('departamento') == 'spa') ? 'selected' : '' }}>Spa</option>
                                <option value="gym" {{ (!$fromEdit && old('departamento') == 'gym') ? 'selected' : '' }}>Gimnasio</option>
                                <option value="valet" {{ (!$fromEdit && old('departamento') == 'valet') ? 'selected' : '' }}>Valet</option>
                                <option value="salon de belleza" {{ (!$fromEdit && old('departamento') == 'salon de belleza') ? 'selected' : '' }}>Salón de Belleza</option>
                            </select>
                            @error('departamento', 'create')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <div class="mb-3">
                                <label class="form-label">Categorías</label>
                                @foreach ($clasesDisponibles as $clase)
                                @php $inputId = 'crear_clase_' . $loop->index; @endphp
                                    <div class="form-check">
                                        <input class="form-check-input"
                                            type="checkbox"
                                            name="clases_actividad[]"
                                            value="{{ $clase }}"
                                            id="{{ $inputId }}"
                                            {{ (!$fromEdit && in_array($clase, old('clases_actividad', []))) ? 'checked' : '' }}>
                                        <label class="form-check-label" for="{{ $inputId }}">
                                            {{ $clase }}
                                        </label>
                                    </div>
                                @endforeach
                            </div>
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
                        <select class="form-select {{ $errors->edit->has('departamento') ? 'is-invalid' : '' }}" id="edit_departamento" name="departamento" required>
                            <option value="spa">Spa</option>
                            <option value="gym">Gimnasio</option>
                            <option value="valet">Valet</option>
                            <option value="salon de belleza">Salón de Belleza</option>
                        </select>
                        @if ($errors->edit->has('departamento'))
                            <div class="invalid-feedback">{{ $errors->edit->first('departamento') }}</div>
                        @endif
                    </div>

                    <div class="mb-3">
                        <label for="edit_clases_actividad" class="form-label">Categoria</label>
                        <div class="mb-3">
                            @foreach ($clasesDisponibles as $clase)
                                @php $inputId = 'editar_clase_' . $loop->index; @endphp
                                <input class="form-check-input"
                                type="checkbox"
                                name="clases_actividad[]"
                                value="{{ $clase }}"
                                id="{{ $inputId }}"
                                {{ in_array($clase, old('clases_actividad', []) ?: []) ? 'checked' : '' }}>
                                <label class="form-check-label" for="{{ $inputId }}">
                                    {{ $clase }}
                                </label>
                            @endforeach
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
    
@endsection

