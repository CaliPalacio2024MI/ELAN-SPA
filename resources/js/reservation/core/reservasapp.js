// resources/js/reservation/core/reservasapp.js

// Importa módulos principales para gestión de reservaciones
import { ModalHandler } from '../modules/modalHandler.js';
import { ContextMenuHandler } from '../modules/contextMenuHandler.js';
import { ReservationFormHandler } from '../modules/formHandler.js';
import { TableLoader } from '../modules/tableLoader.js';
import { EventsBinder } from '../modules/eventsBinder.js';
import '../modules/blockHandler.js'; // Importa módulo para bloqueos, se ejecuta automáticamente

export const ReservasApp = {
    // Inicializa todos los módulos necesarios
    init() {
        ModalHandler.init();
        ContextMenuHandler.init();
        ReservationFormHandler.init();
        TableLoader.init();
        EventsBinder.init();
    }
};
