<?php
namespace app\controller\admin;

use think\facade\Db;

class User
{
    /**
     * 后台用户列表
     * GET /api/admin/user/list
     */
    public function list()
    {
        $page = input('get.page/d', 1);
        $pageSize = input('get.page_size/d', 20);
        $keyword = input('get.keyword', '');

        $pageSize = min($pageSize, 100);
        $offset = ($page - 1) * $pageSize;

        $where = ['status = 1'];
        $params = [];

        if (!empty($keyword)) {
            $where[] = '(phone LIKE ? OR nickname LIKE ?)';
            $params[] = '%' . $keyword . '%';
            $params[] = '%' . $keyword . '%';
        }

        $whereSql = implode(' AND ', $where);

        // 获取总数
        $total = Db::query(
            "SELECT COUNT(*) as cnt FROM user WHERE {$whereSql}",
            $params
        )[0]['cnt'] ?? 0;

        // 获取列表
        $list = Db::query(
            "SELECT id, phone, nickname, vip_expire, last_login_time, create_time FROM user WHERE {$whereSql} ORDER BY id DESC LIMIT ? OFFSET ?",
            array_merge($params, [$pageSize, $offset])
        );
        
        // 处理激活状态
        foreach ($list as &$user) {
            $hasVipExpire = isset($user['vip_expire']) && !empty($user['vip_expire']);
            $isActivated = $hasVipExpire && strtotime($user['vip_expire']) > time();
            $user['is_activated'] = $isActivated ? 1 : 0;
        }
        unset($user);

        return jsonSuccess([
            'list'        => $list,
            'total'       => $total,
            'page'        => $page,
            'page_size'   => $pageSize,
            'total_pages' => ceil($total / $pageSize)
        ]);
    }
}
