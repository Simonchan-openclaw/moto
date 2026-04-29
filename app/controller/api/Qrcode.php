<?php
namespace app\controller;

use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use think\Request;
use think\Response;

class Qrcode
{
    /**
     * 生成二维码（直接输出图片）
     */
    public function index(Request $request)
    {
        // 1. 获取前端 POST 传来的 url
        $url = $request->post('url');

        // 2. 校验不能为空
        if (empty($url)) {
            return json(['code' => 1, 'msg' => 'url 不能为空']);
        }
      
        // 3. 生成二维码
        $qrCode = new QrCode($url);
        $qrCode->setSize(300);  // 二维码大小
        $qrCode->setMargin(10); // 边距

        // 4. 输出图片给前端
        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return Response::create($result->getString(), 'image/png');
    }
  
    /**
     * 生成二维码（直接输出base64）
     */
    public function base64(Request $request)
    {
        // 1. 获取前端 POST 传来的 url
        $url = $request->post('url');
                
        // 2. 校验不能为空
        if (empty($url)) {
            return json(['code' => 1, 'msg' => 'url 不能为空']);
        }

        // 3. 生成二维码
        $qrCode = new QrCode($url);
        $qrCode->setSize(300);  // 二维码大小
        $qrCode->setMargin(10); // 边距

        // 4. 输出图片给前端
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        
        return json([
            'code' => 0,
            'qrcode' => 'data:image/png;base64,' . base64_encode($result->getString())
        ]);
    }
}
