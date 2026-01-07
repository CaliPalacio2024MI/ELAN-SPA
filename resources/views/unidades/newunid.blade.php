@extends('layouts.spa_menu')

@section('css')
    @php
        // Usamos un CSS base o el de palacio como fallback
        $spaCss = 'palacio';
    @endphp
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite('resources/css/menus/' . $spaCss . '/menu_styles.css')
        @vite('resources/css/general_styles.css')
    @endif
@endsection

@section('content')
    <script>
        // Redirigir al módulo principal (Reservaciones) manteniendo el contexto de NewUnid
        window.location.href = "{{ route('reservations.index') }}";
    </script>
@endsection