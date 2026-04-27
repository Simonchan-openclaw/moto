<?php
/**
 * 入口文件
 */

// 定义应用目录
define('APP_PATH', __DIR__ . '/../app/');

// 定义根目录
define('ROOT_PATH', __DIR__ . '/../');

// 定义运行时目录
define('RUNTIME_PATH', __DIR__ . '/../runtime/');

// 加载配置
require_once __DIR__ . '/../config.php';

// 加载 Db 类
require_once __DIR__ . '/../library/Db.php';

// 设置时区
date_default_timezone_set('Asia/Shanghai');

// 设置错误报告
error_reporting(E_ALL);
ini_set('display_errors', 1);

// 记录日志
function log_message($level, $message) {
    $logFile = RUNTIME_PATH . 'logs/' . date('Y-m-d') . '.log';
    $dir = dirname($logFile);
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[{$timestamp}] {$level}: {$message}\n", FILE_APPEND);
}

// 加载路由
require_once __DIR__ . '/../router.php';
