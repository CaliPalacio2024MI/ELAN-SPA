<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite('resources/css/general_styles.css')
    @endif
    <title>Gimnasio</title>
</head>

<body>
    <header
        style="height: 15dvh; display: flex; align-items: center; justify-content: center; background-color: #eee;">
        <img src="{{ asset('images/LOGO_MI.png') }}" alt="Logo de Mundo Imperial"
            style="height: 100%; width: auto;">
    </header>

    <main style="height: 75dvh; background-color: #eee">
        <div class="main-container"
            style="display: flex; flex-direction: column; align-items: center; justify-content: center; height: 70%; margin: 0;">
            <div class="header">
                <div></div>
                <h2 style="text-align: center; font-size: 2rem;">Tokén Inválido / Invalid Token</h2>
                @if (isset($mensaje))
                    <p style="font-size: 1.5rem; text-wrap: wrap; text-align: center;">{{ $mensaje }}</p>
                @endif
                <div></div>
            </div>
        </div>
    </main>

    <footer>
        <div class="linea decorativa xd" style="background-color: var(--primary3); height: 10dvh; width: 100%;"></div>
    </footer>

    <!-- Scripts-->
    <!-- Script -->
    <script></script>
</body>

</html>
