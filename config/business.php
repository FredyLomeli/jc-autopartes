<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Datos Oficiales de Marca — JC Autopartes
    |--------------------------------------------------------------------------
    | Fuente: docs/brief_diseno.md
    */

    'name' => 'JC Autopartes',
    'tagline' => 'Refacciones y Autopartes Originales Usadas con Garantía Escrita',
    'address' => 'Blvd. Lic. Anacleto González Flores Sur #2090, C.P. 47600, Tepatitlán de Morelos, Jal.',
    'city' => 'Tepatitlán de Morelos, Jalisco',
    'postal_code' => '47600',

    'whatsapp_display' => '33 3166 5290',
    'whatsapp_number' => '523331665290',
    'phone_display' => '378 688 2205',
    'phone_link' => '+523786882205',
    'email' => 'jcautopartes.mx@gmail.com',

    'hours' => [
        'weekdays' => 'Lunes a Viernes: 9:00 AM – 6:30 PM',
        'saturday' => 'Sábados: 9:00 AM – 2:30 PM',
        'sunday' => 'Domingos: Cerrado',
    ],

    'warranties' => [
        [
            'days' => 30,
            'title' => 'Motores y Transmisiones',
            'description' => 'Garantía funcional escrita de 30 días naturales en motores 3/4, motores completos y cajas de transmisión.',
        ],
        [
            'days' => 15,
            'title' => 'Partes Internas de Motor',
            'description' => '15 días naturales de respaldo en cabezas de motor, cigüeñales, árboles de levas y componentes internos.',
        ],
        [
            'days' => 7,
            'title' => 'Eléctrico, Mecánica y Carrocería',
            'description' => '7 días naturales en alternadores, marchas, suspensión, colisión, puertas, cofres, faros y calaveras.',
        ],
    ],
];
