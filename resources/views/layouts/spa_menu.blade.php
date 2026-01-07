@if(View::hasSection('roleValidation'))
    @yield('roleValidation')
@endif

<!DOCTYPE html>
<html lang="es">
@php
    use App\Models\Unidad;
    use App\Models\Spa;

    if (Auth::user()->rol === 'master') {
        $area = request()->segment(1);
        if (in_array($area, ['palacio', 'princess', 'pierre'])) {
            session(['current_spa' => $area]);
        }
    }

    $unidadSeleccionada = session('current_unidad_id') ? Unidad::find(session('current_unidad_id')) : null;
    $menuColorStyle = '';
    
    if ($unidadSeleccionada) {
        // Si estamos en una unidad personalizada (NewUnid), forzamos el contexto al Spa "NewUnid"
        // que contiene los datos clonados de Palacio. Esto hace que todo el menú (reservaciones, boutique, etc.)
        // opere de forma independiente con los datos de NewUnid.
        $newUnidSpa = Spa::where('nombre', 'NewUnid')->first();
        if ($newUnidSpa) {
            session(['current_spa' => strtolower($newUnidSpa->nombre)]); // 'newunid'
            session(['current_spa_id' => $newUnidSpa->id]);
        }

        $logoUrl = $unidadSeleccionada->logo_superior ? asset($unidadSeleccionada->logo_superior) : asset("images/palacio/logo.png");
        $decorativoUrl = $unidadSeleccionada->logo_inferior ? asset($unidadSeleccionada->logo_inferior) : asset("images/palacio/decorativo.png");
        if ($unidadSeleccionada->color_unidad) {
            $menuColorStyle = 'background-color: ' . $unidadSeleccionada->color_unidad . ' !important;';
        }
    } else {
        $spasFolder = session('current_spa') ?? strtolower(optional(Auth::user()->spa)->nombre);
        if ($spasFolder === 'new') {
            $spasFolder = 'palacio';
        }
        $logoUrl = asset("images/$spasFolder/logo.png");
        $decorativoUrl = asset("images/$spasFolder/decorativo.png");
    }
@endphp
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    
    <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
    <meta http-equiv="Pragma" content="no-cache">
    <meta http-equiv="Expires" content="0">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    
    <meta name="csrf-token" content="{{ csrf_token() }}">
    
    <title>ELAN SPA & WELLNESS EXPERIENCE</title>

    @php
        $spaCss = session('current_spa') ?? strtolower(optional(Auth::user()->spa)->nombre);
        if ($spaCss === 'newunid') $spaCss = 'palacio';
        if (!$spaCss) $spaCss = 'palacio';
    @endphp
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite('resources/css/menus/' . $spaCss . '/menu_styles.css')
    @endif

    @yield('css') 
</head>
<body class="sidebar-hover">
        <nav class="sidebar" style="{{ $menuColorStyle }}">
            <div class="logo">
            
                <img src="{{ $logoUrl }}" alt="Logo">
            </div>
            <ul>
            
            @if (in_array(Auth::user()->rol, ['master', 'administrador', 'recepcionista']) ||
                (Auth::user()->rol === 'anfitrion' && Auth::user()->departamento === 'spa'))
                    <li class="menu-item">
                        <a href="#"><i class="fas fa-seedling"></i><span> Reservaciones</span></a>
                        <ul class="submenu">
                            <li><a href="{{ route('reservations.index') }}"><i class="fas fa-calendar-alt"></i><span> Sábana de Reservaciones</span></a></li>
                            <li><a href="{{ route('reservations.historial') }}"><i class="fas fa-file-invoice-dollar"></i><span> Historial de Pagos</span></a></li>
                        </ul>
                    </li>

            @endif
                
            {{-- Menú: para salón de belleza, según rol y departamento --}}
            {{-- @if (in_array(Auth::user()->rol, ['master', 'administrador', 'recepcionista']) ||
                (Auth::user()->rol === 'anfitrion' && Auth::user()->departamento === 'salon de belleza'))
                <li><a href="#"><i class="fas fa-spa"></i><span> Salón de Belleza</span></a></li>
            @endif --}}
                
                
            {{-- Menú: para la boutique, según rol y departamento --}}
            @if (in_array(Auth::user()->rol, ['master', 'administrador']))  
                <li class="menu-item">
                    <a href="#"><i class="fas fa-store"></i><span> Boutique</span></a>
                    <ul class="submenu">
                        <li><a href="{{ route('boutique.venta') }}"><i class="fas fa-dollar-sign"></i><span> Venta</span></a></li>
                        <li><a href="{{ route('boutique.inventario') }}"><i class="fas fa-box"></i><span> Inventario</span></a></li>
                        <li><a href="{{ route('boutique.reporteo') }}"><i class="fas fa-chart-line"></i><span> Reporteo</span></a></li>
                    </ul>
                </li>
            @endif

            {{-- Menú: para el gimnasio, según rol y departamento --}}
            @if (in_array(Auth::user()->rol, ['master', 'administrador']) || (Auth::user()->rol === 'anfitrion' && Auth::user()->departamento === 'gym'))
                <li class="menu-item">
                    <a href="#"><i class="fas fa-dumbbell"></i><span> Gimnasio</span></a>
                    <ul class="submenu">
                        <li><a href="{{ route('gimnasio.reporteo') }}"><i class="fas fa-chart-line"></i><span> Reporteo</span></a></li>
                        <li><a href="{{ route('gimnasio.historial') }}"><i class="fas fa-history"></i><span> Historial</span></a></li>
                        <li><a href="{{ route('gimnasio.qr_code') }}" target="_blank"><i class="fas fa-qrcode"></i><span> Código QR</span></a></li>
    
                    </ul>
                </li>
            @endif
                
                
                    <li class="menu-item">
                        <a href="#"><i class="fas fa-cogs"></i><span> Administración</span></a>
                        <ul class="submenu">
                            @if (in_array(Auth::user()->rol, ['master']))  
                                <li><a href="{{ route('anfitriones.index') }}"><i class="fas fa-user-tie"></i><span> Anfitriones</span></a></li>
                            @endif
                            @if (in_array(Auth::user()->rol, ['master', 'administrador']))  
                                <li><a href="{{ route('experiences.index') }}"><i class="fas fa-list"></i><span> Experiencias</span></a></li>
                                <li><a href="{{ route('cabinas.index') }}"><i class="fas fa-door-closed"></i><span> Cabinas</span></a></li>
                                <li><a href="{{ route('cliente.index') }}"><i class="fas fa-users"></i><span> Clientes</span></a></li>
                                <li><a href="{{ route('familias.index') }}"><i class="fas fa-box"></i><span> Familias</span></a></li>
                            @endif
                        </ul>
                    </li>

                @php
                
                    $anfitrion = Auth::user();
                @endphp

                @if (is_array(Auth::user()->accesos) && count(Auth::user()->accesos) > 0)
                    <li class="menu-item">
                        <a class="nav-link" href="{{ route('modulos') }}">
                            <i class="fas fa-th-large"></i><span> Unidad de negocios</span>
                        </a>
                    </li>
                @endif

                <li>
                    <form method="POST" action="{{ route('logout') }}" class="logout-form">
                        @csrf 
                        <button type="submit"><i class="fas fa-sign-out-alt"></i><span> Cerrar Sesión</span></button>
                    </form>
                </li>
            </ul>
            
            <div class="sidebar-decoration" style="background-image: url('{{ $decorativoUrl }}');"></div>
        </nav>

    <div class="container">
        <main>
            @yield('content')
        </main>
    </div>

    @yield('scripts')
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
