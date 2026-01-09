@extends('layouts.spa_menu')

@section('logo_img')
    @php
        $spasFolder = session('current_spa') ?? strtolower(optional(Auth::user()->spa)->nombre);
    @endphp
    <img src="{{ asset("images/$spasFolder/logo.png") }}" alt="Logo de {{ ucfirst($spasFolder) }}">
@endsection

@section('css')
    @php
        $spaCss = session('current_spa') ?? strtolower(optional(Auth::user()->spa)->nombre);
    @endphp
    @if (file_exists(public_path('build/manifest.json')) || file_exists(public_path('hot')))
        @vite('resources/css/menus/' . $spaCss . '/menu_styles.css')
        @vite('resources/css/general_styles.css')
        @vite('resources/css/boutique/boutique_inventario_styles.css')
        @vite('resources/css/componentes/autoComplete.css')
        @vite('resources/css/componentes/selectDropdown.css')
        @vite('resources/css/componentes/modal.css')
    @endif
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
@endsection

@section('decorativo')
    @php
        $spasFolder = session('current_spa') ?? strtolower(optional(Auth::user()->spa)->nombre);
        $linDecorativa = asset("images/$spasFolder/decorativo.png");
    @endphp
    <div class="sidebar-decoration" style="background-image: url('{{ $linDecorativa }}');"></div>
@endsection

@section('content')
    <div class="main-container">
        <x-modal-mensaje id="modal-mensaje" />
        <x-modal-confirmar id="modal-confirmar" />

        @php $modalArticuloId = 'modal-articulo'; @endphp
        <x-modal-slot id="{{ $modalArticuloId }}">
            <form id="{{ $modalArticuloId }}-form" class="form">
                <div class="new-articulo-container" style="gap: 0;">
                    <label class="label-new-articulo" for="new_nombre_articulo">Nombre del Artículo *</label>
                    <input type="text" class="input-new-articulo" id="new_nombre_articulo"
                        placeholder="Nombre del artículo" autocomplete="off">
                    <div id="similares-container"
                        style="opacity: 0; margin-top: 0px; pointer-events: none; max-height: 0; transition: opacity 0.3s ease, max-height 0.3s ease;">
                        <p style="font-size: 14px; margin: 0; text-align: left;"><b>Artículos similares:</b></p>
                        <ul id="similares-list" style="margin: 0; padding: 0; list-style-type: none;"></ul>
                    </div>
                </div>
                <div class="new-articulo-container">
                    <label class="label-new-articulo" for="new_familia_articulo">Familia *</label>
                    <x-select-dropdown id="new_familia_articulo" class="input-new-articulo" placeholder="Ingrese familia..."
                        :values="$familias_disponibles" />
                </div>
                <div class="new-articulo-container">
                    <label class="label-new-articulo" for="new_numero_auxiliar">No. Auxiliar del Artículo *</label>
                    <input type="text" class="input-new-articulo" id="new_numero_auxiliar"
                        placeholder="No. Auxiliar del Artículo" autocomplete="off">
                </div>
                <div class="new-articulo-container">
                    <label class="label-new-articulo" for="new_precio_publico_articulo">Precio al Público / Unidad</label>
                    <input type="number" class="input-new-articulo" id="new_precio_publico_articulo"
                        placeholder="Precio al Público / Unidad" autocomplete="off" min="0" step="0.01">
                </div>
                <div class="new-articulo-container">
                    <label class="label-new-articulo" for="new_descripcion">Descripción</label>
                    <input type="text" class="input-new-articulo" id="new_descripcion" placeholder="Descripción"
                        autocomplete="off">
                </div>
            </form>

            @slot('footer')
                <button id="{{ $modalArticuloId }}-boton-cancelar" class="btn"
                    style="min-width: 120px; background-color: #ac0505"
                    onclick="modalSlotCerrar('{{ $modalArticuloId }}')">Cancelar</button>
                <button id="{{ $modalArticuloId }}-boton-confirmar" class="btn" style="min-width: 120px;"
                    onclick="confirmarNuevoArticulo()">Confirmar</button>
            @endslot
        </x-modal-slot>

        @php $modalCompraId = 'modal-compra'; @endphp
        <x-modal-slot id="{{ $modalCompraId }}">
            <form id="{{ $modalCompraId }}-form" class="form">
                <div class="new-articulo-container">
                    <label class="label-new-articulo" for="select_tipo_compra">Tipo de Compra *</label>
                    <x-select-dropdown id="select_tipo_compra" class="form-control" placeholder="Tipo de compra"
                        :values="$tipo_compra" default="normal" />
                </div>
                <div class="new-articulo-container" id="orden_compra_container">
                    <label class="label-new-articulo" for="new_folio_orden">Folio Orden de Compra *</label>
                    <input type="text" class="input-new-articulo" id="new_folio_orden"
                        value="com2500001" readonly autocomplete="off">
                </div>
                <div class="new-articulo-container">
                    <label class="label-new-articulo" for="new_folio_factura">Folio de Factura *</label>
                    <input type="text" class="input-new-articulo" id="new_folio_factura"
                        placeholder="Ingrese número de factura" autocomplete="off">
                </div>
                <div class="new-articulo-container">
                    <label class="label-new-articulo" for="select_articulo">Seleccionar Artículo *</label>
                    <x-auto-complete id="autocomplete_articulo" class="form-control" placeholder="Ingrese artículo..."
                        :values="$articulos_disponibles" :settings="$settings" />
                </div>
                <div class="new-articulo-container">
                    <label class="label-new-articulo" for="new_precio_proveedor">Costo del Proveedor / Unidad (con IVA
                        incluido) *</label>
                    <input type="number" class="input-new-articulo" id="new_precio_proveedor"
                        placeholder="Precio del Proveedor / Unidad" autocomplete="off">
                </div>
                <div class="fechas-caducidad-container" style="display: flex; flex-direction: row; gap: 10px;">
                    <div class="new-articulo-container" style="padding-right: 0;">
                        <label class="label-new-articulo" for="new_cantidad">Cantidad Recibida *</label>
                        <input type="number" class="input-new-articulo" id="new_cantidad" min=1
                            placeholder="Cantidad Recibida">
                    </div>
                    <div class="new-articulo-container" style="padding: 0;">
                        <label class="label-new-articulo" for="new_fecha">Fecha de Caducidad</label>
                        <input type="date" class="input-new-articulo" id="new_fecha"
                            placeholder="Fecha de Caducidad">
                    </div>
                    <div style="display: flex; align-items: center; padding-top: 22px; width: 50px;">
                        {{-- el primero no tendría esto, pero todos los que se van agregando sí --}}
                        {{-- <button class="fas fa-delete-left fa-lg" style="width: 50px; color: #ac0505; background-color: transparent; border: none; cursor: pointer; padding: 24px 10px 24px 10px;"></button> --}}
                    </div>
                </div>
                <div style="display: flex; justify-content: center; align-items: center; padding-bottom: 2px;">
                    <button type="button" class="btn" onclick="agregarFilaFecha()">
                        <i class="fas fa-plus" style="padding-right: 5px;"></i> Dividir Productos
                    </button>
                </div>
            </form>
            @slot('footer')
                <button id="{{ $modalCompraId }}-boton-cancelar" class="btn"
                    style="min-width: 120px; background-color: #ac0505"
                    onclick="modalSlotCerrar('{{ $modalCompraId }}')">Cancelar</button>
                <button id="{{ $modalCompraId }}-boton-confirmar" class="btn" style="min-width: 120px;"
                    onclick="confirmarNuevaCompra()">Confirmar</button>
            @endslot
        </x-modal-slot>

        <!-- Modal para eliminar compra -->
        @php $modalEliminarCompraId = 'modal-eliminar-compra'; @endphp
        <x-modal-slot id="{{ $modalEliminarCompraId }}">
            <form id="{{ $modalEliminarCompraId }}-form" class="form">
                <div class="new-articulo-container">
                    <label class="label-new-articulo" for="motivo_eliminacion">Motivo de la eliminación *</label>
                    <textarea class="input-new-articulo" id="motivo_eliminacion"
                        placeholder="Ejemplo: Producto dañado, error en registro, producto vencido, etc." autocomplete="off"
                        maxlength="255" rows="4" style="resize: vertical; min-height: 80px;" required>
                    </textarea>
                    <small class="form-text text-muted">Máximo 255 caracteres</small>
                </div>
            </form>

            @slot('footer')
                <button id="{{ $modalEliminarCompraId }}-boton-cancelar" class="btn"
                    style="min-width: 120px; background-color: #ac0505"
                    onclick="modalSlotCerrar('{{ $modalEliminarCompraId }}')">Cancelar</button>
                <button id="{{ $modalEliminarCompraId }}-boton-confirmar" class="btn" style="min-width: 120px;"
                    onclick="procederConEliminacion()">Continuar</button>
            @endslot
        </x-modal-slot>

        <!-- Modal para editar compra -->
        @php $modalEditarCompraId = 'modal-editar-compra'; @endphp
        <x-modal-slot id="{{ $modalEditarCompraId }}">
            <form id="{{ $modalEditarCompraId }}-form" class="form">
                <div class="new-articulo-container">
                    <label class="label-new-articulo" for="edit_cantidad_compra">Cantidad Recibida *</label>
                    <input type="number" class="input-new-articulo" id="edit_cantidad_compra"
                        placeholder="Cantidad Recibida" autocomplete="off" min="1">
                </div>
                <div class="new-articulo-container">
                    <label class="label-new-articulo" for="edit_fecha_compra">Fecha de Caducidad</label>
                    <input type="date" class="input-new-articulo" id="edit_fecha_compra"
                        placeholder="Fecha de Caducidad" autocomplete="off">
                </div>
            </form>

            @slot('footer')
                <button id="{{ $modalEditarCompraId }}-boton-cancelar" class="btn"
                    style="min-width: 120px; background-color: #ac0505"
                    onclick="modalSlotCerrar('{{ $modalEditarCompraId }}')">Cancelar</button>
                <button id="{{ $modalEditarCompraId }}-boton-confirmar" class="btn" style="min-width: 120px;"
                    onclick="confirmarEditarCompra()">Confirmar</button>
            @endslot
        </x-modal-slot>

        <!-- Modal para editar artículo -->
        @php $modalEditarArticuloId = 'modal-editar-articulo'; @endphp
        <x-modal-slot id="{{ $modalEditarArticuloId }}">
            <form id="{{ $modalEditarArticuloId }}-form" class="form">
                <div class="new-articulo-container">
                    <label class="label-new-articulo" for="edit_numero_auxiliar">No. Auxiliar del Artículo *</label>
                    <input type="number" class="input-new-articulo" id="edit_numero_auxiliar"
                        placeholder="No. Auxiliar del Artículo" autocomplete="off">
                </div>
                <div class="new-articulo-container">
                    <label class="label-new-articulo" for="edit_nombre_articulo">Nombre del Artículo *</label>
                    <input type="text" class="input-new-articulo" id="edit_nombre_articulo"
                        placeholder="Nombre del artículo" autocomplete="off">
                </div>
                <div class="new-articulo-container">
                    <label class="label-new-articulo" for="edit_familia_articulo">Familia *</label>
                    <x-select-dropdown id="edit_familia_articulo" class="input-new-articulo"
                        placeholder="Ingrese familia..." :values="$familias_disponibles" />
                </div>
                <div class="new-articulo-container">
                    <label class="label-new-articulo" for="edit_precio_publico">Precio al Público / Unidad</label>
                    <input type="number" class="input-new-articulo" id="edit_precio_publico"
                        placeholder="Precio al Público / Unidad" autocomplete="off" min="0" step="0.01">
                </div>
            </form>

            @slot('footer')
                <button id="{{ $modalEditarArticuloId }}-boton-cancelar" class="btn"
                    style="min-width: 120px; background-color: #ac0505"
                    onclick="modalSlotCerrar('{{ $modalEditarArticuloId }}')">Cancelar</button>
                <button id="{{ $modalEditarArticuloId }}-boton-confirmar" class="btn" style="min-width: 120px;"
                    onclick="confirmarEditarArticulo()">Confirmar</button>
            @endslot
        </x-modal-slot>

        <div class="header">
            <a href="{{ route('boutique.inventario.historial') }}" class="btn">
                <i class="fa-solid fa-clock-rotate-left" style="padding-right: 10px;"></i>Historial de Compras
            </a>
            <h2>Inventario Boutique</h2>
            <a href="{{ route('boutique.inventario.eliminaciones') }}" class="btn">
                <i class="fa-solid fa-clock-rotate-left" style="padding-right: 10px;"></i>Compras Eliminadas
            </a>
        </div>

        <!-- Agregar filtros aquí en el futuro -->
        <form method="GET" action="{{ route('boutique.inventario') }}">
            <div class="filter">
                <input type="text" id="filtro-articulo" class="input-filter" placeholder="Buscar artículo...">
                <button type="button" class="input-filter btn" onclick="limpiar_filtro()">Limpiar</button>
            </div>
            <div style="display: flex; flex-direction: row; width:">
                <button type="button" class="btn" onclick="nuevaCompra()"><i class="fa-solid fa-circle-plus"
                        style="padding-right: 10px;"></i>
                    <p style="padding: 0; margin: 0; width: 120px; color: white;">Nueva Compra</p>
                </button>
            </div>
            <div style="display: flex; flex-direction: row;">
                <button type="button" class="btn" onclick="nuevoArticulo()"><i class="fa-solid fa-circle-plus"
                        style="padding-right: 10px;"></i>
                    <p style="padding: 0; margin: 0; width: 120px; color: white;">Nuevo Artículo</p>
                </button>
            </div>
            <div style="display: flex; flex-direction: row;">
                <a href="{{ route('boutique.inventario.excel') }}" class="btn" style="display: flex; align-items: center; text-decoration: none;">
                    <i class="fas fa-download"></i>
                </a>
            </div>
        </form>

        <div class="tables-container"
            style="display: flex; flex-direction: row; justify-content: space-between; gap: 30px; width: 100%;">
            <!-- Tabla de inventario -->
            <div style="min-width: 47%; flex-shrink: 0;">
                <h3>Inventario</h3>
                <div class="table-margin">
                    <table class="table" id="tabla-compras">
                        <thead>
                            <tr>
                                <th>Id</th>
                                <th>No. Auxiliar</th>
                                <th>Nombre</th>
                                <th>Cantidad</th>
                                <th>Fecha Expiración</th>
                                <th style="text-align: center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($compras as $compra)
                                <tr class="fila-articulo">
                                    <td>{{ $compra->compra_id }}</td>
                                    <td>{{ str_pad($compra->numero_auxiliar, 10, '0', STR_PAD_LEFT) }}</td>
                                    <td>{{ $compra->nombre_articulo }}</td>
                                    <td>{{ $compra->cantidad_actual }}</td>
                                    <td>{{ $compra->fecha_caducidad ? \Carbon\Carbon::parse($compra->fecha_caducidad)->format('d/m/Y') : '-' }}
                                    </td>
                                    <td style="width: 120px;">
                                        <div class="botones-accion"
                                            style="display: flex; gap: 10px; flex-direction: row;">
                                            <button class="fas fa-pen-to-square fa-lg" style="width: 50px;"
                                                onclick="editarCompra({{ $compra->compra_id }})"></button>
                                            <button class="fas fa-delete-left fa-lg" style="width: 50px; color: #ac0505;"
                                                onclick="eliminarCompra({{ $compra->compra_id }})"></button>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Tabla de Artículos -->
            <div style="min-width: 47%; flex-shrink: 0;">
                <h3>Artículos</h3>
                <div class="table-margin">
                    <table class="table" id="tabla-articulos">
                        <thead>
                            <tr>
                                <th>No. Auxiliar</th>
                                <th>Nombre</th>
                                <th>Familia</th>
                                <th>Precio al Público</th>
                                <th>Total</th>
                                <th style="text-align: center">Acciones</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach ($articulos as $articulo)
                                <tr class="fila-articulo">
                                    <td>{{ str_pad($articulo->numero_auxiliar, 10, '0', STR_PAD_LEFT) }}</td>
                                    <td>{{ $articulo->nombre_articulo }}</td>
                                    <td>{{ $articulo->familia }}</td>
                                    <td>${{ number_format($articulo->precio_publico_unidad ?? 0, 2) }}</td>
                                    <td>{{ $articulo->total_cantidad }}</td>
                                    <td style="width: 120px;">
                                        <div class="botones-accion"
                                            style="display: flex; gap: 10px; flex-direction: row; justify-content: center;">
                                            <button class="fas fa-pen-to-square fa-lg" style="width: 50px;"
                                                onclick="editarArticulo('{{ $articulo->numero_auxiliar }}')"></button>
                                            {{-- <button class="fas fa-delete-left fa-lg" style="width: 50px; color: #ac0505;"
                                                onclick="eliminarArticulo('{{ $articulo->numero_auxiliar }}')"></button> --}}
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('scripts')
    @vite('resources/js/autocomplete.js')

    <!-- Filtro -->
    <script>
        document.getElementById('filtro-articulo').addEventListener('input', function() {
            const filtro = this.value.toLowerCase();
            const filas = document.querySelectorAll('.fila-articulo');

            filas.forEach(fila => {
                const textoFila = fila.innerText.toLowerCase();
                fila.style.display = textoFila.includes(filtro) ? '' : 'none';
            });
        });

        limpiar_filtro = () => {
            document.getElementById('filtro-articulo').value = '';
            const filas = document.querySelectorAll('.fila-articulo');
            filas.forEach(fila => {
                fila.style.display = '';
            });
        }
    </script>

    <!-- Inicializacion de variables -->
    <script>
        const articulosDisponibles = @json($articulos_disponibles);
        const familiasDisponibles = @json($familias_disponibles);
    </script>

    <!-- Autocomplete - artículos existentes -->
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const input = document.getElementById('new_nombre_articulo');
            const container = document.getElementById('similares-container');
            const list = document.getElementById('similares-list');

            input.addEventListener('input', function() {
                const texto = input.value.trim().toLowerCase();

                if (!texto) {
                    container.style.opacity = '0';
                    container.style.marginTop = '0';
                    container.style.pointerEvents = 'none';
                    container.style.maxHeight = '0';
                    list.innerHTML = '';
                    return;
                }

                const similares = articulosDisponibles
                    .filter(articulo => articulo.value.toLowerCase().includes(texto))
                    .slice(0, 5);

                if (similares.length > 0) {
                    list.innerHTML = '';
                    similares.forEach((art, i) => {
                        const li = document.createElement('li');
                        li.style.opacity = '0';
                        li.style.transform = 'translateY(-5px)';
                        li.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
                        li.innerHTML =
                            `<p style="font-size: 14px; margin: 0; text-align: left;">${art.value}</p>`;
                        list.appendChild(li);
                        // Delay para animar en cascada si quieres (opcional)
                        setTimeout(() => {
                            li.style.opacity = '1';
                            li.style.transform = 'translateY(0)';
                        }, i * 50); // escalonado
                    });
                    container.style.opacity = '1';
                    container.style.marginTop = '10px';
                    container.style.pointerEvents = 'auto';
                    container.style.maxHeight = '200px';
                } else {
                    container.style.opacity = '0';
                    setTimeout(() => {
                        container.style.marginTop = '0';
                    }, 300);
                    container.style.pointerEvents = 'none';
                    container.style.maxHeight = '0';
                    list.innerHTML = '';
                }
            });
        });
    </script>

    <!-- Nueva Compra -->
    <script>
        // Contador para IDs únicos
        let contadorFilas = 1;

        function getFechaActual() {
            const hoy = new Date();
            const anio = hoy.getFullYear();
            const mes = String(hoy.getMonth() + 1).padStart(2, '0');
            const dia = String(hoy.getDate()).padStart(2, '0');
            return `${anio}-${mes}-${dia}`;
        }

        // Función para agregar una nueva fila de fecha de caducidad
        function agregarFilaFecha() {
            contadorFilas++;
            const container = document.querySelector('.fechas-caducidad-container').parentNode;
            const primeraFila = document.querySelector('.fechas-caducidad-container');

            // Crear nueva fila
            const fechaActual = getFechaActual();
            const nuevaFila = document.createElement('div');
            nuevaFila.className = 'fechas-caducidad-container';
            nuevaFila.id = `fecha-row-${contadorFilas}`;
            nuevaFila.style = 'display: flex; flex-direction: row; gap: 10px;';

            nuevaFila.innerHTML = `
                <div class="new-articulo-container" style="padding-right: 0;">
                    <label class="label-new-articulo" for="new_cantidad_${contadorFilas}">Cantidad Recibida *</label>
                    <input type="number" class="input-new-articulo cantidad-recibida" id="new_cantidad_${contadorFilas}" min=1
                        placeholder="Cantidad Recibida">
                </div>
                <div class="new-articulo-container" style="padding: 0;">
                    <label class="label-new-articulo" for="new_fecha_${contadorFilas}">Fecha de Caducidad</label>
                    <input type="date" class="input-new-articulo fecha-caducidad" id="new_fecha_${contadorFilas}" 
                        placeholder="Fecha de Caducidad" value="${fechaActual}" min="${fechaActual}">
                </div>
                <div style="display: flex; align-items: center; padding-top: 22px; width: 50px;">
                    <button type="button" class="fas fa-delete-left fa-lg eliminar-fila" 
                        style="width: 50px; color: #ac0505; background-color: transparent; border: none; cursor: pointer; padding: 24px 10px 24px 10px;"
                        onclick="eliminarFilaFecha('fecha-row-${contadorFilas}')"></button>
                </div>
            `;

            // Insertar después del botón "Agregar fecha de caducidad"
            const botonContainer = document.querySelector('.fechas-caducidad-container').parentNode.lastElementChild;
            container.insertBefore(nuevaFila, botonContainer);
        }

        // Función para eliminar una fila de fecha de caducidad
        function eliminarFilaFecha(idFila) {
            document.getElementById(idFila).remove();
        }

        function nuevaCompra() {
            MundoImperial.modalSlotMostrar("modal-compra", "Agregar Nueva Compra");
            
            // Limpiar campos del formulario para evitar datos residuales
            document.getElementById("new_folio_factura").value = "";
            document.getElementById("autocomplete_articulo").value = "";
            document.getElementById("autocomplete_articulo").dataset.key = "";
            document.getElementById("new_precio_proveedor").value = "";
            document.getElementById("new_cantidad").value = "";

            document.getElementById("autocomplete_articulo").focus();

            const fechaInput = document.getElementById("new_fecha");
            const fechaActual = getFechaActual();
            fechaInput.value = fechaActual;
            fechaInput.min = fechaActual;
        }

        async function confirmarNuevaCompra() {
            const botonConfirmar = document.getElementById("{{ $modalCompraId }}-boton-confirmar");
            botonConfirmar.disabled = true;

            try {
                // Validación básica
                const tipoCompra = document.getElementById("select_tipo_compra").dataset.key;
                const folioOrdenCompra = document.getElementById("new_folio_orden").value.trim();
                const folioFactura = document.getElementById("new_folio_factura").value.trim();
                const articuloInput = document.querySelector("#autocomplete_articulo");
                const numeroAuxiliar = articuloInput.dataset.key;
                const precioProveedor = document.getElementById("new_precio_proveedor").value.trim();

                if (!tipoCompra || !folioFactura || !numeroAuxiliar || !precioProveedor) {
                    MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Falta información!",
                        "<p>Complete todos los campos obligatorios.</p>");
                    botonConfirmar.disabled = false;
                    return;
                }

                if (tipoCompra === 'normal' && !folioOrdenCompra) {
                    MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Falta información!",
                        "<p>El folio de orden de compra es obligatorio para compras normales.</p>");
                    botonConfirmar.disabled = false;
                    return;
                }

                // Recopilar todas las cantidades y fechas de caducidad
                const fechasData = [];
                const filasFechas = document.querySelectorAll('.fechas-caducidad-container');
                let todasLasFilasValidas = true;

                filasFechas.forEach(fila => {
                    const cantidadInput = fila.querySelector('.cantidad-recibida');
                    const fechaInput = fila.querySelector('.fecha-caducidad');

                    if (!cantidadInput || !fechaInput) return;

                    const cantidad = parseInt(cantidadInput.value);
                    const fecha = fechaInput.value;

                    if (!cantidad || isNaN(cantidad) || cantidad <= 0) {
                        MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Error en las cantidades!",
                            "<p>Todas las cantidades deben ser números positivos.</p>");
                        todasLasFilasValidas = false;
                        return;
                    }

                    fechasData.push({
                        fecha_caducidad: fecha || null,
                        cantidad: cantidad
                    });
                });

                if (!todasLasFilasValidas || fechasData.length === 0) {
                    botonConfirmar.disabled = false;
                    MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Falta información!",
                        "<p>Debe agregar al menos una fecha con su cantidad.</p>");
                    return;
                }

                // Preparar datos para enviar al servidor (sin precio público)
                const data = {
                    tipo_compra: tipoCompra,
                    folio_orden_compra: tipoCompra === 'normal' ? folioOrdenCompra : null,
                    folio_factura: folioFactura,
                    numero_auxiliar: numeroAuxiliar,
                    costo_proveedor_unidad: parseFloat(precioProveedor),
                    fechas_cantidades: fechasData
                };

                // Enviar datos al servidor
                const response = await fetch('/boutique/inventario/nueva_compra', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(data),
                });

                const responseData = await response.json();

                if (!response.ok) {
                    throw new Error(responseData.message || 'Error al procesar la solicitud');
                }

                if (responseData.success) {
                    MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Enhorabuena!",
                        "<p>La compra se ha registrado correctamente.</p>");

                    const Modal = document.getElementById("modal-mensaje");
                    if (Modal) Modal.addEventListener("modal-cerrado", function() {
                        location.reload();
                    }, {
                        once: true
                    });
                }
            } catch (error) {
                let errorMessage =
                    "Hubo un error al registrar la compra, favor de informar al equipo de soporte técnico";

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
            } finally {
                botonConfirmar.disabled = false;
            }
        }

        document.getElementById("select_tipo_compra").addEventListener("change", (e) => {
            const ordenContainer = document.getElementById("orden_compra_container");
            ordenContainer.style.display = e.target.dataset.key === "normal" ? "flex" : "none";
        });

        // Inicialización al cargar la página
        document.addEventListener('DOMContentLoaded', function() {
            // Asignar ID a la primera fila
            const primeraFila = document.querySelector('.fechas-caducidad-container');
            if (primeraFila) {
                primeraFila.id = `fecha-row-1`;

                // Asegurar que el primer input tenga la clase adecuada
                const primerCantidadInput = primeraFila.querySelector('input[id="new_cantidad"]');
                const primerFechaInput = primeraFila.querySelector('input[id="new_fecha"]');

                if (primerCantidadInput) primerCantidadInput.classList.add('cantidad-recibida');
                if (primerFechaInput) primerFechaInput.classList.add('fecha-caducidad');
            }
        });
    </script>

    <!-- Nuevo Artículo -->
    <script>
        function nuevoArticulo() {
            MundoImperial.modalSlotMostrar("modal-articulo", "Agregar Nuevo Artículo");
            document.getElementById("new_nombre_articulo").focus();
        }

        async function confirmarNuevoArticulo() {
            const botonConfirmar = document.getElementById("{{ $modalArticuloId }}-boton-confirmar");
            botonConfirmar.disabled = true;

            try {
                // Recopilar datos del formulario
                const nombre = document.getElementById("new_nombre_articulo").value.trim();
                const familiaInput = document.querySelector("#new_familia_articulo");
                const familiaId = familiaInput.dataset.key;
                const no_auxiliar = document.getElementById("new_numero_auxiliar").value.trim();
                const descripcion = document.getElementById("new_descripcion").value.trim();
                const precioPublico = document.getElementById("new_precio_publico_articulo").value
                    .trim();

                // Validación básica
                if (!nombre || !no_auxiliar || !familiaId) {
                    MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Falta información!",
                        "<p>Por favor, complete todos los campos obligatorios correctamente.</p>");
                    botonConfirmar.disabled = false;
                    return;
                }

                if (no_auxiliar.length > 10) {
                    MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Número auxiliar inválido!",
                        "<p>El número auxiliar no puede tener más de 10 caracteres.</p>");
                    botonConfirmar.disabled = false;
                    return;
                }

                // Preparar datos para enviar
                const data = {
                    nombre_articulo: nombre,
                    familia_id: parseInt(familiaId),
                    numero_auxiliar: no_auxiliar,
                    descripcion: descripcion || null,
                    precio_publico_unidad: precioPublico ? parseFloat(precioPublico) :
                        null // Incluir precio público
                };

                // Petición al servidor
                const response = await fetch('/boutique/inventario/nuevo_articulo', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(data),
                });

                const responseData = await response.json();

                if (!response.ok) {
                    throw new Error(responseData.message || 'Error al procesar la solicitud');
                }

                if (responseData.success) {
                    MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Enhorabuena!",
                        "<p>El artículo se ha agregado correctamente.</p>");

                    const Modal = document.getElementById("modal-mensaje");
                    if (Modal) {
                        Modal.addEventListener("modal-cerrado", function() {
                            location.reload();
                        }, {
                            once: true
                        });
                    }
                }
            } catch (error) {
                let errorMessage = 'Error desconocido';

                // Intentar extraer el mensaje de error si está en formato JSON
                try {
                    const errorObj = JSON.parse(error.message);
                    errorMessage = errorObj.message || errorObj.error || error.message;
                } catch (e) {
                    errorMessage = error.message;
                }

                MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Error!",
                    `<p>Hubo un error al agregar el artículo, favor de informar al equipo de soporte técnico:</p><p>${errorMessage}</p>`
                );
            } finally {
                botonConfirmar.disabled = false;
            }
        }
    </script>

    <!-- Botones de acción -->
    <script>
        // ==================== SCRIPTS PARA BOTONES DE ACCIÓN ====================

        // Variables globales para almacenar datos temporales
        let compraSeleccionada = null;
        let articuloSeleccionado = null;

        // ==================== FUNCIONES PARA TABLA DE COMPRAS ====================

        // Función para editar compra
        function editarCompra(compraId) {
            // Buscar los datos de la compra en la tabla
            const fila = document.querySelector(`button[onclick="editarCompra(${compraId})"]`).closest('tr');
            const celdas = fila.querySelectorAll('td');

            compraSeleccionada = {
                id: compraId,
                numero_auxiliar: celdas[1].textContent,
                nombre_articulo: celdas[2].textContent,
                cantidad_actual: celdas[3].textContent,
                fecha_caducidad: celdas[4].textContent !== '-' ? celdas[4].textContent : ''
            };

            // Llenar el modal con los datos actuales
            document.getElementById('edit_cantidad_compra').value = compraSeleccionada.cantidad_actual;

            // Convertir fecha de formato dd/mm/yyyy a yyyy-mm-dd para el input date
            if (compraSeleccionada.fecha_caducidad) {
                const fechaLimpia = compraSeleccionada.fecha_caducidad.trim();
                const partesFecha = fechaLimpia.split('/');
                document.getElementById('edit_fecha_compra').value =
                    `${partesFecha[2]}-${partesFecha[1]}-${partesFecha[0]}`;
            } else {
                document.getElementById('edit_fecha_compra').value = '';
            }

            MundoImperial.modalSlotMostrar("modal-editar-compra", "Editar Compra");
        }

        // Función para confirmar edición de compra
        async function confirmarEditarCompra() {
            const botonConfirmar = document.getElementById("modal-editar-compra-boton-confirmar");
            botonConfirmar.disabled = true;

            try {
                const nuevaCantidad = parseInt(document.getElementById('edit_cantidad_compra').value);
                const nuevaFecha = document.getElementById('edit_fecha_compra').value;

                if (!nuevaCantidad || nuevaCantidad <= 0) {
                    MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Error!",
                        "<p>La cantidad debe ser un número positivo.</p>");
                    botonConfirmar.disabled = false;
                    return;
                }

                const data = {
                    compra_id: compraSeleccionada.id,
                    nueva_cantidad: nuevaCantidad,
                    nueva_fecha_caducidad: nuevaFecha || null
                };

                const response = await fetch('/boutique/inventario/editar_compra', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(data),
                });

                const responseData = await response.json();

                if (!response.ok) {
                    throw new Error(responseData.message || 'Error al procesar la solicitud');
                }

                if (responseData.success) {
                    MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Éxito!",
                        "<p>La compra se ha actualizado correctamente.</p>");

                    const Modal = document.getElementById("modal-mensaje");
                    if (Modal) {
                        Modal.addEventListener("modal-cerrado", function() {
                            location.reload();
                        }, {
                            once: true
                        });
                    }
                }
            } catch (error) {
                let errorMessage =
                    "Hubo un error al editar la compra, favor de informar al equipo de soporte técnico";

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
            } finally {
                botonConfirmar.disabled = false;
            }
        }

        // Función para eliminar compra - Primer paso: mostrar modal de motivo
        function eliminarCompra(compraId) {
            compraSeleccionada = {
                id: compraId
            };

            // Limpiar el textarea antes de mostrar el modal
            const motivoTextarea = document.getElementById('motivo_eliminacion');
            if (motivoTextarea) {
                motivoTextarea.value = '';
            }

            MundoImperial.modalSlotMostrar("modal-eliminar-compra", `Eliminar productos con id: ${compraId}`);
        }

        // Función para validar motivo y proceder con confirmación
        function procederConEliminacion() {
            const botonProceder = document.getElementById("modal-eliminar-compra-boton-confirmar");
            botonProceder.disabled = true;

            try {
                const motivo = document.getElementById('motivo_eliminacion').value.trim();

                if (!motivo) {
                    MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Atención!",
                        "<p>Debe ingresar un motivo para la eliminación.</p>");
                    botonProceder.disabled = false;
                    return;
                }

                if (motivo.length > 255) {
                    MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Atención!",
                        "<p>El motivo no debe exceder los 255 caracteres.</p>");
                    botonProceder.disabled = false;
                    return;
                }

                // Guardar el motivo
                compraSeleccionada.motivo = motivo;

                // Cerrar modal de motivo
                MundoImperial.modalSlotCerrar("modal-eliminar-compra");

                // Mostrar modal de confirmación con el motivo
                const mensajeConfirmacion =
                    `¿Estás seguro de que deseas eliminar esta compra?`;
                MundoImperial.modalConfirmarMostrar("modal-confirmar", mensajeConfirmacion, () =>
                    confirmarEliminarCompra());

            } catch (error) {
                let errorMessage = "Hubo un error al eliminar la compra, favor de informar al equipo de soporte técnico";

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
            } finally {
                botonProceder.disabled = false;
            }
        }

        // Función para confirmar eliminación de compra
        async function confirmarEliminarCompra() {
            try {
                const response = await fetch('/boutique/inventario/eliminar_compra', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        compra_id: compraSeleccionada.id,
                        motivo: compraSeleccionada.motivo
                    }),
                });

                const responseData = await response.json();

                if (!response.ok) {
                    throw new Error(responseData.message || 'Error al procesar la solicitud');
                }

                if (responseData.success) {
                    MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Éxito!",
                        `<p>${responseData.message}</p>`);

                    const Modal = document.getElementById("modal-mensaje");
                    if (Modal) {
                        Modal.addEventListener("modal-cerrado", function() {
                            location.reload();
                        }, {
                            once: true
                        });
                    }
                }
            } catch (error) {
                let errorMessage =
                    "Hubo un error al eliminar la compra, favor de informar al equipo de soporte técnico";

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
            }
        }

        // ==================== FUNCIONES PARA TABLA DE ARTÍCULOS ====================

        // Función para editar artículo
        function editarArticulo(numeroAuxiliar) {
            // Buscar los datos del artículo en la tabla
            const fila = document.querySelector(`button[onclick="editarArticulo('${numeroAuxiliar}')"]`).closest('tr');
            const celdas = fila.querySelectorAll('td');

            articuloSeleccionado = {
                numero_auxiliar_original: numeroAuxiliar,
                numero_auxiliar: celdas[0].textContent,
                nombre_articulo: celdas[1].textContent,
                familia: celdas[2].textContent,
                precio_publico: celdas[3].textContent.replace('$', '').replace(',', '')
            };

            // Llenar el modal con los datos actuales
            document.getElementById('edit_numero_auxiliar').value = parseInt(articuloSeleccionado.numero_auxiliar);
            document.getElementById('edit_nombre_articulo').value = articuloSeleccionado.nombre_articulo;
            document.getElementById('edit_precio_publico').value = articuloSeleccionado.precio_publico;
            const familiaSeleccionada = familiasDisponibles.find(f => f.value === articuloSeleccionado.familia);
            document.getElementById('edit_familia_articulo').dataset.key = familiaSeleccionada.key;
            document.getElementById('edit_familia_articulo').value = familiaSeleccionada.value;

            MundoImperial.modalSlotMostrar("modal-editar-articulo", "Editar Artículo");
        }

        // Función para confirmar edición de artículo
        async function confirmarEditarArticulo() {
            const botonConfirmar = document.getElementById("modal-editar-articulo-boton-confirmar");
            botonConfirmar.disabled = true;

            try {
                const nuevoNumeroAuxiliar = document.getElementById('edit_numero_auxiliar').value.trim();
                const nuevoNombre = document.getElementById('edit_nombre_articulo').value.trim();
                const nuevaFamiliaId = document.getElementById('edit_familia_articulo').dataset.key;
                const nuevoPrecio = document.getElementById('edit_precio_publico').value.trim();

                if (!nuevoNumeroAuxiliar || !nuevoNombre || !nuevaFamiliaId) {
                    MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Error!",
                        "<p>Por favor, complete todos los campos obligatorios.</p>");
                    botonConfirmar.disabled = false;
                    return;
                }

                const data = {
                    numero_auxiliar_original: articuloSeleccionado.numero_auxiliar_original,
                    nuevo_numero_auxiliar: nuevoNumeroAuxiliar,
                    nuevo_nombre: nuevoNombre,
                    nueva_familia_id: parseInt(nuevaFamiliaId),
                    nuevo_precio_publico: nuevoPrecio ? parseFloat(nuevoPrecio) : null
                };

                const response = await fetch('/boutique/inventario/editar_articulo', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify(data),
                });

                const responseData = await response.json();

                if (!response.ok) {
                    throw new Error(responseData.message || 'Error al procesar la solicitud');
                }

                if (responseData.success) {
                    MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Éxito!",
                        "<p>El artículo se ha actualizado correctamente.</p>");

                    const Modal = document.getElementById("modal-mensaje");
                    if (Modal) {
                        Modal.addEventListener("modal-cerrado", function() {
                            location.reload();
                        }, {
                            once: true
                        });
                    }
                }
            } catch (error) {
                let errorMessage =
                "Hubo un error al editar el artículo, favor de informar al equipo de soporte técnico";

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
            } finally {
                botonConfirmar.disabled = false;
            }
        }

        // Función para eliminar artículo
        function eliminarArticulo(numeroAuxiliar) {
            articuloSeleccionado = {
                numero_auxiliar: numeroAuxiliar
            };
            MundoImperial.modalConfirmarMostrar("modal-confirmar",
                "¿Estás seguro de que deseas eliminar este artículo?",
                () => confirmarEliminarArticulo());
        }

        // Función para confirmar eliminación de artículo
        async function confirmarEliminarArticulo() {
            try {
                const response = await fetch('/boutique/inventario/eliminar_articulo', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute(
                            'content'),
                        'Accept': 'application/json',
                    },
                    body: JSON.stringify({
                        numero_auxiliar: articuloSeleccionado.numero_auxiliar
                    }),
                });

                const responseData = await response.json();

                if (!response.ok) {
                    throw new Error(responseData.message || 'Error al procesar la solicitud');
                }

                if (responseData.success) {
                    MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Éxito!",
                        `<p>${responseData.message}</p>`);

                    const Modal = document.getElementById("modal-mensaje");
                    if (Modal) {
                        Modal.addEventListener("modal-cerrado", function() {
                            location.reload();
                        }, {
                            once: true
                        });
                    }
                }
            } catch (error) {
                MundoImperial.modalMensajeMostrar("modal-mensaje", "¡Error!",
                    `<p>${error.message}</p>`);
            }
        }
    </script>
@endsection
