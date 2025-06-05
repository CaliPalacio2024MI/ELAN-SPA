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
    $spasDisponibles = Spa::whereIn('id', $idsPermitidos)->get()->keyBy('id');
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
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="btn btn-danger">Cerrar sesión</button>
        </form>
    </div>
</header>

<main class="content">
    <div class="button-container">
        @foreach ($spasDisponibles as $spa)
            <a
                href="#"
                onclick="selectSpa('{{ strtolower($spa->nombre) }}')"
                class="area-button"
                title="{{ $spa->nombre }}"
            >
                <img src="{{ asset('images/' . strtolower($spa->nombre) . '/Logo.png') }}" alt="{{ strtoupper($spa->nombre) }}" />
            </a>
        @endforeach
    </div>
</main>

<div class="contenedor-imagen">
    <img src="{{ asset('images/LOGO_MI.png') }}" alt="Logo principal" />
</div>

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
</script>

</body>
</html>
