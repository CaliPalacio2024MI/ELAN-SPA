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
        $experienceLogo = asset("images/$spasFolder/logo.png");
    @endphp
    <img src="{{ $experienceLogo }}" alt="Logo de {{ ucfirst($spasFolder) }}">
@endsection

@section('css')
    @php
        $spaCss = session('current_spa') ?? strtolower(optional(Auth::user()->spa)->nombre);
    @endphp
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite([
        'resources/css/menus/themes/' . $spaCss . '.css',
        'resources/css/gestores/g_experiencias_styles.css',
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
<datalist id="categoriaList">
    <option value="Masajes">
    <option value="Faciales">
    <option value="Corporales">
    <option value="Rituales">
    <option value="Alberca">
    <option value="Add on">
    <option value="A. Humedas">
</datalist>
<header class="main-header">
    <h2>GESTIONAR EXPERIENCIAS</h2>
</header>
    <div class="table-container">
         
        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#experienceModal">
         Nueva Experiencia
        </button>

        <table class="table-responsive custom-table">
            <thead>
                <tr>
                    <th>Nombre</th>
                    <th>Categoria</th>
                    <th>Duración</th>
                    <th>Precio</th>
                    <th>Color</th> 
                    <th>Descripción</th> 
                    <th>Acciones</th>
                    <th>Estado</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($experiences as $experience)
                    <tr>
                        <td>{{ $experience->nombre }}</td>
                        <td>{{ $experience->clase }}</td>
                        <td>{{ $experience->duracion }} min</td>
                        <td>${{ number_format($experience->precio, 2) }}</td>
                        <td>
                            <div style="width: 24px; height: 24px; border-radius: 4px; border: 1px solid #ccc; background-color: {{ $experience->color }};"></div>
                        </td>                        
                        <td>
                            <div class="descripcion-limite">
                                {{ $experience->descripcion }}
                            </div>
                        </td>
                        <td>
                            <span 
                                class="badge toggle-estado {{ $experience->activo ? 'bg-success' : 'bg-danger' }}"
                                data-id="{{ $experience->id }}"
                                style="cursor: pointer;"
                            >
                                {{ $experience->activo ? 'Activo' : 'Inactivo' }}
                            </span>
                        </td>
                        
                        <td>
                            <button type="button" class="btn btn-warning btn-edit"
                                data-bs-toggle="modal"
                                data-bs-target="#editExperienceModal"
                                data-id="{{ $experience->id }}"
                                data-nombre="{{ $experience->nombre }}"
                                data-clase="{{ $experience->clase }}"
                                data-duracion="{{ $experience->duracion }}"
                                data-precio="{{ $experience->precio }}"
                                data-color="{{ $experience->color ?? '#000000' }}"
                                data-descripcion="{{ $experience->descripcion }}"
                                data-activo="{{ $experience->activo }}">
                                <i class="fa-solid fa-pen-to-square icono-accion-pequeno"></i>
                            </button>
            
                            <form action="{{ route('experiences.destroy', $experience) }}" method="POST" class="d-inline" onsubmit="return confirmarEliminacion(event)">
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

<div class="modal fade" id="experienceModal" tabindex="-1" aria-labelledby="experienceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="experienceModalLabel">Nueva Experiencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
               
                <form id="experienceForm" action="{{ route('experiences.store') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <label for="nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="nombre" name="nombre" required>
                    </div>
                    <div class="mb-3">
                        <label for="clase" class="form-label">Clase</label>

                        <div id="clase-select-container">
                            <select class="form-select" id="clase_select" name="clase">
                                @foreach ($experiences->pluck('clase')->unique() as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="toggleClaseInput">Nueva clase</button>
                        </div>

                        <div id="clase-input-container" class="d-none">
                            <input type="text" class="form-control mt-2" id="clase_input" name="clase" placeholder="Nueva clase">
                        </div>
                    </div>
                                     
                    <div class="mb-3">
                        <label for="duracion" class="form-label">Duración (minutos)</label>
                        <input type="number" class="form-control" id="duracion" name="duracion" required>
                    </div>
                    <div class="mb-3">
                        <label for="precio" class="form-label">Precio</label>
                        <input type="number" step="0.01" class="form-control" id="precio" name="precio" required>
                    </div>
                    <div class="mb-3">
                        <label for="color" class="form-label">Color</label>
                        <input type="color" class="form-control form-control-color" id="color" name="color" value="#000000" title="Elige un color">
                    </div>                    
                    <div class="mb-3">
                        <label for="descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="descripcion" name="descripcion" rows="3"></textarea>
                    </div>                   
                    <button type="submit" class="btn btn-success">Guardar Experiencia</button>
                </form>          
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="editExperienceModal" tabindex="-1" aria-labelledby="editExperienceModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editExperienceModalLabel">Editar Experiencia</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
               
                <form id="editExperienceForm" method="POST" action="">
                    @csrf
                    @method('PUT')
                    <input type="hidden" id="edit_id" name="id">
                    <div class="mb-3">
                        <label for="edit_nombre" class="form-label">Nombre</label>
                        <input type="text" class="form-control" id="edit_nombre" name="nombre" required>
                    </div>

                    <div class="mb-3">
                        <label for="edit_clase" class="form-label">Clase</label>

                        <div id="edit-clase-select-container">
                            <select class="form-select" id="edit_clase_select" name="clase">
                                @foreach ($experiences->pluck('clase')->unique() as $cat)
                                    <option value="{{ $cat }}">{{ $cat }}</option>
                                @endforeach
                            </select>
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="edit_toggleClaseInput">Nueva clase</button>
                        </div>

                        <div id="edit-clase-input-container" class="d-none">
                            <input type="text" class="form-control mt-2" id="edit_clase_input" name="clase" placeholder="Nueva clase">
                            <button type="button" class="btn btn-sm btn-outline-secondary mt-2" id="edit_toggleClaseSelect">Usar existente</button>
                        </div>
                    </div> 

                    <div class="mb-3">
                        <label for="edit_duracion" class="form-label">Duración (minutos)</label>
                        <input type="number" class="form-control" id="edit_duracion" name="duracion" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_precio" class="form-label">Precio</label>
                        <input type="text" class="form-control" id="edit_precio" name="precio" required>
                    </div>
                    <div class="mb-3">
                        <label for="edit_color" class="form-label">Color</label>
                        <input type="color" class="form-control form-control-color" id="edit_color" name="color" value="#000000" title="Elige un color">
                    </div>                    
                    <div class="mb-3">
                        <label for="edit_descripcion" class="form-label">Descripción</label>
                        <textarea class="form-control" id="edit_descripcion" name="descripcion" rows="3" required></textarea>
                    </div>    
                    <div class="mb-3">
                        <label for="edit_activo" class="form-label">Estado</label>
                        <select class="form-select" id="edit_activo" name="activo" required>
                            <option value="1">Activo</option>
                            <option value="0">Inactivo</option>
                        </select>
                    </div>                                    
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
    @vite(['resources/js/gestores/experiencias.js'])
    @include('components.session-alert')
@endif
@endsection

