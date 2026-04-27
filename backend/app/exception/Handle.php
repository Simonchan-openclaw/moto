<?php
namespace app\exception;

use Exception;
use think\exception\Handle as ThinkHandle;

class Handle extends ThinkHandle
{
    protected $ignoreReport = [];

    public function render($request, \Throwable $e)
    {
        // API请求返回JSON
        if (request()->isApi()) {
            $code = $e->getCode() ?: 500;
            $message = $e->getMessage() ?: '服务器内部错误';
            
            // 生产环境隐藏详细错误
            if (!config('app.app_debug')) {
                $message = $code == 500 ? '服务器内部错误' : $message;
            }
            
            return json(['code' => $code, 'message' => $message, 'data' => null], $code);
        }
        
        return parent::render($request, $e);
    }
}
