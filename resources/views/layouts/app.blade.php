<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>{{ config('app.name', 'Laravel') }}</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="font-sans antialiased">
        <div class="min-h-screen bg-gray-100">
            @include('layouts.navigation')

            @isset($header)
                @php
                    $headerBgStyle = '';
                    $headerTextStyle = '';
                    $headerClass = 'bg-white'; // Clase de fondo por defecto

                    if ($color = session('current_unidad_color')) {
                        $headerBgStyle = 'background-color: ' . e($color) . ' !important;';
                        $headerClass = ''; // Quitamos el fondo blanco si hay color personalizado

                        // Lógica para determinar si el color de fondo es oscuro
                        $hex = ltrim($color, '#');
                        if (strlen($hex) == 3) {
                            $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
                        }
                        $r = hexdec(substr($hex, 0, 2));
                        $g = hexdec(substr($hex, 2, 2));
                        $b = hexdec(substr($hex, 4, 2));
                        $luminance = (0.299 * $r + 0.587 * $g + 0.114 * $b);

                        // Si la luminancia es baja, el color es oscuro, por lo que el texto debe ser blanco.
                        if ($luminance < 140) {
                            $headerTextStyle = 'color: white !important;';
                        }
                    }
                @endphp
                <header class="{{ $headerClass }} shadow" style="{{ $headerBgStyle }}">
                    <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8" style="{{ $headerTextStyle }}">
                        {{ $header }}
                    </div>
                </header>
            @endisset

            <main>
                @yield('content')
            </main>
        </div>
    </body>
</html>
