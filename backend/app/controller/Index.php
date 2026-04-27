<?php
namespace app\controller;

use think\facade\View;

class Index
{
    public function index()
    {
        return json(['msg' => 'moto exam api running', 'version' => '1.0.0']);
    }
    
    public function health()
    {
        return json(['status' => 'ok', 'time' => date('Y-m-d H:i:s')]);
    }
}
