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
        'resources/css/menus/' . $spaCss . '/menu_styles.css',
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

        <table class="table-responsive custom-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>tipo</th>
                    <th>Categorias</th>
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
                            <label for="clase" class="form-label">tipo</label>
                            <input type="text" class="form-control {{ $errors->create->has('clase') ? 'is-invalid' : '' }}" id="clase" name="clase" value="{{ $fromEdit ? '' : old('clase') }}">
                            @error('clase', 'create')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
    
                        <div class="mb-3">
                            <label for="clases_actividad" class="form-label">Categoria</label>
                            @foreach ($clasesDisponibles as $clase)
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="clases_actividad[]" value="{{ $clase }}" id="clase_{{ $loop->index }}">
                                <label class="form-check-label" for="clase_{{ $loop->index }}">
                                    {{ $clase }}
                                </label>
                            </div>
                        @endforeach

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
                            <label for="edit_clases_actividad" class="form-label">Categorias</label>
                            @foreach ($clasesDisponibles as $clase)
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" name="clases_actividad[]" value="{{ $clase }}" id="clase_{{ $loop->index }}">
                                    <label class="form-check-label" for="clase_{{ $loop->index }}">
                                        {{ $clase }}
                                    </label>
                                </div>
                            @endforeach
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

