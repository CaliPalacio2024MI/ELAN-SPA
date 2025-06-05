import { EventsBinder } from './eventsBinder.js';

export const TableLoader = {
    // Inicializa el observer para detectar cambios en la tabla y reasignar eventos
    init() {
        this.setupObserver();
    },

    observer: null, // Observer para monitorear cambios en la tabla

    // Configura MutationObserver para detectar modificaciones en el DOM de la tabla
    setupObserver() {
        const tabla = document.getElementById("tabla-reservaciones");

        if (!tabla) {
            console.warn("No se encontró la tabla de reservaciones para observar.");
            return;
        }

        this.observer = new MutationObserver(() => {
            console.log("Cambios detectados en tabla. Reasignando eventos...");
            EventsBinder.asignarEventosCeldas();
        });

        this.observer.observe(tabla, { childList: true, subtree: true });
    },

    // Carga la tabla de reservaciones desde la URL proporcionada mediante fetch y actualiza el DOM
    cargarTablaReservaciones(url) {
        if (this.observer) this.observer.disconnect(); // Desconecta observer para evitar loops

        fetch(url, {
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            }
        })
        .then(response => response.text())
        .then(html => {
            const tempDiv = document.createElement("div");
            tempDiv.innerHTML = html;

            const nuevaTabla = tempDiv.querySelector("#tabla-reservaciones");
            const nuevaPaginacion = tempDiv.querySelector(".pagination-controls");

            if (nuevaTabla) {
                document.getElementById("tabla-reservaciones").innerHTML = nuevaTabla.innerHTML;
            }

            if (nuevaPaginacion) {
                document.querySelector(".pagination-controls").innerHTML = nuevaPaginacion.innerHTML;
            }

            EventsBinder.asignarEventosCeldas(); // Reasigna eventos a nuevas celdas
            setTimeout(() => this.setupObserver(), 50); // Reinstala observer después de la actualización
        })
        .catch(error => console.error("Error al cargar la tabla de reservaciones:", error));
    },

    // Recarga la tabla usando la URL actual de la ventana
    reload() {
        this.cargarTablaReservaciones(window.location.href);
    }
};
