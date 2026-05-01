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
        $data = [
            'coach_id'    => $coachId,
            'amount'      => $amount,
            'pay_method'  => $payMethod,
            'trade_no'    => $tradeNo,
            'status'      => $status,
            'create_time' => date('Y-m-d H:i:s')
        ];
        
        return Db::name('recharge_record')->insert($data, true);
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
