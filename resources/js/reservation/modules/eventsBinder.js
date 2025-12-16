// resources/js/reservation/modules/eventsBinder.js

import { TableLoader } from './tableLoader.js';
import { Alerts } from '@/utils/alerts.js';

let celdaSeleccionada = null;

export const EventsBinder = {
    // Inicializa eventos principales
    init() {
        this.asignarEventosCeldas();
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
            const checkIn = celda.getAttribute("data-check-in") || "0";

            document.getElementById("editarOpcion")?.setAttribute("data-reserva-id", reservaId);
            document.getElementById("eliminarOpcion")?.setAttribute("data-reserva-id", reservaId);
            document.getElementById("checkinOpcion")?.setAttribute("data-reserva-id", reservaId);
            document.getElementById("checkinOpcion")?.setAttribute("data-check-in", checkIn);
            document.getElementById('checkoutOpcion')?.setAttribute('data-reserva-id', reservaId);

            this.mostrarMenuContextual("contextMenuReserved", event);
        });
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
