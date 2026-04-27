<?php
return [
    'default' => 'file',
    'stores'  => [
        'file' => [
            'type'       => 'File',
            'path'       => '../runtime/cache/',
            'expire'     => 0,
            'tag_prefix' => 'tag:',
            'serialize'  => [],
        ],
    ],
];
