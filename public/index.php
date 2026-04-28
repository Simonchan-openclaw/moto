<?php
/**
 * 入口文件 - ThinkPHP8
 */

// 定义应用目录
define('APP_PATH', __DIR__ . '/../app/');

// 加载 Composer 自动加载
require __DIR__ . '/../vendor/autoload.php';

// 创建应用实例
$app = new \think\App();

// 绑定默认类
$app->bind('index');

// 执行应用
$http = $app->http;

$response = $http->run();

$response->send();

$http->end($response);
