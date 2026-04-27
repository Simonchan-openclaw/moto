<?php
return [
    'type'       => 'Mysql',
    'host'       => env('REDIS_HOST', '127.0.0.1'),
    'port'       => env('REDIS_PORT', 6379),
    'password'   => env('REDIS_PASSWORD', ''),
    'select'     => 0,
    'timeout'    => 0,
    'expire'     => 0,
    'persistent' => false,
    'prefix'     => '',
];
