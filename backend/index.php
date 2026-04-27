<?php
/**
 * API 路由入口
 * 
 * 摩托车笔试题库系统 - 教练激活模块 API
 */

// 加载基础类
require_once __DIR__ . '/library/Response.php';
require_once __DIR__ . '/library/Db.php';

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 获取请求信息
$method = $_SERVER['REQUEST_METHOD'];
$uri = $_SERVER['REQUEST_URI'];
$path = parse_url($uri, PHP_URL_PATH);

// 移除前缀斜杠
$path = trim($path, '/');

// API基础路径
$basePath = 'api/';

// 检查是否是API请求
if (strpos($path, $basePath) !== 0) {
    Response::notFound('API路径不正确');
}

// 移除基础路径
$route = substr($path, strlen($basePath));

// 路由映射
$routes = [
    // 教练端激活模块
    'coach/login'           => ['CoachActivationController', 'login'],
    'coach/register'        => ['CoachActivationController', 'register'],
    'coach/balance'         => ['CoachActivationController', 'getBalance'],
    'coach/recharge'        => ['CoachActivationController', 'recharge'],
    'coach/recharge_list'   => ['CoachActivationController', 'rechargeList'],
    'coach/activate'        => ['CoachActivationController', 'activate'],
    'coach/activation_list' => ['CoachActivationController', 'activationList'],
    'coach/refund'          => ['CoachActivationController', 'refund'],

    // 学员端激活模块
    'student/activate'     => ['StudentActivationController', 'activate'],
    'student/check'        => ['StudentActivationController', 'check'],
    'student/verify_code' => ['StudentActivationController', 'verifyCode'],
];

// 查找路由
if (isset($routes[$route])) {
    list($controllerName, $action) = $routes[$route];

    // 加载控制器
    $controllerFile = __DIR__ . '/controller/' . $controllerName . '.php';

    if (!file_exists($controllerFile)) {
        Response::serverError('控制器文件不存在');
    }

    require_once $controllerFile;

    $controller = new $controllerName();

    if (!method_exists($controller, $action)) {
        Response::serverError('方法不存在');
    }

    // 调用方法
    $controller->$action();

} else {
    Response::notFound('接口不存在: ' . $route);
}
