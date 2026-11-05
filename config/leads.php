<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Notificaciones comerciales
    |--------------------------------------------------------------------------
    |
    | Aviso por correo al equipo comercial cuando llega un prospecto o una
    | solicitud. Desactivado por defecto: no se envía nada hasta contar con
    | credenciales SMTP (MAIL_*) y autorización explícita.
    |
    | Destinatarios: la configuración "Destinatarios de notificaciones" del
    | panel (privada) y, si está vacía, COMMERCIAL_NOTIFICATION_EMAILS.
    |
    */

    'notifications' => [
        'enabled' => (bool) env('LEAD_NOTIFICATIONS_ENABLED', false),
        'recipients' => env('COMMERCIAL_NOTIFICATION_EMAILS', ''),
    ],

];
