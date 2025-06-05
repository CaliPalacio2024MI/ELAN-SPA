<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite('resources/css/general_styles.css')
        @vite('resources/css/gimnasio/gimnasio_public_styles.css')
    @endif
    <title>Reglamento del gimnasio</title>
</head>

<body>
    <header
        style="height: 10dvh; display: flex; align-items: center; justify-content: center; background-color: #eee;">
        <img src="{{ asset('images/LOGO_MI.png') }}" alt="Logo de Mundo Imperial"
            style="height: 100%; width: auto;">
    </header>

    <main style="background-color: #eee">
        <div class="main-container"
            style="display: flex; flex-direction: column; align-items: center; height: 100%; margin: 0;">
            <div class="header">
                <h2 style="text-align: center; font-size: 1.5rem;">Reglamento del Gimnasio</h2>
            </div>
            <div style="width: 80%;">
                @include('gimnasio.componentes.reglamento')
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
