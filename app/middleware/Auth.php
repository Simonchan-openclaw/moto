<?php
namespace app\middleware;

class Auth
{
    public function handle($request, \Closure $next)
    {
        $token = $request->header('Authorization', '');
        $token = str_replace('Bearer ', '', $token);
        
        if (empty($token)) {
            return json(['code' => 401, 'message' => '未登录或Token已过期']);
        }
        
        $decoded = verifyToken($token);
        if (!$decoded) {
            return json(['code' => 401, 'message' => 'Token无效']);
        }
        
        $request->user_id = $decoded->user_id;
        return $next($request);
    }
}
