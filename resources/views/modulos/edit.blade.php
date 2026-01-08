<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Editar Unidad - ELAN SPA</title>

    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;600;700&display=swap" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/modulos/menu_modulos.css'])
    @endif

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
      
</head>
<body style="background-color: #f8f9fa; min-height: 100vh; display: flex; flex-direction: column;">

    <header class="page-header">
        {{-- La ruta 'unidades.create' nos lleva de vuelta a la página con el listado --}}
        <a href="{{ route('unidades.create') }}" class="menu-btn" style="text-decoration: none;">
            <i class="fas fa-arrow-left fa-2x"></i>
        </a>
    
        <img src="{{ asset('images/LOGO_ES.png') }}" alt="Logo" class="header-logo" />
    
        <div style="width: 50px;"></div>
    </header>

    <main class="container my-auto">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="unidad-modal-content">
                    {{-- El formulario apunta a la ruta de actualización y usa el método PUT --}}
                    <form action="{{ route('unidad.update', $unidad->id) }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <h5 class="fw-bold mb-3">Editar unidad: {{ $unidad->nombre_unidad }}</h5>

                        @if ($errors->any())
                            <div class="alert alert-danger">
                                <ul>
                                    @foreach ($errors->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif

                        <div class="mb-3">
                            <label class="form-label">Nombre de la unidad:</label>
                            {{-- Usamos old() para mantener el valor si la validación falla, si no, usamos el de la BD --}}
                            <input type="text" name="nombre_unidad" class="form-control" value="{{ old('nombre_unidad', $unidad->nombre_unidad) }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="color_unidad">Color de la unidad:</label>
                            <input type="color" id="color_unidad" name="color_unidad" class="form-control form-control-color" value="{{ old('color_unidad', $unidad->color_unidad) }}" title="Elige un color">
                        </div>

                        <div class="mb-3">
                            <label for="logo_unidad_principal" class="form-label">Logo Principal de la Unidad</label>
                            <input type="file" name="logo_unidad_principal" class="form-control">
                            @if($unidad->logo_unidad)
                                <div class="mt-2">
                                    <small>Logo actual:</small>
                                    <img src="{{ asset($unidad->logo_unidad) }}" alt="Logo Principal" style="max-height: 50px; background: #e0e0e0; border-radius: 4px;">
                                </div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Logo superior</label>
                            <input type="file" name="logo_unidad_superior" accept="image/*" class="form-control">
                            @if($unidad->logo_superior)
                                <div class="mt-2">
                                    <small>Logo actual:</small>
                                    <img src="{{ asset($unidad->logo_superior) }}" alt="Logo Superior" style="max-height: 50px; background: #e0e0e0; border-radius: 4px;">
                                </div>
                            @endif
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Logo inferior:</label>
                            <input type="file" name="logo_unidad_inferior" accept="image/*" class="form-control">
                             @if($unidad->logo_inferior)
                                <div class="mt-2">
                                    <small>Logo actual:</small>
                                    <img src="{{ asset($unidad->logo_inferior) }}" alt="Logo Inferior" style="max-height: 50px; background: #e0e0e0; border-radius: 4px;">
                                </div>
                            @endif
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">Actualizar</button>
                            {{-- Regresamos a la página de creación/listado --}}
                            <a href="{{ route('unidades.create') }}" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

</body>
</html>