<?php
/**
 * 用户模型（学员）
 */

require_once __DIR__ . '/../library/Db.php';

class UserModel
{
    private $db;
    private $table = 'user';

    public function __construct()
    {
        $this->db = Db::getInstance();
    }

    /**
     * 根据手机号查找用户
     */
    public function findByPhone($phone)
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE phone = ? AND status = 1",
            [$phone]
        );
    }

    /**
     * 根据ID查找用户
     */
    public function findById($id)
    {
        return $this->db->fetch(
            "SELECT id, phone, nickname, avatar, status, create_time FROM {$this->table} WHERE id = ?",
            [$id]
        );
    }

    /**
     * 用户注册
     */
    public function register($phone, $password, $nickname = '')
    {
        // 检查手机号是否已注册
        $exists = $this->findByPhone($phone);
        if ($exists) {
            throw new Exception('该手机号已注册');
        }

        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $this->db->execute(
            "INSERT INTO {$this->table} (phone, password, nickname) VALUES (?, ?, ?)",
            [$phone, $passwordHash, $nickname ?: '摩托学员']
        );

        return $this->db->lastInsertId();
    }

    /**
     * 用户登录验证
     */
    public function verifyLogin($phone, $password)
    {
        $user = $this->findByPhone($phone);

        if (!$user) {
            return false;
        }

        if (!password_verify($password, $user['password'])) {
            return false;
        }

        // 更新登录信息
        $this->db->execute(
            "UPDATE {$this->table} SET last_login_time = NOW(), last_login_ip = ? WHERE id = ?",
            [$_SERVER['REMOTE_ADDR'] ?? '', $user['id']]
        );

        return $user;
    }

    /**
     * 更新用户信息
     */
    public function update($userId, $data)
    {
        $fields = [];
        $params = [];

        if (isset($data['nickname'])) {
            $fields[] = 'nickname = ?';
            $params[] = $data['nickname'];
        }

        if (isset($data['avatar'])) {
            $fields[] = 'avatar = ?';
            $params[] = $data['avatar'];
        }

        if (empty($fields)) {
            return false;
        }

        $params[] = $userId;

        return $this->db->execute(
            "UPDATE {$this->table} SET " . implode(', ', $fields) . " WHERE id = ?",
            $params
        );
    }

    /**
     * 验证验证码（简化版，实际应接入短信服务）
     */
    public function verifyCode($phone, $code, $type = 'login')
    {
        // TODO: 实际应查询短信发送记录表
        // 这里简化处理，测试验证码为 123456
        return $code === '123456';
    }
}
