<?php
namespace app\controller\admin;

use think\facade\Db;

class Coach
{
    /**
     * 教练列表
     * GET /api/admin/coach/list
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
            $where[] = '(phone LIKE ? OR real_name LIKE ?)';
            $params[] = '%' . $keyword . '%';
            $params[] = '%' . $keyword . '%';
        }

        $whereStr = implode(' AND ', $where);

        // 获取列表
        $list = Db::query(
            "SELECT id, phone, real_name, balance, total_recharged, status, last_login_time, create_time 
             FROM coach WHERE {$whereStr} ORDER BY id DESC LIMIT ? OFFSET ?",
            array_merge($params, [$pageSize, $offset])
        );

        // 获取总数
        $total = Db::query(
            "SELECT COUNT(*) as cnt FROM coach WHERE {$whereStr}",
            $params
        )[0]['cnt'] ?? 0;

        // 获取每个教练的激活次数（从activation_log表）
        foreach ($list as &$item) {
            $activationCount = Db::query(
                "SELECT COUNT(*) as cnt FROM activation_log WHERE coach_id = ?",
                [$item['id']]
            )[0]['cnt'] ?? 0;
            $item['activation_count'] = $activationCount;
            
            // 获取累计佣金
            $commission = Db::query(
                "SELECT COALESCE(SUM(commission), 0) as total FROM activation_log WHERE coach_id = ?",
                [$item['id']]
            )[0]['total'] ?? 0;
            $item['total_commission'] = $commission;
            
            // 脱敏手机号
            $item['phone_mask'] = substr($item['phone'], 0, 3) . '****' . substr($item['phone'], -4);
        }

        return jsonSuccess([
            'list'       => $list,
            'total'      => $total,
            'page'       => $page,
            'page_size'  => $pageSize,
            'total_page' => ceil($total / $pageSize)
        ]);
    }

    /**
     * 添加教练
     * POST /api/admin/coach/add
     */
    public function add()
    {
        $phone = input('post.phone', '');
        $password = input('post.password', '');
        $realName = input('post.real_name', '');

        if (empty($phone)) {
            return jsonError('手机号不能为空');
        }

        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return jsonError('手机号格式不正确');
        }

        if (empty($password) || strlen($password) < 6) {
            return jsonError('密码至少6位');
        }

        // 检查手机号是否已存在
        $exists = Db::query("SELECT id FROM coach WHERE phone = ? AND status = 1", [$phone]);
        if ($exists) {
            return jsonError('该手机号已注册');
        }

        // 创建教练
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        Db::execute(
            "INSERT INTO coach (phone, password, real_name, balance, status, create_time) VALUES (?, ?, ?, 0.00, 1, NOW())",
            [$phone, $passwordHash, $realName]
        );

        $coachId = Db::query("SELECT LAST_INSERT_ID() as id")[0]['id'];

        return jsonSuccess(['coach_id' => $coachId], '教练添加成功');
    }

    /**
     * 教练充值
     * POST /api/admin/coach/recharge
     */
    public function recharge()
    {
        $coachId = input('post.coach_id/d', 0);
        $amount = floatval(input('post.amount', 0));

        if ($coachId <= 0) {
            return jsonError('教练ID不能为空');
        }

        if ($amount <= 0) {
            return jsonError('充值金额必须大于0');
        }

        // 检查教练是否存在
        $coach = Db::query("SELECT id, balance FROM coach WHERE id = ? AND status = 1", [$coachId]);
        if (!$coach) {
            return jsonError('教练不存在');
        }

        // 创建充值记录
        $tradeNo = 'ADMIN' . date('YmdHis') . rand(1000, 9999);
        Db::execute(
            "INSERT INTO recharge_record (coach_id, amount, pay_method, trade_no, status, create_time) VALUES (?, ?, 3, ?, 1, NOW())",
            [$coachId, $amount, $tradeNo]
        );

        // 更新教练余额
        Db::execute(
            "UPDATE coach SET balance = balance + ?, total_recharged = total_recharged + ? WHERE id = ?",
            [$amount, $amount, $coachId]
        );

        // 获取最新余额
        $newBalance = Db::query("SELECT balance FROM coach WHERE id = ?", [$coachId])[0]['balance'] ?? 0;

        return jsonSuccess([
            'trade_no' => $tradeNo,
            'amount'   => $amount,
            'balance'  => $newBalance
        ], '充值成功');
    }

    /**
     * 删除教练
     * POST /api/admin/coach/delete
     */
    public function delete()
    {
        $coachId = input('post.coach_id/d', 0);

        if ($coachId <= 0) {
            return jsonError('教练ID不能为空');
        }

        // 软删除
        Db::execute("UPDATE coach SET status = 0 WHERE id = ?", [$coachId]);

        return jsonSuccess(['coach_id' => $coachId], '删除成功');
    }
}
