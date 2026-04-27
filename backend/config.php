<?php
/**
 * 配置文件
 */

// 数据库配置
$_ENV['DB_HOST'] = getenv('DB_HOST') ?: '127.0.0.1';
$_ENV['DB_PORT'] = getenv('DB_PORT') ?: '3306';
$_ENV['DB_NAME'] = getenv('DB_NAME') ?: 'moto_db';
$_ENV['DB_USER'] = getenv('DB_USER') ?: 'root';
$_ENV['DB_PASS'] = getenv('DB_PASS') ?: '';

// 系统配置
$_ENV['APP_NAME'] = '摩托车笔试题库系统';
$_ENV['APP_VERSION'] = '1.0.0';

// 激活模块配置
$_ENV['ACTIVATION_PRICE'] = 18.00;        // 单次激活价格（元）
$_ENV['MIN_RECHARGE'] = 18.00;           // 最低充值金额（元）
$_ENV['ACTIVATION_EXPIRE_DAYS'] = 30;    // 激活码有效期（天）

// Token配置
$_ENV['TOKEN_EXPIRE_DAYS'] = 30;          // Token过期天数

// 短信配置（TODO: 接入实际短信服务）
$_ENV['SMS_ENABLED'] = false;
$_ENV['SMS_APP_ID'] = '';
$_ENV['SMS_APP_KEY'] = '';
$_ENV['SMS_SIGN_NAME'] = '摩托题库';

// 微信支付配置（TODO: 接入实际微信支付）
$_ENV['WX_APPID'] = '';
$_ENV['WX_MCHID'] = '';
$_ENV['WX_KEY'] = '';
$_ENV['WX_SECRET'] = '';
