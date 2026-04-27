<?php
/**
 * 响应格式化工具类
 */

class Response
{
    /**
     * 成功响应
     */
    public static function success($data = [], $message = '操作成功')
    {
        return self::json(200, $message, $data);
    }

    /**
     * 错误响应
     */
    public static function error($message = '操作失败', $code = 400, $data = [])
    {
        return self::json($code, $message, $data);
    }

    /**
     * 未授权
     */
    public static function unauthorized($message = '未授权，请登录')
    {
        return self::json(401, $message);
    }

    /**
     * 无权限
     */
    public static function forbidden($message = '无权限访问')
    {
        return self::json(403, $message);
    }

    /**
     * 资源不存在
     */
    public static function notFound($message = '资源不存在')
    {
        return self::json(404, $message);
    }

    /**
     * 服务器错误
     */
    public static function serverError($message = '服务器内部错误')
    {
        return self::json(500, $message);
    }

    /**
     * 返回JSON
     */
    private static function json($code, $message, $data)
    {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode([
            'code'    => $code,
            'message' => $message,
            'data'    => $data
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
