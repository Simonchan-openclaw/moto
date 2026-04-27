<?php
return [
    'type'           => 'File',
    'path'           => '../runtime/session/',
    'prefix'         => '',
    'auto_start'     => true,
    'httponly'       => true,
    'secure'         => false,
    'same_site'      => 'Lax',
    'expire'         => 86400,
    'cookie_name'    => 'MOTO_SESSION',
    'session_name'   => 'PHPSESSID',
    'serialize'      => [],
];
