<?php
/**
 * 管理员控制器
 */

namespace Controller;

require_once __DIR__ . '/../library/Db.php';
require_once __DIR__ . '/../library/Response.php';

class AdminController
{
    private $db;

    public function __construct()
    {
        $this->db = \Db::getInstance();
    }

    /**
     * 验证管理员登录
     */
    private function authAdmin()
    {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (strpos($token, 'Bearer ') === 0) {
            $adminId = intval(str_replace('Bearer ', '', $token));
            if ($adminId > 0) {
                return $adminId;
            }
        }

        Response::unauthorized('请先登录');
    }

    /**
     * 管理员登录
     * POST /api/admin/login
     */
    public function login()
    {
        $username = $_POST['username'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($username) || empty($password)) {
            Response::error('用户名和密码不能为空');
        }

        // MD5加密密码
        $passwordHash = md5($password);

        $admin = $this->db->fetch(
            "SELECT * FROM admin WHERE username = ? AND password = ? AND status = 1",
            [$username, $passwordHash]
        );

        if (!$admin) {
            Response::error('用户名或密码错误');
        }

        // 生成Token
        $token = bin2hex(random_bytes(32));

        // 更新登录信息
        $this->db->execute(
            "UPDATE admin SET login_count = login_count + 1, last_login_time = NOW(), last_login_ip = ? WHERE id = ?",
            [$_SERVER['REMOTE_ADDR'] ?? '', $admin['id']]
        );

        Response::success([
            'token'     => $token,
            'adminInfo' => [
                'id'         => $admin['id'],
                'username'   => $admin['username'],
                'nickname'   => $admin['real_name'],
                'role'       => $admin['role_id'] == 1 ? 'admin' : 'editor',
                'create_time'=> $admin['create_time']
            ]
        ], '登录成功');
    }
}
