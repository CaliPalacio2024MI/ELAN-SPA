import { ModalAlerts } from './modalAlerts.js';

export const Alerts = {
    // Muestra alerta de éxito con mensaje y tiempo de cierre automático
    success(message = "Operación exitosa", timer = 1500) {
        ModalAlerts.show(message, {
            title: "Éxito",
            type: "success",
            autoClose: timer
        });
    },

    // Muestra alerta de error con mensaje y título personalizado, sin cierre automático
    error(message = "Ocurrió un error inesperado", title = "Error") {
        ModalAlerts.show(message, {
            title,
            type: "error",
            autoClose: false 
        });
    },

    // Muestra alerta informativa con mensaje y título, con cierre automático
    info(message = "Información importante", title = "Información") {
        ModalAlerts.show(message, {
            title,
            type: "info",
            autoClose: true
        });
    },

    // Muestra alerta de advertencia sin cierre automático
    warning(message = "Advertencia", title = "Atención") {
        ModalAlerts.show(message, {
            title,
            type: "warning",
            autoClose: false 
        });
    },

    // Muestra confirmación de eliminación con botones y callback para confirmar
    confirmDelete(callback, {
        title = "¿Estás seguro?",
        text = "Esta acción no se puede deshacer.",
        confirmText = "Sí, eliminar",
        cancelText = "Cancelar"
    } = {}) {
        ModalAlerts.show(text, {
            title,
            type: "error",
            autoClose: false,
            confirmButton: {
                label: confirmText,
                action: callback
            }
        });
    },

    // Muestra errores de validación como lista de mensajes HTML sin cierre automático
    validationErrors(errores = [], title = "Errores en el formulario") {
        const html = errores.map(e => `<p>${e}</p>`).join("");
        ModalAlerts.show(html, {
            title,
            type: "error",
            autoClose: false
        });
    },

    // Muestra contenido HTML arbitrario como aviso informativo con cierre automático
    rawHtml(htmlContent = "", title = "Aviso") {
        ModalAlerts.show(htmlContent, {
            title,
            type: "info",
            autoClose: true
        });
    }
};
