// resources/js/reservation/modules/editReservationHandler.js

import { ModalHandler } from './modalHandler.js';

export const EditReservationHandler = {
    /**
     * Rellena el formulario de reservación con los datos de una reservación existente para su edición.
     * @param {object} data - Los datos de la reservación a editar.
     */
    async rellenarFormularioEdicion(data) {
        document.getElementById("modalTitle").textContent = "Editar Reservación";
        document.getElementById("saveButton").textContent = "Actualizar Reservación";

        document.getElementById("reserva_id").value = data.id;
        const fechaInput = document.getElementById("fecha_reserva");
        fechaInput.value = data.fecha;
        fechaInput.setAttribute('data-original-date', data.fecha);
        
        window.originalHoraReserva = data.hora; // Guardar hora original

        document.getElementById("duracion").value = data.duracion;
        
        const experienciaSelect = document.getElementById("experiencia_id");
        experienciaSelect.value = data.experiencia_id;

        document.getElementById("cliente_existente_id").value = data.cliente_existente_id || "";
        document.getElementById("correo_cliente").value = data.correo_cliente || "";
        document.getElementById("nombre_cliente").value = data.nombre_cliente || "";
        document.getElementById("apellido_paterno_cliente").value = data.apellido_paterno_cliente || "";
        document.getElementById("apellido_materno_cliente").value = data.apellido_materno_cliente || "";
        document.getElementById("telefono_cliente").value = data.telefono_cliente || "";
        document.getElementById("tipo_visita_cliente").value = data.tipo_visita_cliente || "";

        const datosDiv = document.getElementById("datosCliente");
        datosDiv.style.display = "block";
        document.getElementById("observaciones").value = data.observaciones || "";

        // Mostrar campos de fecha y hora para la edición
        const fechaWrapper = document.getElementById("fecha-wrapper");
        if (fechaWrapper) {
            fechaWrapper.classList.remove("d-none");
        }
        const horaWrapper = document.getElementById("hora-wrapper");
        if (horaWrapper) {
            horaWrapper.classList.remove("d-none");
        }

        const anfitrionWrapper = document.getElementById("anfitrionWrapper");
        const anfitrionSelect = document.getElementById("anfitrion_id_select");
        const cabinaSelect = document.getElementById("cabina_id");
        anfitrionWrapper.style.display = "block";

        // Dispara el evento 'change' en la fecha para que se ejecute el filtro de formHandler.js
        // Esto poblará las cabinas y anfitriones compatibles con la experiencia y fecha.
        fechaInput.dispatchEvent(new Event('change'));

        // Una vez que los selects son filtrados y poblados, asignamos los valores de la reserva.
        anfitrionSelect.value = data.anfitrion_id;
        cabinaSelect.value = data.cabina_id;

        document.getElementById("selected_anfitrion").disabled = true;

        const horaSelect = document.getElementById("hora");
        horaSelect.innerHTML = '<option value="">Selecciona una hora</option>'; // Clear existing options

        if (data.horarios_disponibles && data.horarios_disponibles.length > 0) {
            data.horarios_disponibles.forEach(hora => {
                const option = document.createElement('option');
                option.value = hora;
                option.textContent = hora;
                horaSelect.appendChild(option);
            });
        }
        horaSelect.value = data.hora;
        // Asegurar que el select de hora esté habilitado para poder cambiarla manualmente
        if (horaSelect) {
            horaSelect.disabled = false;
            horaSelect.removeAttribute('disabled');
        }
        
        const addReservaBtn = document.getElementById("addReservaBtn");
        if(addReservaBtn) {
            addReservaBtn.classList.add("d-none");
        }
        
        // Utiliza el manejador de modales existente para mostrar el modal
        ModalHandler.showReservationModal();
    }
};