// Importa el módulo principal de la aplicación de reservaciones
import { ReservasApp } from './reservation/core/reservasapp.js'; 

// Importa utilidades para mostrar alertas modales y mensajes
import { ModalAlerts } from '@/utils/modalAlerts.js';
import { Alerts } from '@/utils/alerts.js';

// Expone las utilidades de alertas al ámbito global para acceso en otros scripts
window.ModalAlerts = ModalAlerts;
window.Alerts = Alerts;

// Inicializa la aplicación de reservaciones cuando el DOM esté completamente cargado
document.addEventListener("DOMContentLoaded", () => {
    ReservasApp.init();
});
