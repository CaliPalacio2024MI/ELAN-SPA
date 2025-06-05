@props([
    'id' => 'modal-confirmar',
    'titulo' => 'Mensaje',
    'onConfirm' => null,
])

<div id="{{ $id }}" class="modal-confirmar"> <!-- Cortina negra -->
    <div class="modal-confirmar-contenido"> <!-- Modal -->
        <div class="modal-confirmar-header">
            <h2 id="{{ $id }}-titulo">{{ $titulo }}</h2>
        </div>
        <div class="modal-confirmar-footer">
            <button id="{{ $id }}-boton-cancelar" class="btn"
                style="min-width: 120px; background-color: #ac0505"
                onclick="modalConfirmarCerrar('{{ $id }}')">Cancelar</button>
            <button id="{{ $id }}-boton-confirmar" class="btn" style="min-width: 120px;">Confirmar</button>
        </div>
    </div>
</div>

@once
    <script>
        window.MundoImperial = window.MundoImperial || {};
        window.MundoImperial.modalConfirmarMostrar = modalConfirmarMostrar;
        window.MundoImperial.modalConfirmarCerrar = modalConfirmarCerrar;

        function modalConfirmarMostrar(id, titulo, onConfirm) {
            const modal = document.getElementById(id);
            const modalContenido = modal.querySelector(".modal-confirmar-contenido");

            // Habilitar el modal para la accesibilidad
            modal.inert = false;

            // Actualizar contenido dinámico
            modal.querySelector(`#${id}-titulo`).textContent = titulo;

            // Limpiar listeners anteriores
            const btnConfirmar = modal.querySelector(`#${id}-boton-confirmar`);
            const newBtnConfirmar = btnConfirmar.cloneNode(true);
            btnConfirmar.replaceWith(newBtnConfirmar);

            // Agregar nueva función
            newBtnConfirmar.addEventListener("click", () => {
                if (typeof onConfirm === "function") {
                    onConfirm();
                } else {
                    console.warn("No se ha definido función de confirmación");
                }
                modalConfirmarCerrar(id);
            });

            // Animación de entrada
            modalContenido.classList.remove("mostrar");
            void modalContenido.offsetWidth;
            modalContenido.classList.add("mostrar");
            modal.classList.add("mostrar");
        }

        function modalConfirmarCerrar(id) {
            const modal = document.getElementById(id);
            const modalContenido = modal.querySelector(".modal-confirmar-contenido");

            modal.inert = true; // Deshabilitar el modal para la accesibilidad

            // Ocultar cortina y contenido con animación inversa
            modal.classList.remove("mostrar");
            modalContenido.classList.remove("mostrar");

            // Esperar a que la animación termine antes de devolver el modal a su posición original
            setTimeout(function() {
                modal.dispatchEvent(new Event("modal-cerrado"));
            }, 300);
        }
    </script>
@endonce
