// resources/js/reservation/modules/eventsBinder.js

import { TableLoader } from './tableLoader.js';
import { Alerts } from '@/utils/alerts.js';
import { ModalHandler } from './modalHandler.js';
import { ReservationFormHandler } from './formHandler.js';

let celdaSeleccionada = null;
let draggingReserva = null;
let isDropping = false;

// Comprueba si un anfitrión está calificado para una experiencia
function anfitrionCalificado(anfitrionId, experienciaId) {
    try {
        const experiencias = window.ReservasConfig?.experiencias || [];
        const anfitriones = window.ReservasConfig?.anfitriones || [];

        const exp = experiencias.find(e => String(e.id) === String(experienciaId));
        const claseExp = (exp?.clase || '').toString();
        const nombreExp = (exp?.nombre || '').toString();

        const anfitrion = anfitriones.find(a => String(a.id) === String(anfitrionId));
        if (!anfitrion) return false;

        const clases = Array.isArray(anfitrion.operativo?.clases_actividad)
            ? anfitrion.operativo.clases_actividad
            : (Array.isArray(anfitrion.clases_actividad) ? anfitrion.clases_actividad : []);

        const normalize = s => String(s || '').normalize('NFD').replace(/[\u0300-\u036f]/g, '').toLowerCase().trim();

        const claseNorm = normalize(claseExp);
        const nombreNorm = normalize(nombreExp);

        for (const c of clases) {
            const cNorm = normalize(c);
            if (cNorm === claseNorm || cNorm === nombreNorm) return true;
        }

        return false;
    } catch (e) {
        return false;
    }
}

export const EventsBinder = {
    // Inicializa eventos principales
    init() {
        this.asignarEventosCeldas();
        this.asignarEventoReservarOpcion();
    },

    asignarEventoReservarOpcion() {
        const reservarOpcion = document.getElementById("reservarOpcion");
        reservarOpcion?.addEventListener("click", async (event) => {
            event.preventDefault();
            const menu = document.getElementById("contextMenu");
            if (menu) menu.style.display = "none";
    
            const anfitrionId = reservarOpcion.dataset.anfitrion;
            const hora = reservarOpcion.dataset.hora;
            const fecha = document.getElementById("filtro_fecha").value;
    
            ReservationFormHandler.limpiarFormulario();
    
            // Rellenar y mostrar el formulario para una nueva reservación
            const fechaInput = document.getElementById("fecha_reserva");
            const horaInput = document.getElementById("hora");
            const anfitrionInput = document.getElementById("selected_anfitrion");

            if (fechaInput) {
                fechaInput.value = fecha;
                const fechaWrapper = fechaInput.closest('#fecha-wrapper');
                if(fechaWrapper) fechaWrapper.style.display = 'block';
                fechaInput.readOnly = true; 
            }
            if (horaInput) {
                horaInput.innerHTML = `<option value="${hora}" selected>${hora}</option>`; // Añade como opción y la selecciona
                const horaWrapper = horaInput.closest('#hora-wrapper');
                if(horaWrapper) horaWrapper.style.display = 'block';
                // Permitir que el usuario cambie la hora manualmente al crear desde la celda
                horaInput.disabled = false;
                horaInput.removeAttribute('disabled');
            }
            if (anfitrionInput) {
                anfitrionInput.value = anfitrionId;
            }
            // Poblar horas disponibles para el anfitrión y fecha seleccionada
            try {
                const horaSelect = document.getElementById("hora");
                if (anfitrionId && fecha && horaSelect) {
                    const url = `/anfitriones/${anfitrionId}/horarios/${fecha}`;
                    const resp = await fetch(url);
                    if (resp.ok) {
                        const horarios = await resp.json();
                        horaSelect.innerHTML = '<option value="">Selecciona una hora</option>';
                        if (horarios && horarios.length > 0) {
                            horarios.forEach(h => {
                                const option = document.createElement('option');
                                option.value = h;
                                option.textContent = h;
                                horaSelect.appendChild(option);
                            });
                        }
                        // Si la hora de la celda no está en la lista, añadirla como opción
                        if (hora) {
                            const exists = Array.from(horaSelect.options).some(o => o.value === hora);
                            if (!exists) {
                                const opt = document.createElement('option');
                                opt.value = hora;
                                opt.textContent = hora;
                                opt.setAttribute('data-original', 'true');
                                horaSelect.appendChild(opt);
                            }
                            horaSelect.value = hora;
                        }
                        horaSelect.disabled = false;
                        horaSelect.removeAttribute('disabled');
                    } else {
                        // Fallback: dejar la hora única proveniente de la celda
                        horaInput.innerHTML = `<option value="${hora}" selected>${hora}</option>`;
                        horaInput.disabled = false;
                        horaInput.removeAttribute('disabled');
                    }
                }
            } catch (e) {
                console.error('Error al obtener horarios del anfitrión:', e);
            }
    
            // Filtra las experiencias para el anfitrión seleccionado
            const form = document.getElementById('reservationForm');
            ReservationFormHandler.filtrarExperienciasPorAnfitrion(anfitrionId, form);
    
            ModalHandler.showReservationModal();
        });
    },

        // Asigna eventos para clicks y menú contextual en celdas

        asignarEventosCeldas() {

            const tabla = document.getElementById("tabla-reservaciones");

            const modal = document.getElementById("reservationDetailsModal");

            const modalContent = document.getElementById("reservationDetails");

    

            // Evento menú contextual para celdas disponibles (.available)

            tabla?.addEventListener("contextmenu", (event) => {

                const celda = event.target.closest(".available");

                if (!celda) return;
                event.preventDefault();

    

                celdaSeleccionada = celda;

    

                const hora = celda.getAttribute("data-hora");

                const anfitrion = celda.getAttribute("data-anfitrion");

    

                // --- INICIO DEL CAMBIO: Lógica simplificada para obtener especialidades ---

                // Obtenemos la lista de especialidades (clases y/o subclases) directamente del anfitrión.

                let especialidadesAnfitrion = [];

                const anfitrionInfo = window.ReservasConfig.anfitriones?.find(a => a.id == anfitrion);

                if (anfitrionInfo) {

                    especialidadesAnfitrion = (anfitrionInfo.operativo?.clases_actividad || anfitrionInfo.clases_actividad || []);

                }

                const clase = especialidadesAnfitrion.join(',');

                // --- FIN DEL CAMBIO ---

    

                console.log("📥 Clase(s) encontrada(s):", clase);

    

                // Actualiza atributos data para opciones del menú contextual

                const reservar = document.getElementById("reservarOpcion");

                if (reservar) {

                    reservar.dataset.hora = hora;

                    reservar.dataset.anfitrion = anfitrion;

                    reservar.dataset.clase = clase;

                }

    

                const bloquear = document.getElementById("bloquearOpcion");

                if (bloquear) {

                    bloquear.dataset.hora = hora;

                    bloquear.dataset.anfitrion = anfitrion;

                }

    

                this.mostrarMenuContextual("contextMenu", event);

            });

    

            // Click para mostrar detalles en celdas ocupadas o bloqueadas

            tabla?.addEventListener("click", (event) => {

                const celdaReserva = event.target.closest(".occupied");

                const celdaBloqueo = event.target.closest(".bloqueada");

                if (celdaReserva) return this.mostrarDetalleReservacion(celdaReserva);

                if (celdaBloqueo) return this.mostrarDetalleBloqueo(celdaBloqueo);

            });

    

            // Menú contextual para celdas ocupadas (.occupied)

            tabla?.addEventListener("contextmenu", (event) => {

                const celda = event.target.closest(".occupied");

                if (!celda) return;

                event.preventDefault();

    

                const reservaId = celda.getAttribute("data-reserva-id");

                const checkIn = celda.getAttribute("data-check-in"); // "1" o "0"

                const checkOut = celda.getAttribute("data-check-out"); // "1" o "0"

    

                document.getElementById("editarOpcion")?.setAttribute("data-reserva-id", reservaId);

                document.getElementById("eliminarOpcion")?.setAttribute("data-reserva-id", reservaId);

    

                const checkinOpcion = document.getElementById("checkinOpcion");

                if (checkinOpcion) {

                    checkinOpcion.setAttribute("data-reserva-id", reservaId);

                    // Ocultar si ya se hizo check-in

                    if (checkIn === '1') {

                        checkinOpcion.style.display = 'none';

                    }

                    else {

                        checkinOpcion.style.display = 'block';

                    }

                }

    

                const checkoutOpcion = document.getElementById("checkoutOpcion");

                if (checkoutOpcion) {

                    checkoutOpcion.setAttribute('data-reserva-id', reservaId);

                    // Mostrar solo si se hizo check-in y no check-out

                    if (checkIn === '1' && checkOut !== '1') {

                        checkoutOpcion.style.display = 'block';

                    }

                    else {

                        checkoutOpcion.style.display = 'none';

                    }

                }

                this.mostrarMenuContextual("contextMenuReserved", event);

            });

    

            // --- INICIO DRAG & DROP ---

            // Evento para iniciar el arrastre de una reserva

            tabla?.addEventListener('dragstart', (event) => {

                const celda = event.target.closest('.occupied');

                if (!celda) return;

    

                const checkIn = celda.getAttribute('data-check-in');

                if (checkIn === '1') {

                    Alerts.error('No se puede mover una reservación con check-in.');

                    event.preventDefault();

                    return;

                }

    

                const reservaId = celda.getAttribute('data-reserva-id');
                console.log('dragstart -> reservaId:', reservaId, 'anfitrion origen:', celda.getAttribute('data-anfitrion'));

                // Guardar información mínima de la reserva que se está arrastrando
                try {
                    const reservas = window.ReservasConfig?.reservaciones || [];
                    const reserv = reservas.find(r => String(r.id) === String(reservaId));
                    draggingReserva = reserv ? { id: reservaId, experiencia_id: reserv.experiencia_id || (reserv.experiencia && reserv.experiencia.id) } : { id: reservaId };
                } catch (e) {
                    draggingReserva = { id: reservaId };
                }

                event.dataTransfer.setData('text/plain', reservaId);

                event.dataTransfer.effectAllowed = 'move';

            });

    

            // Evento para permitir soltar sobre una celda disponible (valida calificación)
            tabla?.addEventListener('dragover', (event) => {
                const celda = event.target.closest('.available');
                if (!celda) return;

                const destinoAnfitrion = celda.getAttribute('data-anfitrion');

                // Si hay una reserva en arrastre, validar si el anfitrión destino está calificado
                if (draggingReserva && draggingReserva.experiencia_id) {
                    const calificado = anfitrionCalificado(destinoAnfitrion, draggingReserva.experiencia_id);
                    if (!calificado) {
                        // No permitir drop y marcar visualmente
                        celda.classList.add('drag-forbidden');
                        celda.classList.remove('drag-over');
                        return;
                    }
                    celda.classList.remove('drag-forbidden');
                }

                // Permitimos el drop
                event.preventDefault();
                celda.classList.add('drag-over'); // Estilo visual opcional
                console.log('dragover -> celda disponible detectada:', destinoAnfitrion, celda.getAttribute('data-hora'));
            });

    

            // Evento para quitar el estilo visual al salir de la zona de drop

            tabla?.addEventListener('dragleave', (event) => {

                const celda = event.target.closest('.available');

                if (celda) {

                    celda.classList.remove('drag-over');
                    celda.classList.remove('drag-forbidden');

                }

            });

    

            // Evento para manejar la acción de soltar

            tabla?.addEventListener('drop', async (event) => {

                if (isDropping) return;

                const celdaDestino = event.target.closest('.available');

                if (!celdaDestino) return;

            

                event.preventDefault();

                celdaDestino.classList.remove('drag-over');

            

                isDropping = true;

                const reservaId = event.dataTransfer.getData('text/plain');

                const nuevoAnfitrionId = celdaDestino.getAttribute('data-anfitrion');

                const nuevaHora = celdaDestino.getAttribute('data-hora');

                const fecha = document.getElementById('filtro_fecha').value;

            

                const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content');

            

                const data = {

                    anfitrion_id: nuevoAnfitrionId,

                    hora: nuevaHora,

                    fecha: fecha,

                    from_drag: true,

                };

            

                try {

                    const response = await fetch(`/reservations/${reservaId}`, {

                        method: 'PUT',

                        headers: {

                            'Content-Type': 'application/json',

                            'X-CSRF-TOKEN': csrfToken,

                            'Accept': 'application/json',

                        },

                        body: JSON.stringify(data),

                    });

            

                    const result = await response.json();

            

                                    if (response.ok) {

            

                                        Alerts.success('¡Reservación movida!', result.message);

            

                                        TableLoader.reload(); // Recargar la tabla

            

                                    } else {

                        const errorMessage = result.error || 'Ocurrió un error desconocido.';

                        Alerts.error('Error al mover', errorMessage);

                    }

                } catch (error) {

                    console.error('Error en la petición de drop:', error);

                    Alerts.error('Error de Conexión', 'No se pudo comunicar con el servidor.');

                } finally {
                    setTimeout(() => {
                        isDropping = false;
                    }, 1000);
                }

            // Limpiar estado cuando termina el arrastre
            tabla?.addEventListener('dragend', (event) => {
                draggingReserva = null;
                document.querySelectorAll('.reserva-celda').forEach(c => {
                    c.classList.remove('drag-over');
                    c.classList.remove('drag-forbidden');
                });
            });

            });

            // --- FIN DRAG & DROP ---

        },

    // Posiciona y muestra menú contextual sin que se desborde
    mostrarMenuContextual(id, event) {
        const menu = document.getElementById(id);
        if (!menu) return;

        const { clientX, clientY } = event;
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;
        const menuRect = menu.getBoundingClientRect();

        let left = clientX + window.scrollX;
        let top = clientY + window.scrollY;

        // Evita desbordamiento horizontal
        if (left + menuRect.width > viewportWidth) {
            left = viewportWidth - menuRect.width - 10;
        }
        // Evita desbordamiento vertical
        if (top + menuRect.height > viewportHeight) {
            top = viewportHeight - menuRect.height - 10;
        }

        menu.style.display = "block";
        menu.style.top = `${top}px`;
        menu.style.left = `${left}px`;
    },

    // Muestra modal con detalles de reservación
    mostrarDetalleReservacion(celda) {
        const id = celda.getAttribute("data-reserva-id");
        if (!id) return;

        fetch(`/reservations/${id}`)
            .then(res => res.json())
            .then(data => {
                const modal = document.getElementById("reservationDetailsModal");
                const modalContent = document.getElementById("reservationDetails");
                modalContent.innerHTML = `
                    <p><strong>Cliente:</strong> ${data.cliente}</p>
                    <p><strong>Anfitrión:</strong> ${data.anfitrion}</p>
                    <p><strong>Experiencia:</strong> ${data.experiencia}</p>
                    <p><strong>Fecha:</strong> ${data.fecha}</p>
                    <p><strong>Hora:</strong> ${data.hora}</p>
                    <p><strong>Cabina:</strong> ${data.cabina}</p>
                    <p><strong>Acompañante:</strong> ${data.acompanante ? "Sí" : "No"}</p>
                    <p><strong>Observaciones:</strong> ${data.observaciones || "Ninguna"}</p>
                `;
                this.mostrarModalEnPosicion(celda, modal);
            })
            .catch(error => Alerts.error("No se pudieron cargar los detalles"));
    },

    // Muestra modal con detalles de bloqueo
    mostrarDetalleBloqueo(celda) {
        const hora = celda.getAttribute("data-hora");
        const anfitrionId = celda.getAttribute("data-anfitrion");
        const bloqueo = window.ReservasConfig?.bloqueos?.find(b => b.hora?.substring(0, 5) === hora && String(b.anfitrion_id) === String(anfitrionId));

        if (bloqueo) {
            const modal = document.getElementById("reservationDetailsModal");
            const modalContent = document.getElementById("reservationDetails");
            modalContent.innerHTML = `
                <p><strong>Motivo:</strong> ${bloqueo.motivo || 'No especificado'}</p>
                <p><strong>Duración:</strong> ${bloqueo.duracion} min</p>
                <p><strong>Hora:</strong> ${bloqueo.hora}</p>
            `;
            this.mostrarModalEnPosicion(celda, modal);
        }
    },

    // Posiciona y muestra modal sin desbordar
    mostrarModalEnPosicion(celda, modal) {
        const rect = celda.getBoundingClientRect();
        const viewportWidth = window.innerWidth;
        const viewportHeight = window.innerHeight;

        requestAnimationFrame(() => {
            let top = rect.top + window.scrollY + 30;
            let left = rect.left + window.scrollX + 30;

            if (left + modal.offsetWidth > viewportWidth) {
                left = viewportWidth - modal.offsetWidth - 10;
            }

            if (top + modal.offsetHeight > viewportHeight) {
                top = viewportHeight - modal.offsetHeight - 10;
            }

            if (top < 10) top = 10;

            modal.style.top = `${top}px`;
            modal.style.left = `${left}px`;
            modal.style.display = "block";
            modal.classList.add("show");
        });
    }
};
