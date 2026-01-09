// resources/js/reservation/modules/contextMenuHandler.js

import { ReservationFormHandler } from './formHandler.js';
import { EditReservationHandler } from './editReservationHandler.js';
import { ModalHandler } from './modalHandler.js';
import { TableLoader } from './tableLoader.js';
import { ModalAlerts } from '@/utils/modalAlerts.js';

export const ContextMenuHandler = {
    // Inicializa listeners para menús contextuales
    init() {
        this.setupCerrarMenus();
        this.setupBotonesContextuales();
    },

    // Cierra menús contextuales al hacer clic fuera
    setupCerrarMenus() {
        document.addEventListener("click", function (event) {
            const contextMenus = ["contextMenu", "contextMenuReserved"];
            contextMenus.forEach(id => {
                const menu = document.getElementById(id);
                if (menu?.style.display === "block" && !event.target.closest(`#${id}`)) {
                    menu.style.display = "none";
                }
            });
        });
    },

    // Configura acciones para cada opción del menú contextual
    setupBotonesContextuales() {
        const reservarBtn = document.getElementById("reservarOpcion");
        const bloquearBtn = document.getElementById("bloquearOpcion");
        const eliminarBtn = document.getElementById("eliminarOpcion");
        const editarBtn = document.getElementById("editarOpcion");
        const checkinBtn = document.getElementById("checkinOpcion");
        const checkoutBtn = document.getElementById("checkoutOpcion");

        reservarBtn?.addEventListener("click", (event) => this.abrirFormularioReservacion(event));
        bloquearBtn?.addEventListener("click", () => this.abrirModalBloqueo());
        eliminarBtn?.addEventListener("click", () => this.eliminarReservacion());
        editarBtn?.addEventListener("click", () => this.editarReservacion());
        checkinBtn?.addEventListener("click", () => this.irACheckin());
        checkoutBtn?.addEventListener("click", () => this.irACheckout());
    },

    // Abre formulario para nueva reservación con datos desde la celda
    abrirFormularioReservacion(event) {
        event.preventDefault();
        const target = event.target;
 
        const hora = target.dataset.hora;
        const anfitrionId = target.dataset.anfitrion;
 
        ReservationFormHandler.limpiarFormulario();
 
        // Centralizamos el filtrado de experiencias en formHandler
        const formPrincipal = document.getElementById('reservationForm');
        ReservationFormHandler.filtrarExperienciasPorAnfitrion(anfitrionId, formPrincipal, false);
 
        const select = document.getElementById("experiencia_id");
 
        // Auto selecciona si solo hay una opción válida
        if (select.options.length === 2) {
            select.selectedIndex = 1;
            select.dispatchEvent(new Event("change"));
        }
 
        // Llena campos básicos y muestra modal
        // Usar la fecha del filtro principal de la página, no la fecha actual.
        const fechaFiltro = document.getElementById("filtro_fecha")?.value;
        document.getElementById("fecha_reserva").value = fechaFiltro || new Date().toISOString().split("T")[0];
        document.getElementById("hora").value = hora;
        document.getElementById("selected_anfitrion").value = anfitrionId;
 
        document.getElementById("modalTitle").textContent = "Nueva Reservación";
        document.getElementById("saveButton").textContent = "Guardar Reservación";
        document.getElementById("reserva_id").value = "";

        ModalHandler.showReservationModal();
        document.getElementById("contextMenu").style.display = "none";
    },

    // Muestra modal para bloqueo de horario
    abrirModalBloqueo() {
        const hora = event.target.getAttribute("data-hora");
        const anfitrion = event.target.getAttribute("data-anfitrion");

        document.getElementById("bloqueo_hora").value = hora;
        document.getElementById("bloqueo_anfitrion_id").value = anfitrion;
        document.getElementById("motivo_bloqueo").value = "";
        document.getElementById("duracion_bloqueo").value = 30;

        new bootstrap.Modal(document.getElementById("bloqueoModal")).show();
    },

    // Elimina reservación con confirmación
    eliminarReservacion() {
        const reservaId = document.getElementById("eliminarOpcion").getAttribute("data-reserva-id");
        if (!reservaId) return;

        document.getElementById("contextMenuReserved").style.display = "none";

        ModalAlerts.show("¿Estás seguro de que deseas cancelar esta reservación?", {
            title: "Confirmar cancelación",
            type: "error",
            confirmButton: {
                label: "Sí, cancelar",
                action: async () => {
                    try {
                        const res = await fetch(`/reservations/${reservaId}`, {
                            method: "DELETE",
                            headers: {
                                "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                                "X-Requested-With": "XMLHttpRequest"
                            }
                        });

                        if (!res.ok) throw new Error("Error al eliminar.");

                        await res.json();
                        ModalAlerts.show("Reservación eliminada correctamente.", {
                            title: "Eliminada",
                            type: "success",
                            autoClose: 3000
                        });
                        TableLoader.reload();
                    } catch (e) {
                        ModalAlerts.show("No se pudo eliminar la reservación.", {
                            title: "Error",
                            type: "error"
                        });
                    }
                }
            }
        });
    },

    // Edita reservación: obtiene datos y rellena formulario
    editarReservacion() {
        const reservaId = document.getElementById("editarOpcion").getAttribute("data-reserva-id");
        if (!reservaId) return;

        document.getElementById("contextMenuReserved").style.display = "none";

        fetch(`/reservations/${reservaId}/edit`)
            .then(res => {
                if (!res.ok) throw new Error("Error al obtener reservación.");
                return res.json();
            })
            .then(data => EditReservationHandler.rellenarFormularioEdicion(data))
            .catch(() => ModalAlerts.show("No se pudieron cargar los datos de la reservación.", {
                title: "Error",
                type: "error"
            }));
    },

    // Redirige a página de check-in de la reservación
    irACheckin() {
        const reservaId = document.getElementById("checkinOpcion").getAttribute("data-reserva-id");
        if (!reservaId) return;

        document.getElementById("contextMenuReserved").style.display = "none";
        window.location.href = `/reservations/${reservaId}/checkin`;
    },

    // Redirige a página de check-out de la reservación
    irACheckout() {
        const reservaId = document.getElementById("checkoutOpcion").getAttribute("data-reserva-id");
        if (!reservaId) return;

        document.getElementById("contextMenuReserved").style.display = "none";
        window.location.href = `/reservations/${reservaId}/checkout`;
    }
};
