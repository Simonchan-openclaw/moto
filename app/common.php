<?php
// 应用公共文件

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

/**
 * 获取运行时目录
 */
function runtime_path()
{
    return dirname(__DIR__) . '/runtime/';
}

/**
 * 获取当前登录用户ID
 */
function getCurrentUserId()
{
    return request()->user_id ?? 0;
}

/**
 * 生成 JWT Token
 */
function generateToken($user_id, $expire = 604800)
{
    $key = env('JWT_SECRET', 'moto_exam_jwt_secret_key_2024');
    $time = time();
    
    $payload = [
        'iss' => 'moto_exam',
        'aud' => 'moto_exam_user',
        'iat' => $time,
        'nbf' => $time,
        'exp' => $time + $expire,
        'user_id' => $user_id,
    ];
    
    return JWT::encode($payload, $key, 'HS256');
}

/**
 * 验证 JWT Token
 */
function verifyToken($token)
{
    $key = env('JWT_SECRET', 'moto_exam_jwt_secret_key_2024');
    
    try {
        $decoded = JWT::decode($token, new Key($key, 'HS256'));
        return $decoded;
    } catch (\Exception $e) {
        return false;
    }
}

/**
 * 返回成功 JSON
 */
function jsonSuccess($data = [], $message = '操作成功')
{
    return json([
        'code' => 200,
        'message' => $message,
        'data' => $data,
    ]);
}

/**
 * 返回错误 JSON
 */
function jsonError($message = '操作失败', $code = 400, $data = [])
{
    return json([
        'code' => $code,
        'message' => $message,
        'data' => $data,
    ]);
}
