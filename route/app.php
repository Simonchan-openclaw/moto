<?php
use think\facade\Route;

// API路由组
Route::group('api', function () {
    // 无需认证
    Route::post('user/send_code', 'api.User/sendCode');
    Route::post('user/login', 'api.User/login');
    Route::post('user/register', 'api.User/register');
    Route::get('vip/status', 'api.Vip/status');
    Route::get('user/getCoachInfo', 'api.User/getCoachInfo');
    
    // 管理后台登录（无需认证）
    Route::post('admin/login', 'admin.Admin/login');
    
    // 需要认证
    Route::group('', function () {
        Route::post('user/info', 'api.User/info');
        Route::put('user/update', 'api.User/update');
        Route::post('user/bind_device', 'api.User/bindDevice');
        Route::get('question/chapters', 'api.Question/chapters');
        Route::get('question/list', 'api.Question/list');
        Route::get('question/detail', 'api.Question/detail');
        Route::post('answer/submit', 'api.Answer/submit');
        Route::get('answer/error_list', 'api.Answer/errorList');
        Route::delete('answer/error_clear', 'api.Answer/errorClear');
        Route::post('collection/toggle', 'api.Collection/toggle');
        Route::get('collection/list', 'api.Collection/list');
        Route::post('exam/generate', 'api.Exam/generate');
        Route::post('exam/submit', 'api.Exam/submit');
        Route::get('exam/record_list', 'api.Exam/recordList');
        Route::post('vip/activate', 'api.Vip/activate');
    })->middleware(\app\middleware\Auth::class);
    
    // 管理后台API（需要认证）
    Route::group('admin', function () {
        Route::post('question/list', 'admin.Question/list');
        Route::post('question/import', 'admin.Question/import');
        Route::post('question/add', 'admin.Question/add');
        Route::post('question/edit', 'admin.Question/edit');
        Route::post('question/delete', 'admin.Question/delete');
        Route::get('user/list', 'admin.User/list');
        Route::get('coach/list', 'admin.Coach/list');
        Route::post('coach/add', 'admin.Coach/add');
        Route::post('coach/recharge', 'admin.Coach/recharge');
        Route::post('coach/delete', 'admin.Coach/delete');
        Route::get('exam/records', 'admin.Exam/records');
        Route::get('stat/summary', 'admin.Stat/summary');
    })->middleware(\app\middleware\Auth::class);
    
    // 教练端API（无需中间件，使用独立认证）
    Route::group('coach', function () {
        Route::post('login', 'coach.Coach/login');
        Route::post('register', 'coach.Coach/register');
        Route::get('check', 'coach.Coach/check');  // 公开接口，无需认证
        Route::get('info', 'coach.Coach/getInfo');
        Route::get('qrcode', 'coach.Coach/qrCode');
        Route::get('invite_list', 'coach.Coach/getInviteList');
        Route::get('balance', 'coach.Coach/getBalance');
        Route::post('recharge', 'coach.Coach/recharge');
        Route::get('recharge_list', 'coach.Coach/rechargeList');
        Route::post('activate', 'coach.Coach/activate');
        Route::get('activation_list', 'coach.Coach/activationList');
        Route::post('refund', 'coach.Coach/refund');
    });
    
    // 学员端API
    Route::group('student', function () {
        Route::post('activate', 'student.Student/activate');
        Route::get('check', 'student.Student/check');
        Route::post('verify_code', 'student.Student/verifyCode');
    });
})->allowCrossDomain();
