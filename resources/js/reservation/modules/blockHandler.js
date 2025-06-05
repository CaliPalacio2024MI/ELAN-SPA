// resources/js/reservation/modules/blockHandler.js

import { ModalAlerts } from '@/utils/modalAlerts.js';

document.addEventListener("DOMContentLoaded", () => {
    const form = document.getElementById("bloqueoForm");
    if (!form) return;

    // Maneja envío del formulario de bloqueo de horarios
    form.addEventListener("submit", async function (e) {
        e.preventDefault();

        const motivo = document.getElementById("motivo_bloqueo").value.trim();
        const duracion = parseInt(document.getElementById("duracion_bloqueo").value);
        const hora = document.getElementById("bloqueo_hora").value;
        const anfitrion_id = document.getElementById("bloqueo_anfitrion_id").value;

        // Validación básica de duración
        if (!duracion || isNaN(duracion) || duracion < 5) {
            ModalAlerts.show("La duración mínima es de 5 minutos", {
                title: "Duración inválida",
                type: "warning",
                autoClose: 4000
            });
            return;
        }

        const boton = form.querySelector('button[type="submit"]');
        boton.disabled = true;

        try {
            // Envía solicitud POST para crear bloqueo
            const res = await fetch('/blocked-slots', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    fecha: document.getElementById("filtro_fecha").value,
                    hora,
                    anfitrion_id,
                    motivo,
                    duracion
                })
            });

            const data = await res.json();

            if (!res.ok) throw data;

            ModalAlerts.show(data.message, {
                title: "Listo",
                type: "success",
                autoClose: 4000
            });

            // Cierra modal y recarga la página para reflejar cambios
            const modal = bootstrap.Modal.getInstance(document.getElementById("bloqueoModal"));
            modal.hide();
            window.location.reload();

        } catch (err) {
            const msg = err.errors
                ? Object.values(err.errors).flat().join("\n")
                : "Error al guardar el bloqueo.";

            ModalAlerts.show(msg, {
                title: "Error",
                type: "error"
            });
        } finally {
            boton.disabled = false;
        }
    });
});
