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
            "SELECT * FROM admin WHERE username = ? AND status = 1",
            [$username]
        );

        if (empty($admin)) {
            return jsonError('用户名或密码错误');
        }

        $admin = $admin[0];

        // bcrypt密码验证
        if (!password_verify($password, $admin['password'])) {
            return jsonError('用户名或密码错误');
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
