<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>ELAN SPA & WELLNESS EXPERIENCE</title>

    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;600;700&display=swap" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/modulos/menu_modulos.css'])
    @endif

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <style>
        .context-menu {
            position: absolute;
            background-color: white;
            border: 1px solid #ccc;
            box-shadow: 2px 2px 5px rgba(0,0,0,0.2);
            z-index: 1000;
            padding: 5px 0;
            list-style: none;
            border-radius: 5px;
        }
        .context-menu li { padding: 8px 15px; cursor: pointer; }
        .context-menu li:hover { background-color: #f0f0f0; }
    </style>
</head>
<body>

@php
    $accesos = Auth::user()->accesos;
    // Abortamos si no tiene accesos asignados
    if (!is_array($accesos) || count($accesos) === 0) {
        abort(403, 'Acceso denegado.');
    }
@endphp

@php
    use App\Models\Spa;

    $user = Auth::user();

    if (!$user) {
        // Redirigir a login si no autenticado
        header("Location: " . route('login'));
        exit;
    }

    // ID del spa principal del usuario
    $principalSpaId = optional($user->spa)->id;

    // Obtener accesos en formato array
    $accesos = is_array($user->accesos) ? $user->accesos : json_decode($user->accesos, true) ?? [];

    // IDs únicos de spas permitidos (principal + accesos)
    $idsPermitidos = array_unique(array_merge([$principalSpaId], $accesos));

    // Obtener colección de spas disponibles para el usuario
    // Eager load la relación para saber si un Spa es una Unidad personalizada.
    $spasDisponibles = Spa::whereIn('id', $idsPermitidos)->with('unidad_detalle')->get();
@endphp

<header class="page-header">
    <button class="menu-btn" onclick="toggleMenu()">
        <div></div>
    </button>

    <img src="{{ asset('images/LOGO_ES.png') }}" alt="Logo" class="header-logo" />

    <button class="settings-btn" onclick="toggleSettingsMenu()">
        <i class="fas fa-cog fa-3x"></i>
    </button>

    <div class="settings-menu" id="settings-menu" hidden>
        <form action="{{ route('unidades.create') }}" method="GET" style="margin: 0;">
            <button type="submit" class="btn btn-danger">Crear unidad</button>
        </form>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-danger">Cerrar sesión</button>
        </form>
    </div>
    
    
</header>

@if(session('success'))
    <div class="alert alert-success m-3" role="alert">{{ session('success') }}</div>
@endif

<main class="content">
    <div class="button-container">
        {{-- Bucle unificado para mostrar Spas principales y Unidades personalizadas --}}
        @foreach ($spasDisponibles as $spa)
            @if ($unidad = $spa->unidad_detalle)
                {{-- Es una Unidad personalizada --}}
                <a href="javascript:void(0);"
                    onclick="selectUnidad({{ $unidad->id }}, event)"
                    class="area-button unidad-item"
                    data-unidad-id="{{ $unidad->id }}"
                    data-unidad-nombre="{{ $unidad->nombre_unidad }}"
                    title="{{ $unidad->nombre_unidad }}"
                >
                    @if($unidad->logo_superior)
                        <img src="{{ asset($unidad->logo_superior) }}" alt="{{ $unidad->nombre_unidad }}" />
                    @elseif($unidad->logo_unidad)
                        <img src="{{ asset($unidad->logo_unidad) }}" alt="{{ $unidad->nombre_unidad }}" />
                    @else
                        {{-- No se muestra nada si no hay logos --}}
                    @endif
                </a>
            @else
                {{-- Es un Spa principal (Palacios, Princess, Pierre) --}}
                <a href="#"
                    onclick="selectSpa('{{ strtolower($spa->nombre) }}')"
                    class="area-button"
                    title="{{ $spa->nombre }}"
                >
                    <img src="{{ asset('images/' . strtolower($spa->nombre) . '/Logo.png') }}" alt="{{ strtoupper($spa->nombre) }}" />
                </a>
            @endif
        @endforeach
    </div>
</main>

<div class="contenedor-imagen">
    <img src="{{ asset('images/LOGO_MI.png') }}" alt="Logo principal" />
</div>

<!-- Menú contextual para unidades -->
<ul id="unidad-context-menu" class="context-menu" style="display:none;">
    <li id="context-menu-edit">Editar</li>
    <li id="context-menu-delete">Eliminar</li>
</ul>

@include('components.alert-modal')

<script>
    function toggleMenu() {
        const menu = document.getElementById('menu');
        const content = document.querySelector('.content');
        if (menu) menu.classList.toggle('open');
        if (content) content.classList.toggle('open');
    }

    function selectSpa(spa) {
        fetch(`/set-spa/${spa}`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({})
        }).then(() => {
            const rol = '{{ strtolower(Auth::user()->rol) }}';
            const departamento = '{{ strtolower(Auth::user()->departamento ?? '') }}';

            if (rol === 'anfitrion') {
                switch (departamento) {
                    case 'spa':
                        window.location.href = "{{ route('reservations.index') }}";
                        break;
                    case 'salon de belleza':
                        window.location.href = "{{ route('salon.index') }}";
                        break;
                    default:
                        window.location.href = "{{ route('reservations.index') }}";
                }
            } else {
                window.location.href = "{{ route('reservations.index') }}";
            }
        });
    }

    function selectUnidad(unidadId, event) {
        // Prevenir la redirección si se hizo clic derecho
        if (event && event.button === 2) {
            return;
        }

        fetch(`/set-unidad/${unidadId}`, { // Esta ruta llama a UnidadController@select
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({})
        }).then(response => {
            if (response.ok) {
                // Redirigir a la página principal de reservaciones o a donde necesites
                window.location.href = "{{ route('reservations.index') }}";
            }
        });
    }

    function toggleSettingsMenu() {
        const menu = document.getElementById('settings-menu');
        if (!menu) return;
        if (menu.hasAttribute('hidden')) {
            menu.removeAttribute('hidden');
            menu.classList.add('open');
        } else {
            menu.setAttribute('hidden', '');
            menu.classList.remove('open');
        }
    }

    // Cerrar menú configuración al hacer clic fuera
    document.addEventListener('click', function (event) {
        const menu = document.getElementById('settings-menu');
        const button = document.querySelector('.settings-btn');
        if (!menu || !button) return;

        if (menu.classList.contains('open') &&
            !menu.contains(event.target) &&
            !button.contains(event.target)) {
            menu.setAttribute('hidden', '');
            menu.classList.remove('open');
        }
    });

    
    // --- Lógica para el menú contextual ---
    document.addEventListener('DOMContentLoaded', function() {
        const contextMenu = document.getElementById('unidad-context-menu');
        const menuEdit = document.getElementById('context-menu-edit');
        const menuDelete = document.getElementById('context-menu-delete');
        let currentUnidadId = null;

        document.querySelectorAll('.unidad-item').forEach(item => {
            item.addEventListener('contextmenu', function(event) {
                event.preventDefault();
                currentUnidadId = this.dataset.unidadId;

                contextMenu.style.top = `${event.pageY}px`;
                contextMenu.style.left = `${event.pageX}px`;
                contextMenu.style.display = 'block';
            });
        });

        // Ocultar menú al hacer clic en cualquier otro lugar
        document.addEventListener('click', function() {
            contextMenu.style.display = 'none';
        });

        // Acción de editar (puedes expandir esto)
        menuEdit.addEventListener('click', function() {
            // Redirigir a la página de edición de la unidad
            window.location.href = `/unidades/${currentUnidadId}/edit`; // O la ruta correcta que tengas definida, ej: /unidad/...
        });

        // Acción de eliminar
        menuDelete.addEventListener('click', function() {
            const unidadItem = document.querySelector(`.unidad-item[data-unidad-id='${currentUnidadId}']`);
            const nombreUnidad = unidadItem.dataset.unidadNombre;

            if (confirm(`¿Estás seguro de que quieres eliminar la unidad "${nombreUnidad}"?`)) {
                eliminarUnidad(currentUnidadId);
            }
        });

        async function eliminarUnidad(id) {
            try {
                const response = await fetch(`/unidades/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (data.success) {
                    // Eliminar el elemento del DOM
                    const unidadElement = document.querySelector(`.unidad-item[data-unidad-id='${id}']`);
                    if (unidadElement) {
                        unidadElement.remove();
                    }
                    // Mostrar alerta de éxito (opcional)
                    alert(data.message);
                } else {
                    alert('Error: ' + (data.message || 'No se pudo eliminar la unidad.'));
                }
            } catch (error) {
                console.error('Error al eliminar la unidad:', error);
                alert('Ocurrió un error de red. Inténtalo de nuevo.');
            }
        }
    });

</script>

</body>
</html>
