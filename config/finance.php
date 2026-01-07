<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Configuración Financiera y de Cálculo
    |--------------------------------------------------------------------------
    |
    | Este archivo es para almacenar variables relacionadas con finanzas y cálculos
    | que se utilizan en toda la aplicación. Centralizarlas aquí
    | facilita su gestión y actualización.
    |
    */

    'tax_rates' => [
        // Tipo impositivo estándar del Impuesto sobre el Valor Añadido (IVA) para servicios.
        'iva' => 0.16,

        // Tasa de cargo por servicio.
        'service_charge' => 0.20,

        // Tasa del Impuesto al Valor Agregado (IVA) aplicable a las propinas.
        'tip_iva' => 0.20,
    ],

    'reservations' => [
        // Tiempo de descanso en minutos para los terapeutas después de un servicio.
        'therapist_break_time' => 10,
    ],

];