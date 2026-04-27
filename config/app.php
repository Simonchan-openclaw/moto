<?php
use app\exception\Handle;

return [
    'default_timezone' => 'Asia/Shanghai',
    'app_debug'        => true,
    'app_trace'        => false,
    'default_lang'     => 'zh-cn',
    'exception_handle' => Handle::class,
    'show_error_msg'   => true,
];
