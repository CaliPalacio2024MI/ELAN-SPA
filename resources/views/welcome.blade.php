<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <meta http-equiv="Cache-Control" content="no-store, no-cache, must-revalidate, max-age=0">
        <meta http-equiv="Pragma" content="no-cache">
        <meta http-equiv="Expires" content="0">

    <title>ELAN SPA & WELLNES EXPERIENCE</title>

        <link rel="preconnect" href="https://fonts.bunny.net">
        <link href="https://fonts.bunny.net/css?family=figtree:400,500,600&display=swap" rel="stylesheet" />

        @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
            @vite(['resources/css/app.css', 'resources/js/app.js'])
        @else
        @endif

        <style>
            body {
                background-image: url('{{ asset('images/Tres_propiedades.jpg') }}');
                background-size: cover;
                background-position: center;
                background-repeat: no-repeat;
            }
        </style>
    </head>
    <body >

        <header>
            <div class="logo">
                <img src="{{ asset('images/LOGO_MI.png') }}" alt="Logo de la empresa">
            </div>
        </header>
    
        <div class="auth-container">
            <div class="logo-auth">
                <img src="{{ asset('images/LOGO_ES.png') }}" alt="Logo de autenticación">
            </div>
            <form class="auth-form"method="POST" action="{{ route('login') }}">
                @csrf
                <div class="form-group">
                    <label for="employee-number">Ingrese RFC</label>
                    <div class="input-icon">
                        <i class="fas fa-user"></i>
                        <input type="text" id="rfc" name="rfc" placeholder="RFC">
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Ingrese contraseña</label>
                    <div class="input-icon">
                        <i class="fas fa-lock"></i>
                        <input type="password" id="password" name="password" placeholder="Contraseña">
                    </div>
                </div>
                <button type="submit" class="btn-login">Ingresar</button>
                <a href="#" class="forgot-password" onclick="document.getElementById('miDialogo').showModal(); return false;">¿Has olvidado la contraseña?</a>
                @if(session('status'))
                <p style="color: green;">{{ session('status') }}</p>
                @endif
            </form>
        </div>
        <dialog id="miDialogo">
            <button class="close-dialog" onclick="document.getElementById('miDialogo').close()">✖</button>
            <div class="dialog-content">
                <p>Le informamos que, para restablecer su contraseña, debe comunicarse con el Departamento de Calidad mediante correo electrónico.</p>
                <p>Al enviar su mensaje, por favor indique su nombre de usuario, su unidad de negocio y la razón de su solicitud.</p>
            </div>
        </dialog>                 
    </body>
</html>
