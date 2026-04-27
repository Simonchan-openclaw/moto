<?php
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

// 生成JWT Token
function createToken($userId, $extra = []) {
    $key = config('jwt.secret');
    $time = time();
    $payload = array_merge([
        'iss' => 'moto_exam',
        'aud' => 'moto_exam_api',
        'iat' => $time,
        'exp' => $time + config('jwt.expire'),
        'user_id' => $userId,
    ], $extra);
    return JWT::encode($payload, $key, 'HS256');
}

// 验证JWT Token
function verifyToken($token) {
    try {
        $key = config('jwt.secret');
        return JWT::decode($token, new Key($key, 'HS256'));
    } catch (\Exception $e) {
        return false;
    }
}

// 获取当前用户ID
function getCurrentUserId() {
    $request = request();
    $token = $request->header('Authorization', '');
    $token = str_replace('Bearer ', '', $token);
    if (empty($token)) return 0;
    $decoded = verifyToken($token);
    return $decoded ? ($decoded->user_id ?? 0) : 0;
}

// JSON响应
function jsonSuccess($data = [], $message = 'success') {
    return json(['code' => 200, 'message' => $message, 'data' => $data]);
}

function jsonError($message = 'error', $code = 400) {
    return json(['code' => $code, 'message' => $message, 'data' => null]);
}

// 运行时路径
defined('RUNTIME_PATH') or define('RUNTIME_PATH', __DIR__ . '/../runtime/');
