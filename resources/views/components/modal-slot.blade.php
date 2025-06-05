@props([
    'id' => 'modal-slot',
    'titulo' => 'Mensaje',
])

<div id="{{ $id }}" class="modal-slot"> <!-- Cortina negra -->
    <div class="modal-slot-contenido"> <!-- Modal -->
        <div class="modal-slot-header">
            <h2 id="{{ $id }}-titulo">{{ $titulo }}</h2>
        </div>
        <div class="modal-slot-body" id="{{ $id }}-cuerpo">
            {!! $slot !!}
        </div>
        <div class="modal-slot-footer">
            {{ $footer ?? '' }}
        </div>
    </div>
</div>

@once
    <script>
        window.MundoImperial = window.MundoImperial || {};
        window.MundoImperial.modalSlotMostrar = modalSlotMostrar;
        window.MundoImperial.modalSlotCerrar = modalSlotCerrar;

        function modalSlotMostrar(id, titulo) {
            const modal = document.getElementById(id);
            const modalContenido = modal.querySelector(".modal-slot-contenido");

            // Habilitar el modal para la accesibilidad
            modal.inert = false;

            // Actualizar contenido dinámico
            modal.querySelector(`#${id}-titulo`).textContent = titulo;

            // Animación de entrada
            modalContenido.classList.remove("mostrar");
            void modalContenido.offsetWidth;
            modalContenido.classList.add("mostrar");
            modal.classList.add("mostrar");
        }

        function modalSlotCerrar(id) {
            const modal = document.getElementById(id);
            const modalContenido = modal.querySelector(".modal-slot-contenido");

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
