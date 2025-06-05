export const ModalHandler = {
    // Inicializa el modal y configura el evento para cerrar el modal de detalles
    init() {
        this.setupCerrarModal();
        this.reservationModal = new bootstrap.Modal(document.getElementById("reservationModal"), {
            keyboard: false
        });
    },

    // Muestra el modal principal de reservación
    showReservationModal() {
        if (this.reservationModal) {
            this.reservationModal.show();
        }
    },

    // Oculta el modal principal de reservación
    hideMain() {
        if (this.reservationModal) {
            this.reservationModal.hide();
        }
    },

    // Configura el cierre del modal de detalles mediante botón o clic fuera del modal
    setupCerrarModal() {
        const modal = document.getElementById("reservationDetailsModal");
        const closeBtn = document.querySelector("#reservationDetailsModal .close-btn");

        if (closeBtn) {
            closeBtn.addEventListener("click", () => {
                modal.classList.remove("show");
                modal.style.display = "none";
            });
        }

        // Cierra el modal si se hace clic fuera de él y no sobre una celda ocupada
        document.addEventListener("click", (event) => {
            if (
                modal.classList.contains("show") &&
                !modal.contains(event.target) &&
                !event.target.closest(".occupied")
            ) {
                modal.classList.remove("show");
                modal.style.display = "none";
            }
        });
    }
};
