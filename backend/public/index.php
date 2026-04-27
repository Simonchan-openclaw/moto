<?php
namespace think;

require __DIR__ . '/../vendor/autoload.php';

// HTTP请求
$http = (new App())->bind('index')->run();
$http->send();
