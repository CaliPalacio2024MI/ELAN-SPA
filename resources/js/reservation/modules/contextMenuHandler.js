// resources/js/reservation/modules/contextMenuHandler.js

import { ReservationFormHandler } from './formHandler.js';
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
        const anfitrion = target.dataset.anfitrion;
        const claseRaw = target.dataset.clase || "";
        const clasesAnfitrion = claseRaw
            .split(",")
            .map(c => c.trim().toLowerCase())
            .filter(c => c.length > 0); // Evita entradas vacías

        console.log("🟡 Anfitrión ID:", anfitrion);
        console.log("🟡 Clases del anfitrión:", clasesAnfitrion);

        ReservationFormHandler.limpiarFormulario();

        const select = document.getElementById("experiencia_id");
        const todas = window.ReservasConfig.experiencias || [];

        console.log("🟢 Todas las experiencias:", todas);

        select.innerHTML = '<option value="">Selecciona experiencia</option>';

        let totalFiltradas = 0;

        // Filtra experiencias según clases del anfitrión
        todas.forEach(exp => {
            // --- INICIO DEL CAMBIO: Lógica de filtrado dual ---
            const claseExp = (exp.clase || "").toLowerCase().trim();
            const nombreExp = (exp.nombre || "").toLowerCase().trim();

            // Condición 1: La experiencia (subclase) está explícitamente en la lista del anfitrión.
            // Esto funciona para anfitriones con subclases asignadas (ej: 'masaje de codos').
            const esSubclaseDirecta = clasesAnfitrion.includes(nombreExp);

            // Condición 2: La clase principal de la experiencia está en la lista del anfitrión.
            // Esto mantiene la compatibilidad con anfitriones que tienen la clase principal (ej: 'experiencias').
            const perteneceAClasePrincipal = clasesAnfitrion.includes(claseExp);

            if (esSubclaseDirecta || perteneceAClasePrincipal) {
                // --- FIN DEL CAMBIO ---
                const opt = document.createElement("option");
                opt.value = exp.id;
                opt.textContent = `${exp.nombre} - ${exp.duracion} min - $${exp.precio}`;
                opt.dataset.duracion = exp.duracion;
                select.appendChild(opt);
                totalFiltradas++;
            }
        });

        console.log(`✅ Total experiencias filtradas: ${totalFiltradas}`);

        // Auto selecciona si solo hay una opción válida
        if (select.options.length === 2) {
            select.selectedIndex = 1;
            select.dispatchEvent(new Event("change"));
        }

        // Llena campos básicos y muestra modal
        document.getElementById("fecha").value = new Date().toISOString().split("T")[0];
        document.getElementById("hora").value = hora;
        document.getElementById("selected_anfitrion").value = anfitrion;

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
            .then(data => ReservationFormHandler.rellenarFormularioEdicion(data))
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
