<?php
return [
    'default'     => 'mysql',
    'connections' => [
        'mysql' => [
            'type'            => 'mysql',
            'hostname'        => env('DB_HOST', '127.0.0.1'),
            'database'        => env('DB_DATABASE', 'moto_db'),
            'username'        => env('DB_USERNAME', 'root'),
            'password'        => env('DB_PASSWORD', ''),
            'hostport'        => env('DB_PORT', '3306'),
            'charset'         => 'utf8mb4',
            'prefix'          => '',
            'deploy'          => 0,
            'rw_separate'     => false,
            'break_reconnect' => false,
            'fields_cache'    => false,
        ]
    ],
];
