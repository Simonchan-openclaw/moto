<?php
/**
 * 用户控制器
 */

namespace Controller;

require_once __DIR__ . '/../model/UserModel.php';
require_once __DIR__ . '/../library/Response.php';

class UserController
{
    private $model;

    public function __construct()
    {
        $this->model = new \UserModel();
    }

    /**
     * 验证用户登录
     */
    private function authUser()
    {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (empty($token)) {
            Response::unauthorized('请先登录');
        }

        // 简化验证：Token格式为 "Bearer {user_id}"
        if (strpos($token, 'Bearer ') === 0) {
            $userId = intval(str_replace('Bearer ', '', $token));
            if ($userId <= 0) {
                Response::unauthorized('无效的登录状态');
            }
            return $userId;
        }

        Response::unauthorized('无效的登录状态');
    }

    /**
     * 发送验证码
     * POST /api/user/send_code
     */
    public function sendCode()
    {
        $phone = $_POST['phone'] ?? '';
        $type = $_POST['type'] ?? 'login';

        if (empty($phone)) {
            Response::error('手机号不能为空');
        }

        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            Response::error('手机号格式不正确');
        }

        // TODO: 实际应接入短信服务
        // 模拟：生成6位验证码
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // 存储验证码（实际应存入缓存或数据库）
        file_put_contents(RUNTIME_PATH . 'codes/' . md5($phone) . '.txt', $code);

        Response::success([
            'success' => true,
            'message' => '验证码发送成功',
            'code'    => $code  // 测试环境返回，生产环境不应返回
        ], '验证码发送成功');
    }

    /**
     * 用户登录/注册
     * POST /api/user/login
     */
    public function login()
    {
        $phone = $_POST['phone'] ?? '';
        $code = $_POST['code'] ?? '';

        if (empty($phone) || empty($code)) {
            Response::error('手机号和验证码不能为空');
        }

        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            Response::error('手机号格式不正确');
        }

        // 验证验证码（简化版）
        $cacheFile = RUNTIME_PATH . 'codes/' . md5($phone) . '.txt';
        if (file_exists($cacheFile)) {
            $cachedCode = file_get_contents($cacheFile);
            if ($cachedCode !== $code && $code !== '123456') { // 123456为万能测试码
                Response::error('验证码错误');
            }
        } elseif ($code !== '123456') {
            Response::error('验证码错误或已过期');
        }

        // 查找或创建用户
        $user = $this->model->findByPhone($phone);

        if (!$user) {
            // 自动注册
            $userId = $this->model->register($phone, '', '摩托学员');
            $user = $this->model->findById($userId);
        }

        // 生成Token
        $token = bin2hex(random_bytes(32));

        Response::success([
            'token'    => $token,
            'coach_id' => $user['id'], // 兼容旧格式
            'userInfo' => [
                'id'          => $user['id'],
                'phone'       => $user['phone'],
                'nickname'    => $user['nickname'],
                'avatar'      => $user['avatar'],
                'create_time' => $user['create_time']
            ]
        ], '登录成功');
    }

    /**
     * 获取用户信息
     * POST /api/user/info
     */
    public function getInfo()
    {
        $userId = $this->authUser();

        $user = $this->model->findById($userId);

        if (!$user) {
            Response::error('用户不存在');
        }

        Response::success([
            'id'          => $user['id'],
            'phone'       => substr($user['phone'], 0, 3) . '****' . substr($user['phone'], -4),
            'nickname'    => $user['nickname'],
            'avatar'      => $user['avatar'],
            'create_time' => $user['create_time']
        ]);
    }

    /**
     * 更新用户信息
     * PUT /api/user/update
     */
    public function update()
    {
        $userId = $this->authUser();

        $data = json_decode(file_get_contents('php://input'), true);

        if (empty($data)) {
            Response::error('没有要更新的数据');
        }

        // 过滤允许更新的字段
        $allowedFields = ['nickname', 'avatar'];
        $updateData = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field])) {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            Response::error('没有有效的更新字段');
        }

        $this->model->update($userId, $updateData);

        Response::success(['success' => true], '修改成功');
    }
}
