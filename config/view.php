<?php
return [
    'default' => 'html',
    'tpl'     => [
        'type'         => 'Think',
        'view_path'    => '',
        'view_suffix'  => 'html',
        'view_depr'    => DIRECTORY_SEPARATOR,
        'tpl_begin'    => '{',
        'tpl_end'      => '}',
        'tag_begin'    => '<',
        'tag_end'      => '>',
        'tag_load'     => 'load',
        'strip_space'  => false,
        'default_filter' => 'htmlspecialchars',
    ],
];
