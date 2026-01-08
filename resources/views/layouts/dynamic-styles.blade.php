@php
    // Revisa si hay un tema de 'unidad de negocio' cargado en la sesión
    $unidadTheme = session('current_unidad_theme');

    // Si no, revisa si hay un SPA principal seleccionado (princess, pierre, etc.)
    $spaName = session('current_spa');

    // Construye la ruta al archivo de tema estático para los SPAs principales
    $staticThemePath = $spaName ? 'resources/css/menus/themes/' . $spaName . '.css' : null;
@endphp

@if ($unidadTheme && is_array($unidadTheme))
    {{-- CASO 1: Es una 'Unidad de Negocio'. Generamos el CSS dinámicamente desde la sesión. --}}
    <style>
        :root {
            --sidebar-bg: {{ $unidadTheme['sidebar_bg'] ?? '#033e59' }};
            --sidebar-hover-bg: {{ $unidadTheme['sidebar_hover_bg'] ?? '#3989b5' }};
            --icon-color: {{ $unidadTheme['icon_color'] ?? '#E0E0E0' }};
            --text-color: {{ $unidadTheme['text_color'] ?? '#E0E0E0' }};
            --submenu-bg: {{ $unidadTheme['submenu_bg'] ?? '#0a3d5f' }};
            --submenu-link-bg: {{ $unidadTheme['submenu_link_bg'] ?? '#105b89' }};
            --submenu-link-hover-bg: {{ $unidadTheme['submenu_link_hover_bg'] ?? '#3989b5' }};
            --logout-text-color: {{ $unidadTheme['logout_text_color'] ?? '#E0E0E0' }};
            --logout-icon-color: {{ $unidadTheme['logout_icon_color'] ?? '#E0E0E0' }};
        }
    </style>
@elseif ($staticThemePath && file_exists(base_path($staticThemePath)))
    {{-- CASO 2: Es un SPA principal. Cargamos su archivo de tema estático. --}}
    @vite($staticThemePath)
@else
    {{-- CASO 3: Fallback. Si nada coincide, cargamos el tema por defecto. --}}
    @vite('resources/css/menus/default.css')
@endif
