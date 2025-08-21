<?php
// config/truck-tracking.php

return [
    'boltrack' => [
        'base_url' => env('BOLTRACK_API_URL', 'https://gestiondeflota.boltrack.net/integracionapi'),
        'token' => env('BOLTRACK_API_TOKEN', 'bltrck2021_454fd3d'),
        'timeout' => env('BOLTRACK_API_TIMEOUT', 30),
    ],

    'alerts' => [
        'critical_hours' => env('TRUCK_TRACKING_CRITICAL_HOURS', 48), // 2 días
        'warning_hours' => env('TRUCK_TRACKING_WARNING_HOURS', 8),    // 8 horas
        'normal_hours' => env('TRUCK_TRACKING_NORMAL_HOURS', 4),      // 4 horas
    ],

    'processing' => [
        'parallel_requests' => env('TRUCK_TRACKING_PARALLEL', true),
        'max_workers' => env('TRUCK_TRACKING_MAX_WORKERS', 5),
        'generate_excel' => env('TRUCK_TRACKING_GENERATE_EXCEL', true),
    ],

    'geocercas' => [
        'hierarchy' => ['DOCKS', 'TRACK AND TRACE', 'CBN', 'CIUDADES'],
        'cache_ttl' => env('TRUCK_TRACKING_CACHE_TTL', 3600), // 1 hora
    ],

    'schedule' => [
        'enabled' => env('TRUCK_TRACKING_SCHEDULE_ENABLED', true),
        'interval' => env('TRUCK_TRACKING_SCHEDULE_INTERVAL', '*/30'), // Cada 30 minutos
    ],

    'reports' => [
        'storage_path' => 'truck-tracking/reports',
        'retention_days' => env('TRUCK_TRACKING_REPORT_RETENTION', 30),
    ],

    'deposito_mappings' => [
        'Cerveceria SCZ' => [
            'ciudad' => 'SANTA CRUZ',
            'cbn' => 'PLANTA SANTA CRUZ',
            'track_trace' => 'TYT - PLANTA SANTA CRUZ',
            'docks' => 'DOCK - 7 - PLANTA SANTA CRUZ'
        ],
        'Cerveceria LPZ' => [
            'ciudad' => 'LA PAZ',
            'cbn' => 'PLANTA LA PAZ',
            'track_trace' => 'TYT - PLANTA LA PAZ',
            'docks' => 'DOCK - 3 - PLANTA LA PAZ'
        ],
        'Cerveceria CBBA' => [
            'ciudad' => 'COCHABAMBA',
            'cbn' => 'PLANTA COCHABAMBA',
            'track_trace' => 'TYT - PLANTA COCHABAMBA',
            'docks' => 'DOCK - 5 - PLANTA COCHABAMBA'
        ]
    ],
];
