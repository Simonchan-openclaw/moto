<?php
/**
 * 充值记录模型
 */

require_once __DIR__ . '/../library/Db.php';

class RechargeRecordModel
{
    private $db;
    private $table = 'recharge_record';

    public function __construct()
    {
        $this->db = Db::getInstance();
    }

    /**
     * 创建充值记录
     */
    public function create($coachId, $amount, $payMethod = 1, $tradeNo = '', $status = 1)
    {
        $this->db->execute(
            "INSERT INTO {$this->table} (coach_id, amount, pay_method, trade_no, status) VALUES (?, ?, ?, ?, ?)",
            [$coachId, $amount, $payMethod, $tradeNo, $status]
        );

        return $this->db->lastInsertId();
    }

    /**
     * 获取教练充值记录列表
     */
    public function getListByCoach($coachId, $page = 1, $pageSize = 20)
    {
        $offset = ($page - 1) * $pageSize;

        // 获取总数
        $total = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM {$this->table} WHERE coach_id = ?",
            [$coachId]
        );

        // 获取列表
        $list = $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE coach_id = ? ORDER BY create_time DESC LIMIT ? OFFSET ?",
            [$coachId, $pageSize, $offset]
        );

        return [
            'list'       => $list,
            'total'      => $total,
            'page'       => $page,
            'page_size'  => $pageSize,
            'total_pages' => ceil($total / $pageSize)
        ];
    }

    /**
     * 根据交易流水号查找
     */
    public function findByTradeNo($tradeNo)
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE trade_no = ?",
            [$tradeNo]
        );
    }

    /**
     * 更新充值状态
     */
    public function updateStatus($id, $status)
    {
        return $this->db->execute(
            "UPDATE {$this->table} SET status = ? WHERE id = ?",
            [$status, $id]
        );
    }
}
