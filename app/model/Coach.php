<?php
namespace app\model;

use think\facade\Db;

class Coach
{
    protected $table = 'coach';

    /**
     * 验证登录
     */
    public function verifyLogin($phone, $password)
    {
        $coach = Db::query(
            "SELECT * FROM {$this->table} WHERE phone = ? AND status = 1",
            [$phone]
        );

        if (empty($coach)) {
            return null;
        }

        $coach = $coach[0];

        if (md5($password) !== $coach['password']) {
            return null;
        }

        return $coach;
    }

    /**
     * 获取教练名称
     */
    public function getCoachName($coachId)
    {
        $coach = $this->findById($coachId);
        if (!$coach) {
            return '未知教练';
        }
        return $coach['real_name'] ?: ('教练' . $coach['id']);
    }

    /**
     * 根据ID查找
     */
    public function findById($id)
    {
        $result = Db::query(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$id]
        );
        return $result[0] ?? null;
    }

    /**
     * 注册
     */
    public function register($phone, $password, $realName = '')
    {
        $exists = Db::query(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE phone = ?",
            [$phone]
        )[0]['cnt'] ?? 0;

        if ($exists) {
            throw new \Exception('该手机号已注册');
        }

        $passwordHash = md5($password);

        $data = [
            'phone'       => $phone,
            'password'    => $passwordHash,
            'real_name'   => $realName,
            'balance'     => 0,
            'total_recharged' => 0,
            'status'      => 0,
            'create_time' => date('Y-m-d H:i:s')
        ];

        return Db::name('coach')->insert($data, true);
    }

    /**
     * 获取余额
     */
    public function getBalance($coachId)
    {
        $coach = $this->findById($coachId);
        return $coach ? $coach['balance'] : 0;
    }

    /**
     * 增加余额
     */
    public function addBalance($coachId, $amount)
    {
        Db::execute(
            "UPDATE {$this->table} SET balance = balance + ?, total_recharged = total_recharged + ? WHERE id = ?",
            [$amount, $amount, $coachId]
        );
    }

    /**
     * 扣除余额
     */
    public function deductBalance($coachId, $amount)
    {
        $balance = $this->getBalance($coachId);
        if ($balance < $amount) {
            return false;
        }

        Db::execute(
            "UPDATE {$this->table} SET balance = balance - ? WHERE id = ?",
            [$amount, $coachId]
        );

        return true;
    }
}
