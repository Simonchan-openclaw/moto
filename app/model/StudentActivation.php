<?php
namespace app\model;

use think\facade\Db;

class StudentActivation
{
    protected $table = 'student_activation';

    /**
     * 检查用户激活状态
     */
    public function checkUserActivation($userId, $deviceId)
    {
        // 直接从user表检查vip_expire
        $user = Db::query("SELECT id, vip_expire FROM user WHERE id = ?", [$userId]);
        
        if (!$user || !isset($user[0])) {
            return ['is_activated' => false, 'expire_at' => null];
        }
        
        $user = $user[0];
        $vipExpire = $user['vip_expire'] ?? null;
        
        if (!$vipExpire) {
            return ['is_activated' => false, 'expire_at' => null];
        }
        
        $isActivated = strtotime($vipExpire) > time();
        
        return [
            'is_activated' => $isActivated,
            'expire_at'     => $isActivated ? $vipExpire : null
        ];
    }

    /**
     * 激活（绑定设备）
     */
    public function activate($activateCode, $deviceId, $userId)
    {
        // 查询激活码
        $record = Db::query(
            "SELECT sa.*, c.phone as coach_phone FROM {$this->table} sa 
             LEFT JOIN coach c ON sa.coach_id = c.id 
             WHERE sa.activate_code = ?",
            [$activateCode]
        );

        if (empty($record)) {
            return ['success' => false, 'message' => '激活码无效'];
        }

        $record = $record[0];

        // 检查状态
        if ($record['activate_status'] != 0) {
            $statusText = [0 => '待激活', 1 => '已激活', 2 => '已失效', 3 => '已退款'];
            return ['success' => false, 'message' => '激活码已' . ($statusText[$record['activate_status']] ?? '失效')];
        }

        // 检查是否过期
        if (strtotime($record['expire_at']) < time()) {
            return ['success' => false, 'message' => '激活码已过期'];
        }

        // 更新激活状态
        Db::execute(
            "UPDATE {$this->table} SET activate_status = 1, user_id = ?, device_id = ?, activate_time = NOW() WHERE id = ?",
            [$userId, $deviceId, $record['id']]
        );

        return [
            'success'     => true,
            'expire_at'   => $record['expire_at'],
            'coach_phone' => $record['coach_phone']
        ];
    }

    /**
     * 获取教练激活记录列表
     */
    public function getListByCoach($coachId, $page = 1, $pageSize = 20, $status = null)
    {
        $offset = ($page - 1) * $pageSize;
        $where = ['coach_id = ?'];
        $params = [$coachId];

        if ($status !== null) {
            $where[] = 'activate_status = ?';
            $params[] = $status;
        }

        $whereSql = implode(' AND ', $where);

        $total = Db::query(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE {$whereSql}",
            $params
        )[0]['cnt'] ?? 0;

        $list = Db::query(
            "SELECT * FROM {$this->table} WHERE {$whereSql} ORDER BY id DESC LIMIT ? OFFSET ?",
            array_merge($params, [$pageSize, $offset])
        );

        return [
            'list'        => $list,
            'total'       => $total,
            'page'        => $page,
            'page_size'   => $pageSize,
            'total_pages' => ceil($total / $pageSize)
        ];
    }

    /**
     * 退款
     */
    public function refund($activationId, $coachId)
    {
        $record = Db::query(
            "SELECT * FROM {$this->table} WHERE id = ? AND coach_id = ?",
            [$activationId, $coachId]
        );

        if (empty($record)) {
            return ['success' => false, 'message' => '记录不存在'];
        }

        $record = $record[0];

        // 只能退款待激活的
        if ($record['activate_status'] != 0) {
            $statusText = [0 => '待激活', 1 => '已激活', 2 => '已失效', 3 => '已退款'];
            return ['success' => false, 'message' => '只能退款待激活的记录，当前状态：' . ($statusText[$record['activate_status']] ?? '未知')];
        }

        // 更新状态
        Db::execute(
            "UPDATE {$this->table} SET activate_status = 3, update_time = NOW() WHERE id = ?",
            [$activationId]
        );

        return ['success' => true, 'amount' => $record['amount_deducted']];
    }
}
