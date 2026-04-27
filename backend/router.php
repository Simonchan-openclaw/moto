<?php
/**
 * API 路由配置
 */

// 加载基础类
require_once __DIR__ . '/../library/Response.php';
require_once __DIR__ . '/../library/Db.php';

// 加载所有模型
require_once __DIR__ . '/../model/UserModel.php';
require_once __DIR__ . '/../model/CoachModel.php';
require_once __DIR__ . '/../model/RechargeRecordModel.php';
require_once __DIR__ . '/../model/StudentActivationModel.php';
require_once __DIR__ . '/../model/QuestionModel.php';
require_once __DIR__ . '/../model/ChapterModel.php';
require_once __DIR__ . '/../model/UserAnswerModel.php';
require_once __DIR__ . '/../model/ErrorQuestionModel.php';
require_once __DIR__ . '/../model/CollectionModel.php';
require_once __DIR__ . '/../model/ExamRecordModel.php';

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
    // ==================== 教练端激活模块 ====================
    'coach/login'            => ['CoachActivation', 'login'],
    'coach/register'         => ['CoachActivation', 'register'],
    'coach/balance'          => ['CoachActivation', 'getBalance'],
    'coach/recharge'         => ['CoachActivation', 'recharge'],
    'coach/recharge_list'    => ['CoachActivation', 'rechargeList'],
    'coach/activate'         => ['CoachActivation', 'activate'],
    'coach/activation_list'  => ['CoachActivation', 'activationList'],
    'coach/refund'           => ['CoachActivation', 'refund'],

    // ==================== 学员端激活模块 ====================
    'student/activate'       => ['StudentActivation', 'activate'],
    'student/check'          => ['StudentActivation', 'check'],
    'student/verify_code'    => ['StudentActivation', 'verifyCode'],

    // ==================== 用户模块 ====================
    'user/send_code'         => ['User', 'sendCode'],
    'user/login'              => ['User', 'login'],
    'user/info'               => ['User', 'getInfo'],
    'user/update'             => ['User', 'update'],

    // ==================== 题目模块 ====================
    'question/chapters'      => ['Question', 'getChapters'],
    'question/list'          => ['Question', 'getList'],
    'question/detail'        => ['Question', 'getDetail'],

    // ==================== 答题模块 ====================
    'answer/submit'           => ['Answer', 'submit'],
    'answer/error_list'      => ['Answer', 'errorList'],
    'answer/error_clear'     => ['Answer', 'errorClear'],

    // ==================== 收藏模块 ====================
    'collection/toggle'       => ['Collection', 'toggle'],
    'collection/list'         => ['Collection', 'getList'],

    // ==================== 考试模块 ====================
    'exam/generate'           => ['Exam', 'generate'],
    'exam/submit'             => ['Exam', 'submit'],
    'exam/record_list'        => ['Exam', 'recordList'],

    // ==================== 管理后台 ====================
    'admin/login'             => ['Admin', 'login'],
    'admin/question/list'     => ['AdminQuestion', 'list'],
    'admin/question/import'   => ['AdminQuestion', 'import'],
];

// 查找路由
if (isset($routes[$route])) {
    list($controllerName, $action) = $routes[$route];

    // 加载控制器
    $controllerFile = __DIR__ . '/../controller/' . $controllerName . 'Controller.php';

    if (!file_exists($controllerFile)) {
        Response::serverError('控制器文件不存在: ' . $controllerFile);
    }

    require_once $controllerFile;

    $controllerClass = 'Controller\\' . $controllerName . 'Controller';

    if (!class_exists($controllerClass)) {
        Response::serverError('控制器类不存在: ' . $controllerClass);
    }

    $controller = new $controllerClass();

    if (!method_exists($controller, $action)) {
        Response::serverError('方法不存在: ' . $action);
    }

    // 调用方法
    try {
        $controller->$action();
    } catch (Exception $e) {
        log_message('ERROR', $e->getMessage());
        Response::serverError($e->getMessage());
    }

} else {
    Response::notFound('接口不存在: ' . $route);
}
