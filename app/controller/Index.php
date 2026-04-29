<?php
namespace app\controller;

use think\facade\View;

class Index
{
    public function index()
    {
        return redirect('/h5/');
    }
    
    public function health()
    {
        return json(['status' => 'ok', 'time' => date('Y-m-d H:i:s')]);
    }
}
