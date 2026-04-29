<?php
namespace app\controller\admin;

use think\facade\Db;

class Activation
{
    /**
     * 激活记录列表
     * GET /api/admin/activation/list
     */
    public function list()
    {
        $page = input('get.page/d', 1);
        $pageSize = input('get.page_size/d', 20);
        $coachId = input('get.coach_id/d', 0);
        $status = input('get.status', '');

        $pageSize = min($pageSize, 100);
        $offset = ($page - 1) * $pageSize;

        $where = ['1=1'];
        $params = [];

        if ($coachId > 0) {
            $where[] = 'al.coach_id = ?';
            $params[] = $coachId;
        }

        if ($status !== '') {
            // status: 1=已激活, 其他=全部
        }

        $whereStr = implode(' AND ', $where);

        // 获取总数
        $total = Db::query(
            "SELECT COUNT(*) as cnt FROM activation_log al WHERE {$whereStr}",
            $params
        )[0]['cnt'] ?? 0;

        // 获取列表
        $list = Db::query(
            "SELECT al.*, c.phone as coach_phone, c.real_name as coach_name,
                    u.phone as user_phone, u.nickname as user_name,
                    al.is_self_invited, al.commission
             FROM activation_log al
             LEFT JOIN coach c ON al.coach_id = c.id
             LEFT JOIN user u ON al.user_id = u.id
             WHERE {$whereStr}
             ORDER BY al.id DESC LIMIT ? OFFSET ?",
            array_merge($params, [$pageSize, $offset])
        );

        return jsonSuccess([
            'list'        => $list,
            'total'       => $total,
            'page'        => $page,
            'page_size'   => $pageSize,
            'total_pages' => ceil($total / $pageSize)
        ]);
    }

    /**
     * 统计数据（兼容前端调用）
     */
    public function statistics()
    {
        // 激活总数
        $activationCount = Db::query(
            "SELECT COUNT(*) as cnt FROM activation_log"
        )[0]['cnt'] ?? 0;

        // 今日激活
        $todayActivation = Db::query(
            "SELECT COUNT(*) as cnt FROM activation_log WHERE DATE(create_time) = CURDATE()"
        )[0]['cnt'] ?? 0;

        // 本周激活
        $weekActivation = Db::query(
            "SELECT COUNT(*) as cnt FROM activation_log WHERE YEARWEEK(create_time, 1) = YEARWEEK(CURDATE(), 1)"
        )[0]['cnt'] ?? 0;

        // 激活总额
        $totalAmount = Db::query(
            "SELECT COALESCE(SUM(amount), 0) as total FROM activation_log"
        )[0]['total'] ?? 0;

        // 佣金总额
        $totalCommission = Db::query(
            "SELECT COALESCE(SUM(commission), 0) as total FROM activation_log"
        )[0]['total'] ?? 0;

        // 近7天激活趋势
        $trend = Db::query(
            "SELECT DATE(create_time) as date, COUNT(*) as count 
             FROM activation_log 
             WHERE create_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             GROUP BY DATE(create_time)
             ORDER BY date ASC"
        );

        return jsonSuccess([
            'activation_count' => $activationCount,
            'today_activation' => $todayActivation,
            'week_activation' => $weekActivation,
            'total_amount'    => $totalAmount,
            'total_commission' => $totalCommission,
            'trend'           => $trend
        ]);
    }
}
