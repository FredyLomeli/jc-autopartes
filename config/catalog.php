<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Categorías Oficiales del Catálogo (Fuente Única de Verdad)
    |--------------------------------------------------------------------------
    */
    'categories' => [
        'Carrocería y Colisión' => [
            'name' => 'Carrocería y Colisión',
            'warranty_days' => 7,
            'warranty' => 'Garantía por escrito',
            'desc' => 'Salpicaderas, defensas, parrillas, cofres, puertas, espejos, marcos de radiador y molduras.',
            'placeholder' => 'imagenes/placeholders/colision.svg',
        ],
        'Iluminación' => [
            'name' => 'Iluminación',
            'warranty_days' => 7,
            'warranty' => 'Garantía por escrito',
            'desc' => 'Faros delanteros, calaveras, cuartos, direccionales, faros de niebla y luces interiores.',
            'placeholder' => 'imagenes/placeholders/colision.svg',
        ],
        'Interior y Seguridad' => [
            'name' => 'Interior y Seguridad',
            'warranty_days' => 7,
            'warranty' => 'Garantía por escrito',
            'desc' => 'Cinturones de seguridad, bolsas de aire, estéreos, viseras, manijas, pedales y asientos.',
            'placeholder' => 'imagenes/placeholders/colision.svg',
        ],
        'Suspensión y Dirección' => [
            'name' => 'Suspensión y Dirección',
            'warranty_days' => 7,
            'warranty' => 'Garantía por escrito',
            'desc' => 'Amortiguadores, horquillas, cremalleras, barras estabilizadoras, resortes y columnas.',
            'placeholder' => 'imagenes/placeholders/suspension.svg',
        ],
        'Frenos' => [
            'name' => 'Frenos',
            'warranty_days' => 7,
            'warranty' => 'Garantía por escrito',
            'desc' => 'Calipers, sensores ABS, discos, bombas de freno y boosters.',
            'placeholder' => 'imagenes/placeholders/suspension.svg',
        ],
        'Eléctrico y Sensores' => [
            'name' => 'Eléctrico y Sensores',
            'warranty_days' => 7,
            'warranty' => 'Garantía por escrito',
            'desc' => 'Bobinas de encendido, alternadores, marchas, computadoras, switches y sensores.',
            'placeholder' => 'imagenes/placeholders/electrico.svg',
        ],
        'Enfriamiento y Clima (A/C)' => [
            'name' => 'Enfriamiento y Clima (A/C)',
            'warranty_days' => 7,
            'warranty' => 'Garantía por escrito',
            'desc' => 'Radiadores, motoventiladores, mangueras, depósitos anticongelante, compresores y clima.',
            'placeholder' => 'imagenes/placeholders/motor.svg',
        ],
        'Motores' => [
            'name' => 'Motores',
            'warranty_days' => 30,
            'warranty' => 'Garantía por escrito',
            'desc' => 'Motores completos y 3/4 verificados en compresión y funcionamiento.',
            'placeholder' => 'imagenes/placeholders/motor.svg',
        ],
        'Transmisiones' => [
            'name' => 'Transmisiones',
            'warranty_days' => 30,
            'warranty' => 'Garantía por escrito',
            'desc' => 'Cajas de velocidades automáticas, CVT, estándar, palancas y flechas.',
            'placeholder' => 'imagenes/placeholders/transmision.svg',
        ],
        'Partes de Motor e Inyección' => [
            'name' => 'Partes de Motor e Inyección',
            'warranty_days' => 15,
            'warranty' => 'Garantía por escrito',
            'desc' => 'Cabezas, cigüeñales, cuerpos de aceleración, rieles, inyectores, bombas y soportes.',
            'placeholder' => 'imagenes/placeholders/motor.svg',
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Alias de Compatibilidad para Rutas/Filtros Anteriores
    |--------------------------------------------------------------------------
    */
    'category_aliases' => [
        'Colision' => 'Carrocería y Colisión',
        'Colisión y Carrocería' => 'Carrocería y Colisión',
        'Suspension' => 'Suspensión y Dirección',
        'Electrico' => 'Eléctrico y Sensores',
        'Enfriamiento' => 'Enfriamiento y Clima (A/C)',
        'Partes Internas' => 'Partes de Motor e Inyección',
    ],
];
