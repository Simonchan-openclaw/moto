<?php
namespace app\controller\admin;

use think\facade\Db;

class Admin
{
    /**
     * 管理员登录
     * POST /api/admin/login
     */
    public function login()
    {
        $username = input('post.username', '');
        $password = input('post.password', '');

        if (empty($username) || empty($password)) {
            return jsonError('用户名和密码不能为空');
        }

        // 查询管理员
        $admin = Db::query(
            "SELECT * FROM admin WHERE username = ?",
            [$username]
        );

        // 账号不存在
        if (empty($admin)) {
            return jsonError('账号不存在');
        }

        $admin = $admin[0];

        // 账号被禁用
        if ($admin['status'] != 1) {
            return jsonError('账号已被禁用');
        }

        // MD5密码验证（兼容SQL文件中的密码存储格式）
        if (md5($password) !== $admin['password']) {
            return jsonError('密码错误');
        }

        // 生成Token
        $token = createToken($admin['id'], ['type' => 'admin']);

        // 更新登录信息
        Db::execute(
            "UPDATE admin SET login_count = login_count + 1, last_login_time = NOW(), last_login_ip = ? WHERE id = ?",
            [request()->ip(), $admin['id']]
        );

        return jsonSuccess([
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
