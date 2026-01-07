<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Crear Nueva Unidad - ELAN SPA</title>

    <link href="https://fonts.googleapis.com/css2?family=Kumbh+Sans:wght@300;400;600;700&display=swap" rel="stylesheet" />

    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite(['resources/css/modulos/menu_modulos.css'])
    @endif

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css" />
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
      
</head>
<body style="background-color: #f8f9fa; min-height: 100vh; display: flex; flex-direction: column;">

    <header class="page-header">
        <a href="{{ route('modulos') }}" class="menu-btn" style="text-decoration: none;">
            <i class="fas fa-arrow-left fa-2x"></i>
        </a>
    
        <img src="{{ asset('images/LOGO_ES.png') }}" alt="Logo" class="header-logo" />
    
        <!-- Espaciador para mantener el logo centrado -->
        <div style="width: 50px;"></div>
    </header>

    @php
        use App\Models\Unidad;
        use App\Models\Spa;
        $unidades = Unidad::orderBy('created_at', 'desc')->get();
        $spas = Spa::whereNotIn('nombre', ['new', 'NewUnid'])->get();
    @endphp

    <main class="container-fluid my-auto">
        <div class="row justify-content-center g-4">
            <!-- Columna del formulario -->
            <div class="col-lg-5">
                <div id="unidad-form" class="unidad-modal-content h-100">
                    <form action="{{ route('unidades.store') }}" method="POST" enctype="multipart/form-data">
                        @csrf
                        <h5 class="fw-bold mb-3">Crear unidad</h5>

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
                            <input type="text" name="nombre_unidad" class="form-control" value="{{ old('nombre_unidad') }}" required>
                        </div>

                        <div class="mb-3">
                            <label class="form-label" for="color_unidad">Color de la unidad:</label>
                            <input type="color" id="color_unidad" name="color_unidad" class="form-control form-control-color" value="{{ old('color_unidad', '#000000') }}" title="Elige un color">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Logo de unidad</label>
                            <input type="file" name="logo_unidad" accept="image/*" class="form-control">
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Logo principal (para menú lateral)</label>
                            <input type="file" name="logo_unidad_superior" accept="image/*" class="form-control" required>
                            <small class="form-text text-muted">Este es el logo que aparece arriba en el menú. Se guardará como <strong>logo.png</strong>.</small>
                        </div>

                        <div class="mb-3">
                            <label class="form-label">Imagen decorativa (para menú lateral):</label>
                            <input type="file" name="logo_unidad_inferior" accept="image/*" class="form-control">
                            <small class="form-text text-muted">Esta es la imagen decorativa de abajo. Se guardará como <strong>decorativo.png</strong>.</small>
                        </div>

                        <div class="d-flex gap-2 mt-4">
                            <button type="submit" class="btn btn-primary">Guardar</button>
                            <a href="{{ route('modulos') }}" class="btn btn-secondary">Cancelar</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Columna de unidades registradas -->
            <div class="col-lg-5">
                <div class="unidad-modal-content h-100">
                    <h5 class="fw-bold mb-3">Unidades Registradas</h5>
                    @if($spas->isEmpty() && $unidades->isEmpty())
                        <p class="text-muted">Aún no hay unidades registradas.</p>
                    @else
                        <ul class="list-group list-group-flush">
                            {{-- Spas Fijos --}}
                            @foreach ($spas as $spa)
                                <li class="list-group-item px-0">
                                    <span>{{ ucfirst($spa->nombre) }}</span>
                                </li>
                            @endforeach

                            {{-- Unidades Personalizadas --}}
                            @foreach ($unidades as $unidad)
                                <li class="list-group-item d-flex justify-content-between align-items-center px-0" id="unidad-row-{{ $unidad->id }}">
                                    <div>
                                        @if($unidad->logo_superior)
                                            <img src="{{ asset($unidad->logo_superior) }}" alt="{{ $unidad->nombre_unidad }}" class="me-2" style="height: 40px; width: auto; object-fit: contain;">
                                        @else
                                            <span class="me-2">{{ $unidad->nombre_unidad }}</span>
                                        @endif
                                        <a href="{{ route('unidad.edit', $unidad->id) }}" class="ms-3 text-secondary" title="Editar unidad">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="#" class="ms-2 text-danger delete-unidad-btn" title="Eliminar unidad" data-unidad-id="{{ $unidad->id }}" data-unidad-nombre="{{ $unidad->nombre_unidad }}">
                                            <i class="fas fa-trash-alt"></i>
                                        </a>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.delete-unidad-btn').forEach(button => {
                button.addEventListener('click', function(event) {
                    event.preventDefault();

                    const unidadId = this.dataset.unidadId;
                    const unidadNombre = this.dataset.unidadNombre;

                    if (confirm(`¿Estás seguro de que quieres eliminar la unidad "${unidadNombre}"?`)) {
                        eliminarUnidad(unidadId);
                    }
                });
            });
        });

        async function eliminarUnidad(id) {
            const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            try {
                const response = await fetch(`/unidades/${id}`, {
                    method: 'DELETE',
                    headers: {
                        'X-CSRF-TOKEN': csrfToken,
                        'Accept': 'application/json'
                    }
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    // Eliminar el elemento de la lista del DOM
                    const unidadElement = document.getElementById(`unidad-row-${id}`);
                    if (unidadElement) {
                        unidadElement.remove();
                    }
                    // Opcional: Mostrar una alerta de éxito
                    // alert(data.message);
                } else {
                    alert('Error: ' + (data.message || 'No se pudo eliminar la unidad.'));
                }
            } catch (error) {
                console.error('Error al eliminar la unidad:', error);
                alert('Ocurrió un error de red. Inténtalo de nuevo.');
            }
        }
    </script>
</body>
</html>
