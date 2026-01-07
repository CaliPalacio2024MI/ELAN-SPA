@extends('layouts.spa_menu')

@section('css')
    @php
        $spaCss = session('current_spa') ?? strtolower(optional(Auth::user()->spa)->nombre);
        if ($spaCss === 'newunid') $spaCss = 'palacio';
    @endphp
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite('resources/css/menus/' . $spaCss . '/menu_styles.css')
        @vite('resources/css/general_styles.css')
        @vite('resources/css/boutique/boutique_venta_styles.css')
        @vite('resources/css/componentes/autoComplete.css')
        @vite('resources/css/componentes/selectDropdown.css')
        @vite('resources/css/componentes/modal.css')
    @endif
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
@endsection

@section('content')
    <div class="main-container">
        <x-modal-mensaje id="modal-mensaje" />
        <x-modal-confirmar id="modal-confirmar" />

        <div class="header">
            <a href="{{ route('boutique.venta.historial') }}" class="btn">
                <i class="fa-solid fa-clock-rotate-left" style="padding-right: 10px;"></i>Historial de Ventas
            </a>
            <h2>Venta de Artículos</h2>
            <div></div>
        </div>

        <div class="formulario">
            <h3>Información de Venta</h3>
            <form id="venta-form">
                @csrf
                <div style="display: flex; flex-direction: row; gap: 35px;">
                    <div class="form-group" style="width: 150%;">
                        <label for="numero_auxiliar">No. Auxiliar:</label>
                        <x-auto-complete id="numero_auxiliar" class="form-control" placeholder="Ingrese artículo..."
                            :values="$articulos" :settings="$settings" />
                    </div>
                    <div class="form-group">
                        <label for="nombre_producto">Artículo:</label>
                        <input type="text" id="nombre_producto" name="nombre_producto" class="form-control" readonly
                            disabled required>
                    </div>
                    <div class="form-group">
                        <label for="precio">Precio:</label>
                        <input type="text" id="precio" name="precio" class="form-control" readonly disabled required>
                    </div>
                    <div class="form-group" style="width: 50%;">
                        <label for="cantidad">Cantidad:</label>
                        <input type="number" id="cantidad" name="cantidad" class="form-control" min="1" required
                            disabled>
                    </div>
                    <div class="form-group" style="width: 50%;">
                        <label for="cantidad">Descuento:</label>
                        <input type="number" id="descuento" name="descuento" class="form-control" min="0"
                            max="100" disabled>
                    </div>
                    <div class="form-group">
                        <label for="subtotal">Subtotal:</label>
                        <input type="number" id="subtotal" name="subtotal" class="form-control" required readonly
                            disabled>
                    </div>
                    <button type="button" id="agregar_producto" class="btn"
                        style="align-self: flex-end; margin-bottom: 0.25rem; min-width: auto; height: 2.5rem;">Agregar</button>
                </div>
                <div
                    style="width: 100%; margin: 5px 0 0 0; padding: 0; border-top: 1px solid rgba(var(--primary5-rgb), 0.3);">
                </div>
                <div style="display: flex; flex-direction: row; gap: 35px;">
                    <div class="form-group">
                        <label for="folio_venta">Folio de Venta:</label>
                        <input type="text" id="folio_venta" name="subtotal" class="form-control" required
                            autocomplete="off" value="{{ $folioVenta }}" disabled>
                    </div>
                    <div class="form-group">
                        <label for="forma_pago">Forma de Pago:</label>
                        <x-select-dropdown id="forma_pago" class="form-control" placeholder="" :values="$formas_pago" />
                    </div>
                    <div class="form-group">
                        <label for="referencia_pago">Referencia de Pago:</label>
                        <input type="text" id="referencia_pago" name="subtotal" class="form-control" required
                            autocomplete="off">
                    </div>
                    @php
                        $fecha = new DateTime();
                        $fechaFormateada = $fecha->format('Y-m-d');
                        $horaFormateada = $fecha->format('H:i');
                    @endphp
                    <div class="form-group" style="width: 50%;">
                        <label for="fecha_venta">Fecha:</label>
                        <input type="date" id="fecha_venta" name="subtotal" class="form-control" required
                            value="{{ $fechaFormateada }}" max="{{ $fechaFormateada }}">
                    </div>
                    <div class="form-group" style="width: 50%;">
                        <label for="hora_venta">Hora:</label>
                        <input type="time" id="hora_venta" name="subtotal" class="form-control" required
                            value="{{ $horaFormateada }}">
                    </div>
                </div>
            </form>
        </div>

        <!-- Tablas para visualizar las ventas -->
        <div class="table-container" id="tabla-ventas-container">
            <h3>Registro de Artículos</h3>
            <div class="table-margin">
                <table class="table" id="tabla-ventas">
                    <thead>
                        <tr>
                            <th>No. Auxiliar</th>
                            <th>Artículo</th>
                            <th>Cantidad</th>
                            <th>% Descuento</th>
                            <th>Subtotal</th>
                            <th>Anfitrión</th>
                            <th>Observación</th>
                            <th style="text-align: center">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td colspan="8" style="text-align: center; font-weight: bold;">No hay artículos
                                registrados</td>
                        </tr>
                    </tbody>
                </table>
            </div>
            <div class="button-container">
                <button type="button" id="proceder_pago" class="btn" onclick="completar_venta()">
                    <i class="fa-solid fa-circle-check" style="padding-right: 10px;"></i>Completar Venta</button>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite('resources/js/autocomplete.js')

    <!-- Datos desde el controlador -->
    <script>
        const anfitriones_data = @json($anfitriones);
        const articulos_data = @json($compra);
    </script>

    <!-- Formulario -->
    <script>
        const inputNumeroAuxiliar = document.getElementById('numero_auxiliar');
        inputNumeroAuxiliar.addEventListener('auto-complete-change', (event) => {
            if (inputNumeroAuxiliar.dataset.key !== "") {
                const articuloSeleccionado = articulos_data.find(articulo => articulo.numero_auxiliar ==
                    inputNumeroAuxiliar.dataset.key);

                if (articuloSeleccionado.nombre_articulo && articuloSeleccionado.precio_publico_unidad) {
                    document.getElementById('precio').value = articuloSeleccionado.precio_publico_unidad;
                    document.getElementById('cantidad').disabled = false;
                    document.getElementById("cantidad").focus();
                    document.getElementById("cantidad").value = 1;
                    document.getElementById('subtotal').value = (document.getElementById('precio').value * document
                        .getElementById('cantidad').value).toFixed(2);
                    document.getElementById('descuento').disabled = false;
                    document.getElementById('nombre_producto').value = articuloSeleccionado.nombre_articulo;
                } else {
                    document.getElementById('cantidad').disabled = true;
                    document.getElementById('cantidad').value = '';
                    document.getElementById('subtotal').value = '';
                    document.getElementById('descuento').disabled = true;
                    document.getElementById('nombre_producto').value = 'No encontrado';
                    document.getElementById('precio').value = '';
                }
                document.getElementById('descuento').value = '';
                verificarCampos();
            }
        })

        document.getElementById('cantidad').addEventListener('input', () => {
            const cantidadInput = document.getElementById("cantidad");
            let valor = cantidadInput.value;
            cantidadInput.value = valor.replace(/[^0-9]/g, "");
            if (parseInt(cantidadInput.value) < 1) {
                cantidadInput.value = 1;
            }
            document.getElementById('subtotal').value = (document.getElementById('precio').value * document
                .getElementById('cantidad').value).toFixed(2);
        });

        const botonEnviar = document.getElementById("agregar_producto");
        const inputsRequeridos = document.querySelectorAll(".form-control[required]");

        function verificarCampos() {
            let algunVacio = false;
            if (document.getElementById('numero_auxiliar').dataset.key == "") algunVacio = true;
            if (document.getElementById('nombre_producto').value.trim() == "") algunVacio = true;
            if (document.getElementById('precio').value.trim() == "") algunVacio = true;
            if (document.getElementById('cantidad').value.trim() == "") algunVacio = true;
            if (document.getElementById('subtotal').value.trim() == "") algunVacio = true;
            botonEnviar.disabled = algunVacio;
        }

        inputsRequeridos.forEach(input => input.addEventListener("input", verificarCampos));
        verificarCampos();
    </script>

    <!-- Tabla Ventas -->
    <script>
        const botonAgregar = document.getElementById("agregar_producto");
        const tablaBody = document.querySelector("#tabla-ventas tbody");

        botonAgregar.addEventListener("click", () => {
            const etiqueta = document.getElementById("numero_auxiliar").dataset.key;
            const etiquetaCompleta = etiqueta.toString().padStart(10, '0');
            const articulo = document.getElementById("nombre_producto").value;
            const cantidad = parseInt(document.getElementById("cantidad").value);
            let descuento = parseInt(document.getElementById("descuento").value || '0');
            if (descuento < 0) descuento = 0;
            if (descuento > 100) descuento = 100;
            let subtotal = (parseFloat(document.getElementById("subtotal").value).toFixed(2));
            let subtotalSubrayado = '';
            if (descuento !== 0) {
                subtotalSubrayado = subtotal;
                subtotal = (subtotal * (1 - descuento / 100)).toFixed(2);
            };

            // Validar que los campos requeridos no estén vacíos
            if (!articulo || isNaN(subtotal) || isNaN(cantidad)) {
                MundoImperial.modalMensajeMostrar("modal-mensaje", "Campos incompletos",
                    "<p>Por favor, completa los campos antes de agregar.</p>");
                return;
            }

            // Crear nueva fila en la tabla
            const nuevaFila = document.createElement("tr");
            const inputId = `anfitrion_input_${Date.now()}`; // ID único para cada input

            // Crear array con nombres completos de anfitriones
            const autoCompleteAnfitrionesValues = anfitriones_data.map(anfitrion => ({
                value: `${anfitrion.apellido_paterno} ${anfitrion.apellido_materno} ${anfitrion.nombre_usuario}`,
                key: anfitrion.RFC
            }));

            const autoCompleteAnfitrionesSettings = {
                searchEngine: 'loose',
                showAllOnFocus: true,
                ignoreAccents: true
            }

            nuevaFila.innerHTML = `
                <td class="td-readonly" style="text-align: left;">${etiquetaCompleta}</td>
                <td class="td-readonly">${articulo}</td>
                <td class="td-readonly" style="text-align: center;">${cantidad}</td>
                <td class="td-readonly" style="text-align: center;">${descuento}</td>
                <td class="td-readonly"><s>${subtotalSubrayado}</s> ${subtotal}</td>
                <td class="td-editable">
                    <div class="autocomplete">
                        <input type="text" id="${inputId}" class="input-anfitrion" placeholder="Escribir anfitrión..." required data-key="" spellcheck="false" autocomplete="off" autocorrect="off" autocapitalize="none">
                    </div>
                </td>
                <td class="td-editable"><input type="text" placeholder="Escribir observación..." maxlength="20" required></td>
                <td class="td-editable"><button class="btn-eliminar fas fa-delete-left fa-lg" style="color: #ac0505;"/></td>
            `;

            const sinArticulos = tablaBody.querySelector("tr td[colspan='8']");
            if (sinArticulos) {
                tablaBody.removeChild(sinArticulos.parentElement); // elimina el <tr> completo
            }

            // Agregar la fila a la tabla
            tablaBody.appendChild(nuevaFila);

            // Obtener el input del anfitrión
            const inputAnfitrion = document.getElementById(inputId);

            // Inicializar autocomplete con las opciones
            MundoImperial.autocomplete(inputAnfitrion, autoCompleteAnfitrionesValues,
                autoCompleteAnfitrionesSettings);

            // Limpiar los campos del formulario
            document.getElementById("cantidad").value = "";
            document.getElementById("descuento").value = "";
            document.getElementById("subtotal").value = "";
            document.getElementById("nombre_producto").value = "";
            document.getElementById("numero_auxiliar").value = "";
            document.getElementById("precio").value = "";
            document.getElementById("cantidad").disabled = true;
            botonAgregar.disabled = true;

            const eliminarProducto = (event) => {
                const fila = event.target.closest("tr");
                fila.remove();
            }

            const botonEliminar = nuevaFila.querySelector(".btn-eliminar");
            botonEliminar.addEventListener("click", (event) => {
                MundoImperial.modalConfirmarMostrar("modal-confirmar",
                    "¿Estás seguro de eliminar este producto?", () => eliminarProducto(event));
            });
        });
    </script>

    <!-- Completar Venta -->
    <script>
        function completar_venta() {
            // Obtener todos los datos de la tabla
            let table = document.getElementById('tabla-ventas');
            let rows = table.querySelectorAll('tbody tr');
            if (rows.length === 0) {
                MundoImperial.modalMensajeMostrar("modal-mensaje", "Aviso",
                    "<p>No hay ventas para guardar.</p>");
                return;
            }

            /* ----- Validar todos los inputs de autocomplete ----- */
            let autocompleteInputs = document.querySelectorAll('.input-anfitrion');
            let allValid = true;

            autocompleteInputs.forEach(input => {
                if (input.dataset.key === "") allValid = false;
            });

            if (!allValid) {
                MundoImperial.modalMensajeMostrar("modal-mensaje", "Campos incompletos",
                    "<p>Por favor, completa los campos de <strong>anfitriones</strong> antes de continuar.</p>");
                return;
            }
            /* ---------- */

            /* ----- Validar los inputs importantes del header ----- */
            const folioVenta = document.getElementById('folio_venta').value.trim();
            const formaPago = parseInt(document.getElementById('forma_pago').dataset.key.trim()) || "";
            const referenciaPago = document.getElementById('referencia_pago').value.trim();
            const fechaVenta = document.getElementById('fecha_venta').value.trim();
            const horaVenta = document.getElementById('hora_venta').value.trim();
            let mensajeError = "";
            if (folioVenta === "") {
                mensajeError += "<p><strong>Folio de venta</strong> no puede estar vacío.</p>";
            }
            if (formaPago === "") {
                mensajeError += "<p><strong>Forma de pago</strong> no puede estar vacío.</p>";
            }
            if (fechaVenta === "") {
                mensajeError += "<p><strong>Fecha de venta</strong> no puede estar vacío.</p>";
            }
            if (horaVenta === "") {
                mensajeError += "<p><strong>Hora de venta</strong> no puede estar vacío.</p>";
            }
            if (mensajeError !== "") {
                MundoImperial.modalMensajeMostrar("modal-mensaje", "Campos incompletos",
                    mensajeError);
                return;
            }

            let venta = {
                folio_venta: folioVenta,
                forma_pago: formaPago,
                referencia_pago: referenciaPago,
                fecha_venta: fechaVenta,
                hora_venta: horaVenta
            };

            let ventaDetalles = [];
            rows.forEach(row => {
                let rowData = {
                    numero_auxiliar: parseInt(row.cells[0].textContent.trim(), 10),
                    cantidad: parseInt(row.cells[2].textContent),
                    descuento: parseFloat(row.cells[3].textContent) || 0,
                    subtotal: parseFloat(row.cells[4].lastChild.textContent.trim()),
                    anfitrion: row.cells[5].querySelector('input')?.dataset.key.trim(),
                    observacion: row.cells[6].querySelector('input')?.value.trim(),
                };
                ventaDetalles.push(rowData);
            });

            const completarVentaFormulario = () => {
                fetch('/boutique/articulo/guardar_venta', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                    },
                    body: JSON.stringify({
                        ventaDetalles,
                        venta
                    }),
                }).then(response => {
                    if (!response.ok) {
                        return response.json().then(errorData => {
                            throw new Error(JSON.stringify(errorData));
                        });
                    }
                    return response.json();
                }).then(data => {
                    if (data.success) {
                        // Limpiar la tabla
                        document.getElementById('tabla-ventas').querySelector('tbody').innerHTML =
                            '';
                        document.getElementById("forma_pago").dataset.key = "";
                        document.getElementById("forma_pago").value = "";
                        document.getElementById("referencia_pago").value = "";
                        document.getElementById("fecha_venta").value = "";
                        document.getElementById("hora_venta").value = "";

                        MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Enhorabuena!",
                            "<p>La venta se completó exitosamente.</p><p>Folio de venta: " +
                            `<strong>${document.getElementById("folio_venta").value}</strong></p>`);

                        const Modal = document.getElementById("modal-mensaje");
                        if (Modal) {
                            Modal.addEventListener("modal-cerrado", function() {
                                location.reload();
                            }, {
                                once: true
                            });
                        }
                    }
                }).catch(error => {
                    let errorMessage =
                        "Hubo un error al completar la venta, favor de informar al equipo de soporte técnico";

                    try {
                        // Intentar parsear el error como JSON
                        const errorData = JSON.parse(error.message);
                        if (errorData.message) {
                            errorMessage = errorData.message;
                        }
                    } catch (parseError) {
                        // Si no se puede parsear, usar el mensaje original del error
                        errorMessage = error.message || errorMessage;
                    }

                    MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Error!",
                        `<p>${errorMessage}</p>`
                    );
                });
            }

            MundoImperial.modalConfirmarMostrar("modal-confirmar",
                "¿Estás seguro de completar la venta?", () => completarVentaFormulario());
        }
    </script>
@endsection
