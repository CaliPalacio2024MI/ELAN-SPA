// resources/js/reservation/modules/grupoReservasHandler.js

import { ModalAlerts } from '@/utils/modalAlerts.js';
import { ReservationFormHandler } from './formHandler.js';

/**
 * Rellena los selects de experiencia, cabina y anfitrión para un formulario de acompañante.
 * @param {HTMLElement} wrapper - El contenedor del formulario del acompañante.
 */
function populateSelects(wrapper) {
    const experienciaSelect = wrapper.querySelector("select[name*='experiencia_id']");
    if (experienciaSelect) {
        experienciaSelect.innerHTML = '<option value="" disabled selected>Selecciona experiencia</option>';
        (window.ReservasConfig?.experiencias || []).forEach(exp => {
            const opt = document.createElement("option");
            opt.value = exp.id;
            // Usar el texto con información adicional si está disponible.
            opt.textContent = exp.nombre_con_info || exp.nombre;
            opt.setAttribute("data-duracion", exp.duracion);
            experienciaSelect.appendChild(opt);
        });

        const duracionInput = wrapper.querySelector("input[name='duracion']");
        if (duracionInput) {
            experienciaSelect.addEventListener("change", () => {
                const duracion = experienciaSelect.options[experienciaSelect.selectedIndex]?.getAttribute("data-duracion") || 0;
                duracionInput.value = duracion;
            });
        }
    }

    const cabinaSelect = wrapper.querySelector("select[name*='cabina_id']");
    if (cabinaSelect) {
        cabinaSelect.innerHTML = '<option value="" disabled selected>Selecciona experiencia y fecha</option>';
    }

    const anfitrionSelect = wrapper.querySelector("select[name*='anfitrion_id']");
    if (anfitrionSelect) {
        anfitrionSelect.innerHTML = '<option value="" disabled selected>Selecciona experiencia y fecha</option>';
    }
}

/**
 * Filtra los selects de cabinas y anfitriones compatibles con la experiencia y fecha seleccionadas.
 * @param {HTMLElement} wrapper - El contenedor del formulario del acompañante.
 */
function filtrarCabinasYAnfitriones(wrapper) {
    const experienciaId = wrapper.querySelector("[name*='experiencia_id']")?.value;
    const fecha = wrapper.querySelector("[name*='fecha']")?.value;
    const selectCabina = wrapper.querySelector("select[name*='cabina_id']");
    const selectAnfitrion = wrapper.querySelector("select[name*='anfitrion_id']");

    if (!experienciaId || !fecha || !selectCabina || !selectAnfitrion) return;

    const experiencia = (window.ReservasConfig.experiencias || []).find(e => e.id == experienciaId);
    if (!experiencia) return;

    const nombreRequerido = ReservationFormHandler.normalizeString(experiencia.nombre || '');

    let dia = ReservationFormHandler.diaSemana(fecha);

    // Filtra anfitriones con clase, departamento y horario válido
    const anfitrionesCompatibles = (window.ReservasConfig.anfitriones || []).filter(a => {
        const clasesRaw = (a.operativo?.clases_actividad || a.clases_actividad || []).map(c => typeof c === "string" ? c : (c?.nombre || ''));
        const clases = clasesRaw.map(c => ReservationFormHandler.normalizeString(c));
        const depto = ReservationFormHandler.normalizeString(a.operativo?.departamento || a.departamento || '');
        const horarios = (window.ReservasConfig.horarios?.[a.id]?.[dia]) || [];

        const cumpleClase = clases.includes(nombreRequerido);
        const cumpleHorario = horarios.length > 0;
        const cumpleDepto = depto === "spa";

        return cumpleDepto && cumpleClase && cumpleHorario;
    });

    // Filtra cabinas compatibles con el nombre de la experiencia
    const cabinasCompatibles = (window.ReservasConfig.cabinas || []).filter(c => ReservationFormHandler.cabinaSoportaClase(c, nombreRequerido));

    // Actualiza opciones cabinas y anfitriones
    selectCabina.innerHTML = '<option value="">Selecciona cabina</option>';
    cabinasCompatibles.forEach(c => {
        const opt = document.createElement("option");
        opt.value = c.id;
        opt.textContent = c.nombre;
        selectCabina.appendChild(opt);
    });

    if (selectAnfitrion) {
        selectAnfitrion.innerHTML = '<option value="">Selecciona anfitrión</option>';
        anfitrionesCompatibles.forEach(a => {
            const opt = document.createElement("option");
            opt.value = a.id;
            opt.textContent = a.nombre_usuario + ' ' + (a.apellido_paterno || '');
            selectAnfitrion.appendChild(opt);
        });
    }
}

/**
 * Actualiza las horas disponibles según filtros y bloqueos/reservas existentes para un formulario de acompañante.
 * @param {HTMLElement} wrapper - El contenedor del formulario del acompañante.
 */
async function actualizarHorasDisponibles(wrapper) {
    // 1. Get all necessary data from the current form wrapper
    const experienciaId = wrapper.querySelector("[name*='experiencia_id']")?.value;
    const fecha = wrapper.querySelector("[name*='fecha']")?.value;
    const anfitrionId = wrapper.querySelector("[name*='anfitrion_id']")?.value;
    const cabinaId = wrapper.querySelector("[name*='cabina_id']")?.value;
    const horaSelect = wrapper.querySelector("select[name*='hora']");

    // 2. Validate that all required fields are filled
    if (!fecha || !anfitrionId || !experienciaId || !cabinaId) {
        horaSelect.innerHTML = `<option value="">Completa todos los campos de la reservación</option>`;
        return;
    }

    try {
        // 3. Fetch base available hours from the backend
        const params = new URLSearchParams({ experience_id: experienciaId });
        const url = `/anfitriones/${anfitrionId}/horarios/${fecha}?${params.toString()}`;
        
        const response = await fetch(url);
        if (!response.ok) throw new Error('No se pudieron obtener los horarios.');
        const horariosDesdeBackend = await response.json();

        // 4. Get slots occupied by other reservations in the current form (main + other companions)
        const ocupadosFormulario = ReservationFormHandler.obtenerHorariosOcupadosDelFormulario(wrapper, fecha, anfitrionId, cabinaId);

        // 5. Get the duration of the currently selected experience
        const experiencia = window.ReservasConfig.experiencias.find(e => e.id == experienciaId);
        if (!experiencia) {
            horaSelect.innerHTML = '<option value="">Experiencia no válida</option>';
            return;
        }
        const duracion = parseInt(experiencia.duracion);

        // 6. Filter the backend hours, removing any that conflict with form-occupied slots
        const horariosFiltrados = horariosDesdeBackend.filter(hora => {
            const slotInicio = ReservationFormHandler.toMinutes(hora);
            if (slotInicio === -1) return false; // Skip invalid hour formats

            // Calculate the end time for the proposed slot for both host and cabin
            const slotFinAnfitrion = slotInicio + duracion + 10; // Host needs a 10 min break
            const slotFinCabina = slotInicio + duracion;      // Cabin is free immediately

            // Check for conflict with the host's occupied slots
            const conflictoAnfitrion = ocupadosFormulario.anfitrion.some(([ocupadoInicio, ocupadoFin]) => {
                return slotInicio < ocupadoFin && slotFinAnfitrion > ocupadoInicio;
            });

            if (conflictoAnfitrion) return false;

            // Check for conflict with the cabin's occupied slots
            const conflictoCabina = ocupadosFormulario.cabina.some(([ocupadoInicio, ocupadoFin]) => {
                return slotInicio < ocupadoFin && slotFinCabina > ocupadoInicio;
            });

            if (conflictoCabina) return false;

            return true; // This hour is available
        });

        // 7. Populate the hour select with the final filtered list
        horaSelect.innerHTML = '<option value="">Selecciona una hora</option>';
        if (horariosFiltrados.length > 0) {
            horariosFiltrados.forEach(hora => {
                const option = document.createElement('option');
                option.value = hora;
                option.textContent = hora;
                horaSelect.appendChild(option);
            });
        } else {
             horaSelect.innerHTML = '<option value="">No hay horas disponibles</option>';
        }
    } catch (error) {
        console.error('Error al obtener horarios para el grupo:', error);
        horaSelect.innerHTML = '<option value="">Error al cargar horas</option>';
    }
}

export const GrupoReservasHandler = {
    /**
     * Configura la generación dinámica de formularios para acompañantes.
     */
    init() {
        const generarBtn = document.getElementById("generarReservasBtn");
        const cantidadInput = document.getElementById("cantidadReservas");
        const container = document.getElementById("grupoReservasContainer");
        const template = document.getElementById("reservaExtraTemplate");

        if (!generarBtn || !cantidadInput || !container || !template) return;

        generarBtn.addEventListener("click", () => {
            const cantidad = parseInt(cantidadInput.value);
            if (isNaN(cantidad) || cantidad < 0 || cantidad > 10) {
                ModalAlerts.show("Ingresa una cantidad válida entre 0 y 10.", { type: "warning", autoClose: 4000 });
                return;
            }

            container.innerHTML = ""; // Limpia contenedor antes de agregar

            for (let i = 0; i < cantidad; i++) {
                const clone = template.content.cloneNode(true);
                const html = clone.querySelector(".reserva-extra").innerHTML.replace(/__INDEX__/g, i);
                const wrapper = document.createElement("div");
                wrapper.className = "reserva-extra bg-cliente p-3 my-3 rounded border border-secondary position-relative";
                wrapper.innerHTML = html;

                container.appendChild(wrapper);

                // Copia automáticamente la fecha de la reservación principal a la del acompañante.
                const fechaPrincipal = document.getElementById("fecha_reserva")?.value;
                const fechaAcompananteInput = wrapper.querySelector("[name*='fecha']");
                if (fechaPrincipal && fechaAcompananteInput) {
                    fechaAcompananteInput.value = fechaPrincipal;
                }

                populateSelects(wrapper);

                // Agrega listeners para actualizar cabinas, anfitriones y horas disponibles dinámicamente
                ['experiencia_id', 'fecha'].forEach(campo => {
                    wrapper.querySelector(`[name*='${campo}']`)?.addEventListener('change', () => {
                        filtrarCabinasYAnfitriones(wrapper);
                        actualizarHorasDisponibles(wrapper);
                    });
                });

                ['cabina_id', 'anfitrion_id'].forEach(campo => {
                    wrapper.querySelector(`[name*='${campo}']`)?.addEventListener('change', () => {
                        actualizarHorasDisponibles(wrapper);
                    });
                });

                // Botón para remover formulario de acompañante
                const removeBtn = wrapper.querySelector(".remove-reserva-btn");
                removeBtn?.addEventListener("click", () => wrapper.remove());
            }
        });

        // Evento delegación para buscar clientes en formularios de acompañantes
        document.addEventListener("click", async function (e) {
            const btn = e.target.closest(".buscar-cliente-extra");
            if (!btn) return;

            const wrapper = btn.closest(".reserva-extra");
            if (!wrapper) {
                return;
            }

            const inputCorreo = wrapper.querySelector(".correo-cliente");
            const correo = inputCorreo?.value.trim();

            if (!correo) {
                ModalAlerts.show("Ingresa un correo válido.", { type: "warning", title: "Atención" });
                return;
            }

            try {
                const response = await fetch('/buscar-cliente', {
                    method: 'POST',
                    headers: {
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
                        'Content-Type': 'application/json',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({ correo })
                });

                const data = await response.json();

                if (response.ok && data.success) {
                    wrapper.querySelector(".cliente-id-existente").value = data.cliente.id;
                    wrapper.querySelector("[name*='nombre_cliente']").value = data.cliente.nombre || '';
                    wrapper.querySelector("[name*='apellido_paterno_cliente']").value = data.cliente.apellido_paterno || '';
                    wrapper.querySelector("[name*='apellido_materno_cliente']").value = data.cliente.apellido_materno || '';
                    wrapper.querySelector("[name*='telefono_cliente']").value = data.cliente.telefono || '';
                    wrapper.querySelector("[name*='tipo_visita_cliente']").value = data.cliente.tipo_visita || '';
                    ModalAlerts.show("Cliente encontrado y cargado.", { type: "success", title: "Cliente encontrado" });
                } else {
                    ModalAlerts.show("Cliente no encontrado. Ingresa los datos manualmente.", { type: "info", title: "No encontrado" });
                }

            } catch (error) {
                console.error(error);
                ModalAlerts.show("Ocurrió un error al buscar el cliente.", { type: "error", title: "Error" });
            }
        });
    }
};