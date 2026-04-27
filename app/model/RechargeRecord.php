<?php
namespace app\model;

use think\facade\Db;

class RechargeRecord
{
    protected $table = 'recharge_record';

    /**
     * 创建充值记录
     */
    public function create($coachId, $amount, $payMethod, $tradeNo, $status = 0)
    {
        Db::execute(
            "INSERT INTO {$this->table} (coach_id, amount, pay_method, trade_no, status, create_time) 
             VALUES (?, ?, ?, ?, ?, NOW())",
            [$coachId, $amount, $payMethod, $tradeNo, $status]
        );
        return Db::getLastInsID();
    }

    /**
     * 获取教练充值记录
     */
    public function getListByCoach($coachId, $page = 1, $pageSize = 20)
    {
        $offset = ($page - 1) * $pageSize;

        $total = Db::query(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE coach_id = ?",
            [$coachId]
        )[0]['cnt'] ?? 0;

        $list = Db::query(
            "SELECT * FROM {$this->table} WHERE coach_id = ? ORDER BY id DESC LIMIT ? OFFSET ?",
            [$coachId, $pageSize, $offset]
        );

        return [
            'list'        => $list,
            'total'       => $total,
            'page'        => $page,
            'page_size'   => $pageSize,
            'total_pages' => ceil($total / $pageSize)
        ];
    }
}
