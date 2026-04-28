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
     * @param string $phone 手机号
     * @param string $password 密码（设备码）
     * @param string $nickname 昵称
     * @param string $deviceId 设备ID
     */
    public function register($phone, $password = '', $nickname = '', $deviceId = '')
    {
        // 检查手机号是否已注册
        $exists = $this->findByPhone($phone);
        if ($exists) {
            return $exists['id'];
        }

        $data = [
            'phone'    => $phone,
            'nickname' => $nickname ?: '摩托学员',
            'status'   => 1,
            'password' => password_hash($password ?: $deviceId, PASSWORD_DEFAULT),
            'device_id' => $deviceId,
            'create_time' => date('Y-m-d H:i:s'),
        ];

        return $this->insertGetId($data);
    }

    /**
     * 更新用户设备码
     */
    public function updateDeviceId($userId, $deviceId)
    {
        return $this->where('id', $userId)->update([
            'device_id' => $deviceId,
            'update_time' => date('Y-m-d H:i:s'),
        ]);
    }

    /**
     * 验证设备ID
     */
    public function verifyDeviceId($userId, $deviceId)
    {
        $user = $this->findById($userId);
        if (!$user) {
            return false;
        }
        
        // 如果用户已绑定设备ID，必须匹配
        if (!empty($user['device_id'])) {
            return $user['device_id'] === $deviceId;
        }
        
        return true;
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
