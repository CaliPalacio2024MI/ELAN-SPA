@extends('layouts.spa_menu')

@section('roleValidation')
    @if (!in_array(Auth::user()->rol, ['master', 'administrador']))
        <script>
            alert('Acceso denegado.');
            window.location.href = "{{ route('dashboard') }}";
        </script>
        @php exit; @endphp
    @endif
@endsection

@section('css')
    @php
        $spaCss = session('current_spa') ?? strtolower(optional(Auth::user()->spa)->nombre);
        if ($spaCss === 'newunid') $spaCss = 'palacio';
    @endphp
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite('resources/css/menus/' . $spaCss . '/menu_styles.css')
        @vite('resources/css/general_styles.css')
        @vite('resources/css/gestores/g_familias_styles.css')
        @vite('resources/css/componentes/autoComplete.css')
        @vite('resources/css/componentes/modal.css')
    @endif

    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
@endsection

@section('content')
    <div class="main-container">
        <header class="header">
            <div class="header-left">
            </div>
            <h2 class="header-title">GESTIONAR FAMILIAS</h2>
            <div class="header-right">
                <button type="button" class="btn" id="boton-nueva-familia">
                    Nueva Familia
                </button>
            </div>
        </header>

        <div class="table-container">
            <div class="table-margin">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Nombre de la Familia</th>
                            <th>Artículos Asociados</th>
                            <th style="text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach ($familias as $familia)
                            <tr>
                                <td>{{ $familia->nombre }}</td>
                                <td>{{ $familia->total_articulos }}</td>
                                <td style="width: 180px;">
                                    <div class="botones-accion"
                                        style="display: flex; gap: 10px; flex-direction: row; justify-content: center;">
                                        <button class="fas fa-eye fa-lg boton-ver-familia"
                                            style="width: 40px; background: none; border: none;"
                                            data-familia-id="{{ $familia->id }}" title="Ver artículos"></button>
                                        <button class="fas fa-pen-to-square fa-lg boton-editar-familia"
                                            style="width: 40px; background: none; border: none;"
                                            data-familia-id="{{ $familia->id }}"
                                            data-familia-nombre="{{ $familia->nombre }}" title="Editar familia"></button>
                                        <button class="fas fa-delete-left fa-lg boton-eliminar-familia"
                                            style="width: 40px; color: #ac0505; background: none; border: none;"
                                            data-familia-id="{{ $familia->id }}"
                                            data-familia-nombre="{{ $familia->nombre }}"
                                            data-total-articulos="{{ $familia->total_articulos }}"
                                            title="Eliminar familia"></button>
                                    </div>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Modal para agregar nueva familia --}}
        @php $modalNuevaFamiliaId = 'modal-nueva-familia'; @endphp
        <x-modal-slot id="{{ $modalNuevaFamiliaId }}">
            <form id="{{ $modalNuevaFamiliaId }}-form" class="form"
                style="display: flex; flex-direction: column; gap: 15px;">
                @csrf
                <div class="form-group" style="display: flex; flex-direction: column; gap: 5px;">
                    <label for="nueva_nombre_familia" style="text-align: left">Nombre de la Familia:</label>
                    <input type="text" id="nueva_nombre_familia" name="nombre_familia" class="form-control"
                        maxlength="50" required placeholder="Ej: Corporal, Facial, Cabello...">
                </div>
            </form>
            @slot('footer')
                <button class="btn" style="min-width: 120px; background-color: #6c757d"
                    onclick="modalSlotCerrar('{{ $modalNuevaFamiliaId }}')">Cancelar</button>
                <button id="{{ $modalNuevaFamiliaId }}-boton-confirmar" class="btn"
                    style="min-width: 120px;" onclick="confirmarNuevaFamilia()">Agregar</button>
            @endslot
        </x-modal-slot>

        {{-- Modal para ver artículos de la familia --}}
        @php $modalVerFamiliaId = 'modal-ver-familia'; @endphp
        <x-modal-slot id="{{ $modalVerFamiliaId }}">
            <div id="contenido-articulos-familia">
                <p>Cargando...</p>
            </div>
            @slot('footer')
                <button class="btn" style="min-width: 120px; background-color: #6c757d"
                    onclick="modalSlotCerrar('{{ $modalVerFamiliaId }}')">Cerrar</button>
            @endslot
        </x-modal-slot>

        {{-- Modal para editar familia --}}
        @php $modalEditarFamiliaId = 'modal-editar-familia'; @endphp
        <x-modal-slot id="{{ $modalEditarFamiliaId }}">
            <div>
                <form id="{{ $modalEditarFamiliaId }}-form" class="form"
                    style="display: flex; flex-direction: column; gap: 15px;">
                    @csrf
                    <input type="hidden" id="editar_familia_id" name="familia_id">
                    <div class="form-group" style="display: flex; flex-direction: column; gap: 5px;">
                        <label for="editar_nombre_familia" style="text-align: left">Nombre de la Familia:</label>
                        <input type="text" id="editar_nombre_familia" name="nombre_familia" class="form-control"
                            maxlength="50" required>
                    </div>
                </form>
            </div>
            @slot('footer')
                <button id="{{ $modalEditarFamiliaId }}-boton-confirmar" class="btn"
                    style="min-width: 120px; background-color: #28a745" onclick="confirmarEditarFamilia()">Guardar</button>
                <button class="btn" style="min-width: 120px; background-color: #6c757d"
                    onclick="modalSlotCerrar('{{ $modalEditarFamiliaId }}')">Cancelar</button>
            @endslot
        </x-modal-slot>

        {{-- Modales de mensaje y confirmación --}}
        <x-modal-mensaje id="modal-mensaje" />
        <x-modal-confirmar id="modal-confirmar" />
    </div>
@endsection

@section('scripts')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Botón nueva familia
            document.getElementById('boton-nueva-familia').addEventListener('click', function() {
                nuevaFamilia();
            });

            // Botón ver familia
            document.querySelectorAll('.boton-ver-familia').forEach(button => {
                button.addEventListener('click', async function() {
                    const familiaId = this.dataset.familiaId;
                    await verArticulosFamilia(familiaId);
                });
            });

            // Botón editar familia
            document.querySelectorAll('.boton-editar-familia').forEach(button => {
                button.addEventListener('click', function() {
                    const familiaId = this.dataset.familiaId;
                    const familiaNombre = this.dataset.familiaNombre;
                    editarFamilia(familiaId, familiaNombre);
                });
            });

            // Botón eliminar familia
            document.querySelectorAll('.boton-eliminar-familia').forEach(button => {
                button.addEventListener('click', function() {
                    const familiaId = this.dataset.familiaId;
                    const familiaNombre = this.dataset.familiaNombre;
                    const totalArticulos = parseInt(this.dataset.totalArticulos);

                    if (totalArticulos > 0) {
                        MundoImperial.modalMensajeMostrar("modal-mensaje", "No se puede eliminar",
                            `<p>No se puede eliminar la familia "${familiaNombre}" porque tiene ${totalArticulos} artículo(s) asociado(s).</p><p>Primero debe eliminar o reasignar los artículos de esta familia.</p>`
                        );
                    } else {
                        MundoImperial.modalConfirmarMostrar("modal-confirmar",
                            `¿Estás seguro de eliminar la familia "${familiaNombre}"?`,
                            () => eliminarFamilia(familiaId));
                    }
                });
            });
        });

        function nuevaFamilia() {
            // Limpiar el formulario
            document.getElementById('nueva_nombre_familia').value = '';
            MundoImperial.modalSlotMostrar("{{ $modalNuevaFamiliaId }}", "Agregar Nueva Familia");
            document.getElementById('nueva_nombre_familia').focus();
        }

        async function confirmarNuevaFamilia() {
            const botonConfirmar = document.getElementById("{{ $modalNuevaFamiliaId }}-boton-confirmar");
            botonConfirmar.disabled = true;

            try {
                const nombreFamilia = document.getElementById('nueva_nombre_familia').value.trim();

                if (!nombreFamilia) {
                    MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Falta información!",
                        "<p>El nombre de la familia es obligatorio.</p>");
                    botonConfirmar.disabled = false;
                    return;
                }

                if (nombreFamilia.length > 50) {
                    MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Nombre muy largo!",
                        "<p>El nombre de la familia no puede exceder los 50 caracteres.</p>");
                    botonConfirmar.disabled = false;
                    return;
                }

                const response = await fetch('/boutique/familias/agregar', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        nombre_familia: nombreFamilia
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Éxito!",
                        "<p>La familia se ha agregado correctamente.</p>");

                    modalSlotCerrar("{{ $modalNuevaFamiliaId }}");

                    const modal = document.getElementById("modal-mensaje");
                    if (modal) {
                        modal.addEventListener("modal-cerrado", function() {
                            location.reload();
                        }, {
                            once: true
                        });
                    }
                } else {
                    MundoImperial.modalMensajeMostrar("modal-mensaje", "Error",
                        `<p>${data.message}</p>`);
                }
            } catch (error) {
                console.error('Error:', error);
                MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Error!",
                    '<p>Hubo un error al agregar la familia. Por favor, contacte a soporte técnico.</p>');
            } finally {
                botonConfirmar.disabled = false;
            }
        }

        async function verArticulosFamilia(familiaId) {
            try {
                const response = await fetch(`/boutique/familias/${familiaId}/articulos`, {
                    method: 'GET',
                    headers: {
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                    }
                });

                const data = await response.json();

                if (data.success) {
                    document.getElementById('{{ $modalVerFamiliaId }}-cuerpo').style.padding = '0';
                    let contenidoHTML = '';
                    if (data.articulos.length === 0) {
                        contenidoHTML =
                            '<p style="text-align: center; color: #6c757d; font-style: italic;">No hay artículos asociados a esta familia en este hotel.</p>';
                    } else {
                        contenidoHTML = `
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Nombre</th>
                                <th>Descripción</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                        data.articulos.forEach(articulo => {
                            contenidoHTML += `
                        <tr>
                            <td>${articulo.nombre_articulo}</td>
                            <td>${articulo.descripcion || 'Sin descripción'}</td>
                        </tr>
                    `;
                        });

                        contenidoHTML += '</tbody></table>';
                    }

                    document.getElementById('contenido-articulos-familia').innerHTML = contenidoHTML;
                    MundoImperial.modalSlotMostrar("{{ $modalVerFamiliaId }}", `${data.familia_nombre}`);
                } else {
                    MundoImperial.modalMensajeMostrar("modal-mensaje", "Error",
                        `<p>${data.message || 'Error al cargar los artículos'}</p>`);
                }
            } catch (error) {
                console.error('Error:', error);
                MundoImperial.modalMensajeMostrar("modal-mensaje", "Error",
                    '<p>Error al cargar los artículos de la familia</p>');
            }
        }

        function editarFamilia(familiaId, familiaNombre) {
            document.getElementById('editar_familia_id').value = familiaId;
            document.getElementById('editar_nombre_familia').value = familiaNombre;
            MundoImperial.modalSlotMostrar("{{ $modalEditarFamiliaId }}", "Editar Familia");
            document.getElementById('editar_nombre_familia').focus();
        }

        async function confirmarEditarFamilia() {
            const botonConfirmar = document.getElementById("{{ $modalEditarFamiliaId }}-boton-confirmar");
            botonConfirmar.disabled = true;

            try {
                const familiaId = document.getElementById('editar_familia_id').value;
                const nombreFamilia = document.getElementById('editar_nombre_familia').value.trim();

                if (!nombreFamilia) {
                    MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Falta información!",
                        "<p>El nombre de la familia es obligatorio.</p>");
                    botonConfirmar.disabled = false;
                    return;
                }

                const response = await fetch(`/boutique/familias/${familiaId}/editar`, {
                    method: 'PUT',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        nombre_familia: nombreFamilia
                    }),
                });

                const data = await response.json();

                if (data.success) {
                    MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Éxito!",
                        "<p>La familia se ha actualizado correctamente.</p>");

                    modalSlotCerrar("{{ $modalEditarFamiliaId }}");

                    const modal = document.getElementById("modal-mensaje");
                    if (modal) {
                        modal.addEventListener("modal-cerrado", function() {
                            location.reload();
                        }, {
                            once: true
                        });
                    }
                } else {
                    MundoImperial.modalMensajeMostrar("modal-mensaje", "Error",
                        `<p>${data.message}</p>`);
                }
            } catch (error) {
                console.error('Error:', error);
                MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Error!",
                    '<p>Hubo un error al actualizar la familia. Por favor, contacte a soporte técnico.</p>');
            } finally {
                botonConfirmar.disabled = false;
            }
        }

        async function eliminarFamilia(familiaId) {
            try {
                const response = await fetch(`/boutique/familias/${familiaId}/eliminar`, {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json',
                    }
                });

                const data = await response.json();

                if (data.success) {
                    MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Éxito!",
                        "<p>La familia se ha eliminado correctamente.</p>");

                    const modal = document.getElementById("modal-mensaje");
                    if (modal) {
                        modal.addEventListener("modal-cerrado", function() {
                            location.reload();
                        }, {
                            once: true
                        });
                    }
                } else {
                    MundoImperial.modalMensajeMostrar("modal-mensaje", "Error",
                        `<p>${data.message}</p>`);
                }
            } catch (error) {
                console.error('Error:', error);
                MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Error!",
                    '<p>Hubo un error al eliminar la familia. Por favor, contacte a soporte técnico.</p>');
            }
        }
    </script>
@endsection
