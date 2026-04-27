<?php
/**
 * 学员激活记录模型
 */

require_once __DIR__ . '/../library/Db.php';

class StudentActivationModel
{
    private $db;
    private $table = 'student_activation';

    public function __construct()
    {
        $this->db = Db::getInstance();
    }

    /**
     * 生成唯一激活码
     */
    public function generateActivateCode($length = 16)
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $code = '';
        for ($i = 0; $i < $length; $i++) {
            $code .= $chars[random_int(0, strlen($chars) - 1)];
        }

        // 检查是否已存在
        $exists = $this->db->fetch(
            "SELECT id FROM {$this->table} WHERE activate_code = ?",
            [$code]
        );

        if ($exists) {
            return $this->generateActivateCode($length);
        }

        return $code;
    }

    /**
     * 教练激活学员（生成激活码）
     */
    public function createActivation($coachId, $studentPhone, $amount = 18.00, $expireDays = 30)
    {
        $activateCode = $this->generateActivateCode();

        $this->db->execute(
            "INSERT INTO {$this->table} (coach_id, student_phone, activate_code, amount_deducted, expire_days, activate_status) VALUES (?, ?, ?, ?, ?, 0)",
            [$coachId, $studentPhone, $activateCode, $amount, $expireDays]
        );

        return [
            'activation_id'   => $this->db->lastInsertId(),
            'activate_code'  => $activateCode,
            'student_phone'  => $studentPhone,
            'amount'         => $amount,
            'expire_days'    => $expireDays,
            'expire_at'      => date('Y-m-d H:i:s', strtotime("+{$expireDays} days")),
            'message'        => '激活码生成成功，请发送给学员'
        ];
    }

    /**
     * 学员激活（绑定设备）
     */
    public function activate($activateCode, $deviceId, $userId = null)
    {
        // 查找激活码记录
        $record = $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE activate_code = ? AND activate_status = 0",
            [$activateCode]
        );

        if (!$record) {
            return ['success' => false, 'message' => '激活码无效或已使用'];
        }

        // 检查是否过期
        $expireAt = strtotime($record['expire_at']);
        if ($expireAt < time()) {
            // 更新为失效状态
            $this->db->execute(
                "UPDATE {$this->table} SET activate_status = 2, deactivate_reason = '已过期' WHERE id = ?",
                [$record['id']]
            );
            return ['success' => false, 'message' => '激活码已过期'];
        }

        // 检查设备是否已被使用
        if ($deviceId) {
            $deviceUsed = $this->db->fetch(
                "SELECT id, student_phone FROM {$this->table} WHERE device_id = ? AND activate_status = 1",
                [$deviceId]
            );

            if ($deviceUsed) {
                return [
                    'success' => false,
                    'message' => "该设备已被学员 {$this->maskPhone($deviceUsed['student_phone'])} 激活使用"
                ];
            }
        }

        // 绑定设备并激活
        $now = date('Y-m-d H:i:s');
        $this->db->execute(
            "UPDATE {$this->table} SET activate_status = 1, device_id = ?, user_id = ?, activated_at = ?, expire_at = DATE_ADD(?, INTERVAL {$record['expire_days']} DAY) WHERE id = ?",
            [$deviceId, $userId, $now, $now, $record['id']]
        );

        return [
            'success'       => true,
            'message'       => '激活成功',
            'expire_at'     => date('Y-m-d H:i:s', strtotime("+{$record['expire_days']} days", strtotime($now))),
            'coach_phone'   => $this->getCoachPhone($record['coach_id'])
        ];
    }

    /**
     * 获取教练手机号
     */
    private function getCoachPhone($coachId)
    {
        $coach = $this->db->fetch("SELECT phone FROM coach WHERE id = ?", [$coachId]);
        return $coach ? $this->maskPhone($coach['phone']) : '';
    }

    /**
     * 手机号脱敏
     */
    private function maskPhone($phone)
    {
        return substr($phone, 0, 3) . '****' . substr($phone, -4);
    }

    /**
     * 检查用户是否已激活
     */
    public function checkUserActivation($userId, $deviceId = null)
    {
        $sql = "SELECT * FROM {$this->table} WHERE user_id = ? AND activate_status = 1";
        $params = [$userId];

        if ($deviceId) {
            $sql .= " AND device_id = ?";
            $params[] = $deviceId;
        }

        $sql .= " ORDER BY activated_at DESC LIMIT 1";

        $record = $this->db->fetch($sql, $params);

        if (!$record) {
            return [
                'activated' => false,
                'message'   => '未找到激活记录'
            ];
        }

        // 检查是否过期
        $expireAt = strtotime($record['expire_at']);
        if ($expireAt < time()) {
            return [
                'activated'   => false,
                'message'     => '激活已过期',
                'expire_at'   => $record['expire_at']
            ];
        }

        return [
            'activated'      => true,
            'expire_at'      => $record['expire_at'],
            'coach_phone'    => $this->getCoachPhone($record['coach_id']),
            'activated_at'   => $record['activated_at'],
            'message'        => '激活有效'
        ];
    }

    /**
     * 获取教练的激活记录列表
     */
    public function getListByCoach($coachId, $page = 1, $pageSize = 20, $status = null)
    {
        $offset = ($page - 1) * $pageSize;

        $where = "coach_id = ?";
        $params = [$coachId];

        if ($status !== null) {
            $where .= " AND activate_status = ?";
            $params[] = $status;
        }

        // 获取总数
        $total = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM {$this->table} WHERE {$where}",
            $params
        );

        // 获取列表
        $list = $this->db->fetchAll(
            "SELECT sa.*, u.nickname as student_nickname, u.avatar as student_avatar
             FROM {$this->table} sa
             LEFT JOIN user u ON sa.user_id = u.id
             WHERE {$where}
             ORDER BY sa.create_time DESC
             LIMIT ? OFFSET ?",
            array_merge($params, [$pageSize, $offset])
        );

        // 处理数据
        foreach ($list as &$item) {
            $item['student_phone_mask'] = $this->maskPhone($item['student_phone']);
            $item['status_text'] = $this->getStatusText($item['activate_status']);
        }

        return [
            'list'        => $list,
            'total'       => $total,
            'page'        => $page,
            'page_size'   => $pageSize,
            'total_pages' => ceil($total / $pageSize)
        ];
    }

    /**
     * 获取激活状态文字
     */
    private function getStatusText($status)
    {
        $statusMap = [
            0 => '待激活',
            1 => '已激活',
            2 => '已失效',
            3 => '已退款'
        ];
        return $statusMap[$status] ?? '未知';
    }

    /**
     * 退款（作废激活码）
     */
    public function refund($activationId, $coachId)
    {
        $record = $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE id = ? AND coach_id = ?",
            [$activationId, $coachId]
        );

        if (!$record) {
            return ['success' => false, 'message' => '记录不存在'];
        }

        if ($record['activate_status'] == 1) {
            // 已激活的，需要先退款余额
            return ['success' => false, 'message' => '已激活的记录无法退款'];
        }

        if ($record['activate_status'] == 3) {
            return ['success' => false, 'message' => '该记录已退款'];
        }

        // 更新状态
        $this->db->execute(
            "UPDATE {$this->table} SET activate_status = 3, deactivate_reason = '退款' WHERE id = ?",
            [$activationId]
        );

        return [
            'success' => true,
            'message' => '退款成功',
            'amount'  => $record['amount_deducted']
        ];
    }
}
