@extends('layouts.spa_menu')

@section('logo_img')
@php
    // Carpeta del spa activa para cargar logo dinámico
    $spasFolder = session('current_spa') ?? strtolower(optional(Auth::user()->spa)->nombre);
@endphp
<img src="{{ asset("images/$spasFolder/logo.png") }}" alt="Logo de {{ ucfirst($spasFolder) }}">
@endsection

@section('css')
    @php
        // Carpeta del spa para cargar estilos específicos
        $spaCss = session('current_spa') ?? strtolower(optional(Auth::user()->spa)->nombre);
    @endphp
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        {{-- Vite carga estilos CSS específicos para el spa --}}
        @vite([
            'resources/css/menus/themes/' . $spaCss . '.css',
            'resources/css/sabana_reservaciones/reservaciones_styles.css',
            'resources/css/ModalAviso/modal_aviso_individual.css'
        ])
    @endif
    {{-- Font Awesome y Google Fonts --}}
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">
@endsection

@section('decorativo')
    @php
        // Imagen decorativa lateral dinámica según spa
        $linDecorativa = asset("images/$spasFolder/decorativo.png");
    @endphp
    <div class="sidebar-decoration" style="background-image: url('{{ $linDecorativa }}');"></div>
@endsection

@section('content')
<header class="main-header">
    <h2>SABANA DE RESERVACIONES</h2>
</header>

{{-- Mostrar errores de validación --}}
@if ($errors->any())
    <div class="alert-danger" id="errorAlert">
        <ul>
            @foreach ($errors->all() as $error)
                <li>{{ $error }}</li>
            @endforeach
        </ul>
        <button class="close-btn" onclick="document.getElementById('errorAlert').style.display='none'">✖</button>
    </div>
@endif

{{-- Icono fijo para acceso a reporte general --}}
<div style="position: fixed; top: 20px; right: 30px; z-index: 1000;">
    <a href="{{ route('reports.index') }}" title="Ir al reporte general">
        <i class="fas fa-file-alt fa-2x" style="color: #888;"></i>
    </a>
</div>

{{-- Filtro por fecha --}}
<form method="GET" action="{{ route('reservations.index') }}" class="filtro-fecha-form">
    <input type="date" id="filtro_fecha" name="fecha" value="{{ request('fecha', now()->toDateString()) }}">
    <button type="submit"><i class="fas fa-search"></i></button>
</form>

{{-- Menú contextual para celdas libres --}}
<div id="contextMenu" class="context-menu" style="display: none; position: absolute;">
    <ul>
        @if (in_array(Auth::user()->rol, ['master', 'administrador', 'recepcionista']))
            <li id="reservarOpcion" data-hora="" data-anfitrion="">Reservar aquí</li>
            <li id="bloquearOpcion">Bloquear celda</li>
        @endif
    </ul>
</div>

{{-- Menú contextual para celdas reservadas --}}
<div id="contextMenuReserved" class="context-menu" style="display: none; position: absolute;">
    <ul>
        @if (in_array(Auth::user()->rol, ['master', 'administrador', 'recepcionista']))
            {{-- Opciones para reservaciones SIN check-out --}}
            <li id="editarOpcion" class="opcion-no-checkout">Editar Reservación</li>
            <li id="eliminarOpcion" class="opcion-no-checkout">Cancelar Reservación</li>
            
            {{-- Opción para reservaciones SIN check-in --}}
            <li id="checkinOpcion" class="opcion-no-checkout">Check in</li>

            {{-- Opción para reservaciones CON check-in pero SIN check-out (lleva a la pantalla de pago) --}}
            <li id="checkoutOpcion" class="opcion-no-checkout">Check-out</li>
        @endif
    </ul>
</div>

{{-- Modal detalles reservación --}}
<div id="reservationDetailsModal" class="reservation-details-modal">
    <div class="modal-content">
        <span class="close-btn" id="closeReservationDetailsBtn">&times;</span>
        <h3>Detalles de Reservación</h3>
        <div id="reservationDetails"></div>
    </div>
</div>

{{-- Contenedor de tabla de reservaciones --}}
<div id="tabla-reservaciones">
    @include('reservations.table')
</div>

{{-- Template para reservaciones adicionales dinámicas --}}
<template id="reservaExtraTemplate">
    <div class="reserva-extra bg-white shadow-sm border rounded-3 p-4 mb-4 position-relative">
        <h5 class="fw-bold text-secondary mb-3">| RESERVACION ADICIONAL</h5>

        {{-- Botón eliminar --}}
        <button type="button" class="btn btn-sm btn-danger remove-reserva-btn" title="Quitar">X</button>

        {{-- Inputs para datos de cliente y reserva --}}
        <input type="hidden" name="grupo[__INDEX__][cliente_existente_id]" class="cliente-id-existente">

        <div class="mb-3">
            <label class="form-label">Correo</label>
            <div class="input-group">
                <input type="email" name="grupo[__INDEX__][correo_cliente]" class="form-control correo-cliente" required>
                <button type="button" class="btn buscar-cliente-extra" title="Buscar por correo">
                    <i class="fas fa-search"></i>
                </button>
            </div>
        </div>

        <div class="nuevo-cliente">
            <div class="mb-3"><label class="form-label">Nombre</label><input type="text" name="grupo[__INDEX__][nombre_cliente]" class="form-control" required></div>

            <div class="row">
                <div class="col-md-6 mb-3"><label class="form-label">Apellido Paterno</label><input type="text" name="grupo[__INDEX__][apellido_paterno_cliente]" class="form-control" required></div>
                <div class="col-md-6 mb-3"><label class="form-label">Apellido Materno</label><input type="text" name="grupo[__INDEX__][apellido_materno_cliente]" class="form-control"></div>
            </div>

            <div class="mb-3"><label class="form-label">Teléfono</label><input type="text" name="grupo[__INDEX__][telefono_cliente]" class="form-control" required></div>

            <div class="mb-3">
                <label class="form-label">Tipo de Visita</label>
                <select name="grupo[__INDEX__][tipo_visita_cliente]" class="form-select" required>
                    <option value="">Seleccione</option>
                    <option value="palacio mundo imperial">Palacio Mundo Imperial</option>
                    <option value="princess mundo imperial">Princess Mundo Imperial</option>
                    <option value="pierre mundo imperial">Pierre Mundo Imperial</option>
                    <option value="condominio">Condominio</option>
                    <option value="locales">Locales</option>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Experiencia</label>
            <select class="form-select experiencia-select" name="grupo[__INDEX__][experiencia_id]" required>
                <option value="">Selecciona experiencia</option>
                @foreach ($experiences as $experience)
                    <option value="{{ $experience->id }}">{{ $experience->nombre }} - {{ $experience->duracion }} min</option>
                @endforeach
            </select>
        </div>

        <div class="col-md-6 mb-3">
            <label class="form-label">Fecha</label>
            <input type="date" class="form-control" name="grupo[__INDEX__][fecha]" required>
        </div>

        <div class="mb-3">
            <label class="form-label">Cabina</label>
            <select class="form-select" name="grupo[__INDEX__][cabina_id]" required>
                <option value="">Selecciona cabina</option>
                @foreach ($cabinas as $cabina)
                    <option value="{{ $cabina->id }}">{{ $cabina->nombre }}</option>
                @endforeach
            </select>
        </div>

        <div class="mb-3">
            <label class="form-label">Anfitrión/Terapeuta</label>
            <select class="form-select" name="grupo[__INDEX__][anfitrion_id]" required>
                <option value="">Selecciona anfitrión/terapeuta</option>
                @foreach ($anfitrionesDisponibles as $anfitrion)
                    @php
                        $esSalon = $anfitrion->operativo->departamento === 'salon de belleza';
                    @endphp
                    <th class="{{ $esSalon ? 'encabezado-salon' : '' }}">
                        {{ $anfitrion->nombre_usuario }} {{ $anfitrion->apellido_paterno }}
                    </th>
                @endforeach
            </select>
        </div>

        <div class="row">
            <div class="col-md-6 mb-3">
                <label class="form-label">Hora</label>
                <select class="form-select hora-select" name="grupo[__INDEX__][hora]" required>
                    <option value="">Selecciona hora</option>
                </select>
            </div>
        </div>

        <div class="mb-3">
            <label class="form-label">Observaciones</label>
            <textarea class="form-control" name="grupo[__INDEX__][observaciones]" rows="2"></textarea>
        </div>
    </div>
</template>

{{-- Modal para crear o editar reservaciones --}}
<div class="modal fade" id="reservationModal" tabindex="-1" aria-labelledby="reservationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="modalTitle">Nueva Reservación</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="reservationForm" method="POST" action="{{ route('reservations.store') }}">
                    @csrf 
                    {{-- Campos ocultos para datos esenciales --}}
                    <input type="hidden" id="cliente_existente_id" name="cliente_existente_id" value="">
                    <input type="hidden" id="reserva_id" name="reserva_id">
                    <div class="mb-3 d-none" id="fecha-wrapper">
                        <label for="fecha_reserva" class="form-label">Fecha</label>
                        <input type="date" id="fecha_reserva" name="fecha" class="form-control">
                    </div>
                    <div class="mb-3 d-none" id="hora-wrapper">
                        <label for="hora" class="form-label">Hora</label>
                        <select id="hora" name="hora" class="form-select">
                            <option value="">Selecciona una hora</option>
                        </select>
                    </div>
                    <input type="hidden" id="duracion" name="duracion">
                    <input type="hidden" id="selected_anfitrion" name="anfitrion_id">

                    {{-- Campo correo con botón para buscar cliente existente --}}
                    <div class="mb-3">
                        <label for="correo_cliente" class="form-label">Correo</label>
                        <div class="input-group">
                            <input id="correo_cliente" name="correo_cliente" type="email" class="form-control estilo-correo" placeholder="Correo del cliente">
                            <button id="buscarClienteBtn" type="button" class="btn buscar-cliente-extra">
                                <i class="fas fa-search"></i>
                            </button>
                        </div>
                    </div>

                    {{-- Campos visibles para datos cliente (ocultos inicialmente) --}}
                    <div id="datosCliente" style="display: none;" class="nuevo-cliente">
                        <div class="mb-3"><label for="nombre_cliente" class="form-label">Nombre</label><input type="text" id="nombre_cliente" name="nombre_cliente" class="form-control"></div>
                        <div class="mb-3"><label for="apellido_paterno_cliente" class="form-label">Apellido Paterno</label><input type="text" id="apellido_paterno_cliente" name="apellido_paterno_cliente" class="form-control"></div>
                        <div class="mb-3"><label for="apellido_materno_cliente" class="form-label">Apellido Materno</label><input type="text" id="apellido_materno_cliente" name="apellido_materno_cliente" class="form-control"></div>
                        <div class="mb-3"><label for="telefono_cliente" class="form-label">Teléfono</label><input type="text" id="telefono_cliente" name="telefono_cliente" class="form-control"></div>
                        <div class="mb-3">
                            <label for="tipo_visita_cliente" class="form-label">Tipo de Visita</label>
                            <select id="tipo_visita_cliente" name="tipo_visita_cliente" class="form-select">
                                <option value="">Seleccione</option>
                                <option value="palacio mundo imperial">Palacio Mundo Imperial</option>
                                <option value="princess mundo imperial">Princess Mundo Imperial</option>
                                <option value="pierre mundo imperial">Pierre Mundo Imperial</option>
                                <option value="condominio">Condominio</option>
                                <option value="locales">Locales</option>
                            </select>
                        </div>
                    </div>                    

                    {{-- Select experiencia --}}
                    <div class="mb-3">
                        <label for="experiencia_id" class="form-label">Experiencia</label>
                        <select class="form-select" id="experiencia_id" name="experiencia_id">
                            <option value="" disabled selected>Selecciona una experiencia</option>
                            @foreach ($experiences as $experience)
                                <option value="{{ $experience->id }}" data-duracion="{{ $experience->duracion }}">
                                    {{ $experience->nombre }} - {{ $experience->duracion }} min - ${{ $experience->precio }}
                                </option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Select anfitrión (solo para edición) --}}
                    <div class="mb-3" id="anfitrionWrapper" style="display: none;">
                        <label for="anfitrion_id" class="form-label">Anfitrión/Terapeuta</label>
                        <select class="form-select" id="anfitrion_id_select" name="anfitrion_id">
                            {{-- Opciones se llenarán dinámicamente --}}
                        </select>
                    </div>

                    {{-- Select cabina --}}
                    <div class="mb-3">
                        <label for="cabina_id" class="form-label">Cabina Disponible</label>
                        <select class="form-select" id="cabina_id" name="cabina_id">
                            <option value="" disabled selected>Selecciona una cabina</option>
                            @foreach ($cabinas as $cabina)
                                <option value="{{ $cabina->id }}">{{ $cabina->nombre }}</option>
                            @endforeach
                        </select>
                    </div>

                    {{-- Observaciones --}}
                    <div class="mb-3">
                        <label for="observaciones" class="form-label">Observaciones</label>
                        <textarea class="form-control" id="observaciones" name="observaciones" rows="2"></textarea>
                    </div>

                    <hr>

                    {{-- Sección para agregar reservaciones adicionales --}}
                    <div class="mb-3 d-flex align-items-center gap-2">
                        <label class="form-label mb-0">¿Reservaciones adicionales?</label>
                        <input type="number" id="cantidadReservas" class="form-control" style="width: 80px;" min="0" max="10" placeholder="0">
                        <button type="button" id="generarReservasBtn" class="btn btn-primary p-2"><i class="fas fa-plus"></i></button>
                    </div>

                    {{-- Contenedor para los formularios de reservas adicionales --}}
                    <div id="grupoReservasContainer"></div>

                    {{-- Botón guardar --}}
                    <button type="submit" class="btn btn-primary mt-3" id="saveButton">Guardar Reservación</button>
                </form>
            </div>
        </div>
    </div>
</div>

{{-- Modal para bloquear horarios --}}
<div class="modal fade" id="bloqueoModal" tabindex="-1" aria-labelledby="bloqueoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <form id="bloqueoForm">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Bloquear horarios</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                </div>
                <div class="modal-body">
                    {{-- Campos ocultos para hora y anfitrión --}}
                    <input type="hidden" id="bloqueo_hora" name="hora">
                    <input type="hidden" id="bloqueo_anfitrion_id" name="anfitrion_id">

                    {{-- Motivo del bloqueo --}}
                    <div class="mb-3">
                        <label for="motivo_bloqueo" class="form-label">Motivo</label>
                        <input type="text" class="form-control" id="motivo_bloqueo" name="motivo" placeholder="Motivo del bloqueo">
                    </div>

                    {{-- Duración del bloqueo --}}
                    <div class="mb-3">
                        <label for="duracion_bloqueo" class="form-label">Duración (min)</label>
                        <input type="number" class="form-control" id="duracion_bloqueo" name="duracion" min="30" max="300" value="30">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </div>
        </form>
    </div>
</div>

@endsection

{{-- SweetAlert2 --}}
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

@section('scripts')

<script>
    // Configuración global accesible en JS para datos de reservaciones, cabinas, bloqueos, etc.
    window.ReservasConfig = {
        reservaciones: @json($reservaciones),
        cabinas: @json($cabinas),
        cabinasOcupadas: @json($cabinasOcupadas),
        clases_actividad: @json($clases_actividad),
        bloqueos: @json($bloqueos),
        experiencias: @json($experiences),
        anfitriones: @json($anfitrionesDisponibles->values()->all()),
        horarios: @json($horariosAnfitriones),
    };
</script>

{{-- Script para filtrar anfitriones por experiencia --}}
@vite(['resources/js/main.js'])

<script>
document.addEventListener('DOMContentLoaded', function () {
    const selectExperiencia = document.getElementById('experiencia_id');
    const selectAnfitrion = document.getElementById('anfitrion_id_select');
    const selectCabina = document.getElementById('cabina_id');

    if (!selectExperiencia || !selectAnfitrion || !selectCabina) return;

    const todosLosAnfitriones = window.ReservasConfig.anfitriones;

    function filtrarAnfitriones() {
        const experienciaId = parseInt(selectExperiencia.value);
        if (!experienciaId) return;

        // Buscar experiencia seleccionada
        const experiencia = window.ReservasConfig.experiencias.find(e => e.id === experienciaId);
        if (!experiencia || !experiencia.clase) return;

        const normalize = s => String(s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();
        const claseNorm = normalize(experiencia.clase || '');

        // Limpiar select de anfitriones
        selectAnfitrion.innerHTML = '<option value="">Selecciona anfitrión</option>';

        // Filtrar anfitriones por clase (revisando clases_actividad del operativo)
        const filtrados = todosLosAnfitriones.filter(anfitrion => {
            const clases = Array.isArray(anfitrion.operativo?.clases_actividad)
                ? anfitrion.operativo.clases_actividad
                : (Array.isArray(anfitrion.clases_actividad) ? anfitrion.clases_actividad : []);
            const clasesNorm = clases.map(c => normalize(c));
            return clasesNorm.includes(claseNorm);
        });

        // Agregar opciones al select
        filtrados.forEach(anfitrion => {
            const option = document.createElement('option');
            option.value = anfitrion.id;
            option.textContent = anfitrion.nombre_usuario + ' ' + anfitrion.apellido_paterno;
            selectAnfitrion.appendChild(option);
        });
    }

    async function filtrarCabinas() {
        const experienciaId = parseInt(selectExperiencia.value);
        if (!experienciaId) {
            selectCabina.innerHTML = '<option value="">Selecciona una cabina</option>';
            return;
        }

        try {
            const response = await fetch(`/api/experiences/${experienciaId}/cabinas`);
            if (!response.ok) throw new Error('Error al cargar las cabinas');
            const cabinas = await response.json();

            selectCabina.innerHTML = '<option value="">Selecciona una cabina</option>';
            cabinas.forEach(cabina => {
                const option = document.createElement('option');
                option.value = cabina.id;
                option.textContent = cabina.nombre;
                selectCabina.appendChild(option);
            });
        } catch (error) {
            console.error(error);
            selectCabina.innerHTML = '<option value="">Error al cargar cabinas</option>';
        }
    }

    async function filtrarExperiencias() {
        const anfitrionId = parseInt(selectAnfitrion.value);
        if (!anfitrionId) {
            selectExperiencia.innerHTML = '<option value="">Selecciona una experiencia</option>';
            return;
        }

        try {
            const response = await fetch(`/api/anfitriones/${anfitrionId}/experiences`);
            if (!response.ok) throw new Error('Error al cargar las experiencias');
            const experiences = await response.json();

            selectExperiencia.innerHTML = '<option value="">Selecciona una experiencia</option>';
            experiences.forEach(experience => {
                const option = document.createElement('option');
                option.value = experience.id;
                option.textContent = `${experience.nombre} - ${experience.duracion} min - $${experience.precio}`;
                option.dataset.duracion = experience.duracion;
                selectExperiencia.appendChild(option);
            });
        } catch (error) {
            console.error(error);
            selectExperiencia.innerHTML = '<option value="">Error al cargar experiencias</option>';
        }
    }

    // Detectar cambio en experiencia para filtrar anfitriones y cabinas
    selectExperiencia.addEventListener('change', function() {
        filtrarAnfitriones();
        filtrarCabinas();
    });

    // Detectar cambio en anfitrion para filtrar experiencias
    selectAnfitrion.addEventListener('change', filtrarExperiencias);
});
</script>

{{-- Modal para alertas --}}
@include('components.alert-modal')

@endsection
