<?php
// config/settings.php

/* 
    ACTIVACION MINIFICACION/OFUSCACION DE HTML Y CSS/JS
    true = Activado, false = Desactivado 
*/
// config/settings.php
return [
    // Interruptor de minificación (No olvides la coma al final)
    'minify_html' => true,

    // Configuración SMTP
    'smtp' => [
        'host' => 'mail.obradoiroweb.com',
        'username' => 'galiedu@obradoiroweb.com',
        'password' => '$gali20A10b96C*11d01',
        'secure' => 'ssl',
        'port' => 465,
        'from_email' => 'galieduobradoiroweb.com',
        'from_name' => 'EduGame. Servicio de Soporte'
    ]
];
?>