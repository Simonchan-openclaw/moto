<?php
/**
 * 教练模型
 */

require_once __DIR__ . '/../library/Db.php';

class CoachModel
{
    private $db;
    private $table = 'coach';

    public function __construct()
    {
        $this->db = Db::getInstance();
    }

    /**
     * 根据手机号查找教练
     */
    public function findByPhone($phone)
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE phone = ? AND status = 1",
            [$phone]
        );
    }

    /**
     * 根据ID查找教练
     */
    public function findById($id)
    {
        return $this->db->fetch(
            "SELECT id, phone, real_name, avatar, balance, total_recharged, status, create_time FROM {$this->table} WHERE id = ?",
            [$id]
        );
    }

    /**
     * 教练注册
     */
    public function register($phone, $password, $realName = '')
    {
        // 检查手机号是否已注册
        $exists = $this->findByPhone($phone);
        if ($exists) {
            throw new Exception('该手机号已注册');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $this->db->execute(
            "INSERT INTO {$this->table} (phone, password, real_name) VALUES (?, ?, ?)",
            [$phone, $passwordHash, $realName]
        );

        return $this->db->lastInsertId();
    }

    /**
     * 教练登录验证
     */
    public function verifyLogin($phone, $password)
    {
        $coach = $this->findByPhone($phone);

        if (!$coach) {
            return false;
        }

        if (!password_verify($password, $coach['password'])) {
            return false;
        }

        // 更新登录信息
        $this->db->execute(
            "UPDATE {$this->table} SET last_login_time = NOW(), last_login_ip = ? WHERE id = ?",
            [$_SERVER['REMOTE_ADDR'] ?? '', $coach['id']]
        );

        return $coach;
    }

    /**
     * 扣除余额
     */
    public function deductBalance($coachId, $amount)
    {
        return $this->db->execute(
            "UPDATE {$this->table} SET balance = balance - ? WHERE id = ? AND balance >= ?",
            [$amount, $coachId, $amount]
        );
    }

    /**
     * 增加余额（充值）
     */
    public function addBalance($coachId, $amount)
    {
        return $this->db->execute(
            "UPDATE {$this->table} SET balance = balance + ?, total_recharged = total_recharged + ? WHERE id = ?",
            [$amount, $amount, $coachId]
        );
    }

    /**
     * 获取教练余额
     */
    public function getBalance($coachId)
    {
        $coach = $this->findById($coachId);
        return $coach ? $coach['balance'] : 0;
    }

    /**
     * 生成Token
     */
    public function generateToken($coachId)
    {
        $token = bin2hex(random_bytes(32));
        $expireTime = date('Y-m-d H:i:s', strtotime('+30 days'));

        // 存储Token（可选择存入Redis或数据库）
        $tokenKey = "coach_token:{$coachId}";
        // 这里简化为直接返回，实际应存储
        return $token;
    }
}
