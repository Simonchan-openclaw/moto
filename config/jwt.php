<?php
return [
    'secret' => env('JWT_SECRET', 'moto_exam_jwt_secret_key_2024'),
    'expire' => env('JWT_EXPIRE', 86400 * 7), // 7天
];
