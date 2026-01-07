<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>

@section('roleValidation')
@if (!in_array(Auth::user()->rol, ['master', 'administrador']))
    <script>
        alert('Acceso denegado.');
        window.location.href = "{{ route('dashboard') }}";
    </script>
    @php exit; @endphp
@endif
@endsection

@extends('layouts.spa_menu')

@section('css')
@php
    $spaCss = session('current_spa') ?? strtolower(optional(Auth::user()->spa)->nombre);
    if ($spaCss === 'newunid') $spaCss = 'palacio';
    if (!$spaCss) {
        $spaCss = 'palacio'; 
    }
@endphp
@if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
    @vite([
        'resources/css/menus/' . $spaCss . '/menu_styles.css',
        'resources/css/gestores/g_clientes_styles.css',
        'resources/css/ModalAviso/modal_aviso.css',
        ])
@endif

<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
<link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
@endsection

@section('content')

<header class="main-header">
    <h2>GESTIONAR CLIENTES</h2>
</header>

    <div class="botonera-top">
    <div class="d-flex gap-2">
        @php $qs = http_build_query(request()->query()); @endphp
        <a href="{{ route('clientes.export') }}{{ $qs ? '?'.$qs : '' }}" class="btn btns d-flex align-items-center justify-content-center">
            <i class="fas fa-download"></i>
        </a>

        <button type="button" class="btn btns d-flex align-items-center justify-content-center" data-bs-toggle="modal" data-bs-target="#importCSVModal">
            <i class="fas fa-upload"></i>
        </button>
    </div>

    <button type="button" class="btn btn-nuevo-cliente" data-bs-toggle="modal" data-bs-target="#clienteModal">
        Nuevo Cliente
    </button>
</div>

<!-- Modal para cargar el archivo CSV -->
<div class="modal fade" id="importCSVModal" tabindex="-1" aria-labelledby="importCSVModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('clientes.import.csv') }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="importCSVModalLabel">Importar Clientes desde CSV</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label for="csv_file" class="form-label">Selecciona el archivo CSV</label>
                    <input type="file" name="csv_file" id="csv_file" class="form-control" accept=".csv" required>
                </div>
                <div class="alert alert-info">
                    Asegúrate de que el archivo tenga las siguientes columnas: <br>
                    <strong>nombre, apellido_paterno, apellido_materno, correo, telefono, tipo_visita</strong>
                </div>
            </div>
            <div class="modal-footer">
                <button type="submit" class="btn btn-primary">Importar</button>
            </div>
        </form>
    </div>
</div>

<div class="table-container">
    <form method="GET" action="{{ route('cliente.index') }}" class="search-form d-flex mb-3">
        <input type="text" name="search" value="{{ request('search') }}" class="form-control search-input" placeholder="Buscar cliente...">
        <button type="submit" class="btn btn-buscar">
            <i class="fas fa-search"></i>
        </button>
    </form>

    <table class="table-responsive custom-table">
        <thead>
            <tr>
                <th>Nombre</th>
                <th>Apellido Paterno</th>
                <th>Apellido Materno</th>
                <th>Correo</th>
                <th>Teléfono</th>
                <th>Tipo de Visita</th>
                <th>Acciones</th>
            </tr>
        </thead>
        <tbody>
            @foreach ($clientes as $cliente)
                <tr>
                    <td>{{ $cliente->nombre }}</td>
                    <td>{{ $cliente->apellido_paterno }}</td>
                    <td>{{ $cliente->apellido_materno ?? '-' }}</td>
                    <td>{{ $cliente->correo ?? '-' }}</td>
                    <td>{{ $cliente->telefono }}</td>
                    <td>{{ ucfirst($cliente->tipo_visita) }}</td>
                    <td>
                        <button type="button" class="btn btn-warning btn-edit" data-bs-toggle="modal" 
                            data-bs-target="#editclienteModal"
                            data-id="{{ $cliente->id }}"
                            data-nombre="{{ $cliente->nombre }}"
                            data-apellido_paterno="{{ $cliente->apellido_paterno }}"
                            data-apellido_materno="{{ $cliente->apellido_materno }}"
                            data-correo="{{ $cliente->correo }}"
                            data-telefono="{{ $cliente->telefono }}"
                            data-tipo_visita="{{ $cliente->tipo_visita }}">
                            <i class="fa-solid fa-pen-to-square icono-accion-pequeno"></i>
                        </button>

                        <form action="{{ route('cliente.destroy', $cliente->id) }}" method="POST" class="d-inline">
                            @csrf
                            @method('DELETE')
                            <button type="button" class="btn btn-danger btn-delete">
                                <i class="fa-solid fa-delete-left icono-accion-pequeno" style="color: #ac0505;"></i>
                            </button>
                        </form>


                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>


<div class="modal fade" id="clienteModal" tabindex="-1" aria-labelledby="clienteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form action="{{ route('cliente.store') }}" method="POST" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title" id="clienteModalLabel">Nuevo Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">

                <div class="mb-3">
                    <label for="nombre" class="form-label">Nombre</label>
                    <input type="text" name="nombre" id="nombre" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="apellido_paterno" class="form-label">Apellido Paterno</label>
                    <input type="text" name="apellido_paterno" id="apellido_paterno" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="apellido_materno" class="form-label">Apellido Materno</label>
                    <input type="text" name="apellido_materno" id="apellido_materno" class="form-control">
                </div>

                <div class="mb-3">
                    <label for="correo" class="form-label">Correo</label>
                    <input type="email" name="correo" id="correo" class="form-control">
                </div>

                <div class="mb-3">
                    <label for="telefono" class="form-label">Teléfono</label>
                    <input type="text" name="telefono" id="telefono" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="tipo_visita" class="form-label">Tipo de Visita</label>
                    <select name="tipo_visita" id="tipo_visita" class="form-select" required>
                        <option value="">Seleccione</option>
                        <option value="palacio mundo imperial">Palacio Mundo Imperial</option>
                        <option value="princess mundo imperial">Princess Mundo Imperial</option>
                        <option value="pierre mundo imperial">Pierre Mundo Imperial</option>
                        <option value="condominio">Condominio</option>
                        <option value="locales">Locales</option>
                    </select>
                </div>

            </div>
            <div class="modal-footer">
                <button type="submit" class="btns">Guardar</button>
            </div>
        </form>
    </div>
</div>

<div class="modal fade" id="editclienteModal" tabindex="-1" aria-labelledby="editclienteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="editclienteForm" method="POST" action="" class="modal-content">
            @csrf
            @method('PUT')
            <div class="modal-header">
                <h5 class="modal-title" id="editclienteModalLabel">Editar Cliente</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
            </div>
            <div class="modal-body">

                <input type="hidden" id="edit_id" name="id">

                <div class="mb-3">
                    <label for="edit_nombre" class="form-label">Nombre</label>
                    <input type="text" name="nombre" id="edit_nombre" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="edit_apellido_paterno" class="form-label">Apellido Paterno</label>
                    <input type="text" name="apellido_paterno" id="edit_apellido_paterno" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="edit_apellido_materno" class="form-label">Apellido Materno</label>
                    <input type="text" name="apellido_materno" id="edit_apellido_materno" class="form-control">
                </div>

                <div class="mb-3">
                    <label for="edit_correo" class="form-label">Correo</label>
                    <input type="email" name="correo" id="edit_correo" class="form-control">
                </div>

                <div class="mb-3">
                    <label for="edit_telefono" class="form-label">Teléfono</label>
                    <input type="text" name="telefono" id="edit_telefono" class="form-control" required>
                </div>

                <div class="mb-3">
                    <label for="edit_tipo_visita" class="form-label">Tipo de Visita</label>
                    <select name="tipo_visita" id="edit_tipo_visita" class="form-select" required>
                        <option value="">Seleccione</option>
                        <option value="palacio mundo imperial">Palacio Mundo Imperial</option>
                        <option value="princess mundo imperial">Princess Mundo Imperial</option>
                        <option value="pierre mundo imperial">Pierre Mundo Imperial</option>
                        <option value="condominio">Condominio</option>
                        <option value="locales">Locales</option>
                    </select>
                </div>

            </div>
            <div class="modal-footer">
                <button type="submit" class="btns">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

    @include('components.alert-modal')
    @endsection

    @section('scripts')
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/js/gestores/clients.js'])
    @else
        <script src="{{ asset('js/gestores/clients.js') }}"></script>
    @endif
     @include('components.session-alert')
    @endsection
