@props([
    'id' => 'modal-mensaje',
    'titulo' => 'Mensaje',
    'mensaje' => '',
])

<div id="{{ $id }}" class="modal-mensaje"> <!-- Cortina negra -->
    <div class="modal-mensaje-contenido"> <!-- Modal -->
        <div class="modal-mensaje-header">
            <h2 id="{{ $id }}-titulo">{{ $titulo }}</h2>
        </div>
        <div class="modal-mensaje-body" id="{{ $id }}-cuerpo">
            {!! $mensaje !!}
        </div>
        <div class="modal-mensaje-footer">
            <button id="{{ $id }}-boton-cerrar" class="btn"
                onclick="MundoImperial.modalMensajeCerrar('{{ $id }}')">Cerrar</button>
        </div>
    </div>
</div>

@once
    <script>
        window.MundoImperial = window.MundoImperial || {};
        window.MundoImperial.modalMensajeMostrar = modalMensajeMostrar;
        window.MundoImperial.modalMensajeCerrar = modalMensajeCerrar;

        function modalMensajeMostrar(id, titulo, mensaje) {
            const modal = document.getElementById(id);
            const modalContenido = modal.querySelector(".modal-mensaje-contenido");
            const botonCerrar = modal.querySelector(`#${id}-boton-cerrar`);

            // Habilitar el modal para la accesibilidad
            modal.inert = false;

            // Actualizar contenido dinámico
            modal.querySelector(`#${id}-titulo`).textContent = titulo;
            modal.querySelector(`#${id}-cuerpo`).innerHTML = mensaje;

            // Animación de entrada
            modalContenido.classList.add("mostrar");
            modal.classList.add("mostrar");

            setTimeout(function() {
                botonCerrar.focus();
            }, 300);
        }

        function modalMensajeCerrar(id) {
            const modal = document.getElementById(id);
            const modalContenido = modal.querySelector(".modal-mensaje-contenido");

            modal.inert = true; // Deshabilitar el modal para la accesibilidad

            // Ocultar cortina y contenido con animación inversa
            modalContenido.classList.remove("mostrar");
            modal.classList.remove("mostrar");

            // Esperar a que la animación termine antes de devolver el modal a su posición original
            setTimeout(function() {
                modal.dispatchEvent(new Event("modal-cerrado"));
            }, 300);
        }
    </script>
@endonce
