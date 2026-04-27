<?php
namespace app\model;

use think\Model;

class User extends Model
{
    protected $name = 'user';
    protected $pk = 'id';

    /**
     * 根据手机号查找用户
     */
    public function findByPhone($phone)
    {
        return $this->where('phone', $phone)->where('status', 1)->find();
    }

    /**
     * 根据ID查找用户
     */
    public function findById($id)
    {
        return $this->field('id, phone, nickname, avatar, status, create_time')->find($id);
    }

    /**
     * 用户注册
     */
    public function register($phone, $password, $nickname = '')
    {
        // 检查手机号是否已注册
        $exists = $this->findByPhone($phone);
        if ($exists) {
            throw new \Exception('该手机号已注册');
        }

        $data = [
            'phone'    => $phone,
            'nickname' => $nickname ?: '摩托学员',
            'status'   => 1,
        ];

        if (!empty($password)) {
            $data['password'] = password_hash($password, PASSWORD_DEFAULT);
        }

        return $this->insertGetId($data);
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

        if (!empty($user['password']) && !password_verify($password, $user['password'])) {
            return false;
        }

        // 更新登录信息
        $this->where('id', $user['id'])->update([
            'last_login_time' => date('Y-m-d H:i:s'),
            'last_login_ip'   => request()->ip()
        ]);

        return $user;
    }

    /**
     * 更新用户信息
     */
    public function updateInfo($userId, $data)
    {
        $allowedFields = ['nickname', 'avatar'];
        $updateData = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return false;
        }

        return $this->where('id', $userId)->update($updateData);
    }
}
