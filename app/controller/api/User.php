<?php
namespace app\controller\api;

use app\model\User as UserModel;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

class User
{
    protected $model;

    public function __construct()
    {
        $this->model = new UserModel();
    }

    /**
     * 发送验证码
     * POST /api/user/send_code
     */
    public function sendCode()
    {
        $phone = input('post.phone', '');
        $type = input('post.type', 'login');

        if (empty($phone)) {
            return jsonError('手机号不能为空');
        }

        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return jsonError('手机号格式不正确');
        }

        // TODO: 实际应接入短信服务
        // 模拟：生成6位验证码
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        // 存储验证码（实际应存入缓存或数据库）
        $cacheDir = runtime_path() . 'codes/';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        file_put_contents($cacheDir . md5($phone) . '.txt', $code);

        return jsonSuccess([
            'success' => true,
            'message' => '验证码发送成功',
            'code'    => $code  // 测试环境返回，生产环境不应返回
        ], '验证码发送成功');
    }

    /**
     * 用户登录
     * POST /api/user/login
     * @param string $phone 手机号
     * @param string $deviceId 设备码
     */
    public function login()
    {
        $phone = input('post.phone', '');
        $deviceId = input('post.code', '');
        $countryCode = input('post.country_code', '86');

        if (empty($phone) || empty($deviceId)) {
            return jsonError('手机号和设备码不能为空');
        }

        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return jsonError('手机号格式不正确');
        }

        if (strlen($deviceId) < 8) {
            return jsonError('设备码无效');
        }

        // 查找用户（按区域码+手机号）
        $user = $this->model->findByPhone($phone, $countryCode);

        // 用户不存在，返回未注册错误
        if (!$user) {
            return jsonError('该手机号未注册，请先注册');
        }

        // 已存在用户，检查设备码是否匹配
        if (!empty($user['device_id']) && $user['device_id'] !== $deviceId) {
            return jsonError('该账号已绑定其他设备，请使用原设备登录');
        }

        // 如果用户没有设备码，记录当前设备码
        if (empty($user['device_id'])) {
            $this->model->updateDeviceId($user['id'], $deviceId);
        }

        // 生成JWT Token
        $token = createToken($user['id']);

        return jsonSuccess([
            'token'    => $token,
            'userInfo' => [
                'id'          => $user['id'],
                'phone'       => $user['phone'],
                'nickname'    => $user['nickname'],
                'avatar'      => $user['avatar'] ?? '',
                'inv_coach_id'=> $user['inv_coach_id'] ?? 0,
                'create_time' => $user['create_time']
            ]
        ], '登录成功');
    }

    /**
     * 用户注册
     * POST /api/user/register
     * @param string $phone 手机号
     * @param string $deviceId 设备码
     * @param string $inviteCode 邀请码（可选）
     */
    public function register()
    {
        $phone = input('post.phone', '');
        $name = input('post.name', '');
        $password = input('post.password', '');
        $deviceId = input('post.device_id', '');
        $inviteCode = input('post.invite_code', '');
        $countryCode = input('post.country_code', '86');

        if (empty($phone)) {
            return jsonError('手机号不能为空');
        }

        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return jsonError('手机号格式不正确');
        }

        if (empty($name)) {
            return jsonError('姓名不能为空');
        }

        if (empty($password) || strlen($password) < 6) {
            return jsonError('密码不能少于6位');
        }

        if (empty($deviceId) || strlen($deviceId) < 8) {
            return jsonError('设备码无效');
        }

        // 检查是否已注册（同一区域码下唯一）
        $exists = $this->model->findByPhone($phone, $countryCode);
        if ($exists) {
            return jsonError('该手机号已注册，请直接登录');
        }

        // 解析邀请码（新格式：C开头+教练ID，或旧格式：base64 JSON）
        $invCoachId = 0;
        if (!empty($inviteCode)) {
            if (strpos($inviteCode, 'C') === 0) {
                // 新格式：C10001 -> coachId = 10001
                $invCoachId = intval(substr($inviteCode, 1));
            } else {
                // 旧格式：base64 JSON
                try {
                    $decoded = json_decode(base64_decode($inviteCode), true);
                    if (isset($decoded['coach_id']) && intval($decoded['coach_id']) > 0) {
                        $invCoachId = intval($decoded['coach_id']);
                    }
                } catch (\Exception $e) {
                    // 无效的邀请码，忽略
                }
            }
        }

        // 创建用户（密码需要加密）
        $passwordHash = password_hash($password, PASSWORD_DEFAULT);
        $userId = $this->model->register($phone, $passwordHash, $name, $deviceId, $invCoachId, $countryCode);
        $user = $this->model->findById($userId);

        // 生成JWT Token
        $token = createToken($user['id']);

        return jsonSuccess([
            'token'    => $token,
            'userInfo' => [
                'id'          => $user['id'],
                'phone'       => $user['phone'],
                'nickname'    => $user['nickname'],
                'avatar'      => $user['avatar'] ?? '',
                'inv_coach_id'=> $user['inv_coach_id'] ?? 0,
                'create_time' => $user['create_time']
            ]
        ], $invCoachId > 0 ? '注册成功，已绑定邀请教练' : '注册成功');
    }

    /**
     * 获取教练信息（根据coach_id）
     * GET /api/coach/info?id=xxx
     */
    public function getCoachInfo()
    {
        $coachId = input('get.id/d', 0);
        
        if (!$coachId) {
            return jsonError('教练ID不能为空');
        }
        
        $coach = \think\facade\Db::name('coach')
            ->where('id', $coachId)
            ->where('status', 1)
            ->find();
        
        if (!$coach) {
            return jsonError('教练不存在');
        }
        
        return jsonSuccess([
            'coach_id'   => $coach['id'],
            'real_name'  => $coach['real_name'] ?: ('教练' . $coach['id']),
            'phone'      => substr($coach['phone'], 0, 3) . '****' . substr($coach['phone'], -4)
        ]);
    }

    /**
     * 获取用户信息
     * POST /api/user/info
     */
    public function info()
    {
        $userId = getCurrentUserId();
        
        $user = $this->model->findById($userId);

        if (!$user) {
            return jsonError('用户不存在');
        }

        return jsonSuccess([
            'id'          => $user['id'],
            'phone'       => substr($user['phone'], 0, 3) . '****' . substr($user['phone'], -4),
            'nickname'    => $user['nickname'],
            'avatar'      => $user['avatar'] ?? '',
            'create_time' => $user['create_time']
        ]);
    }

    /**
     * 更新用户信息
     * PUT /api/user/update
     */
    public function update()
    {
        $userId = getCurrentUserId();
        $data = input('put.');
        
        if (empty($data)) {
            return jsonError('没有要更新的数据');
        }

        // 过滤允许更新的字段
        $allowedFields = ['nickname', 'avatar'];
        $updateData = [];

        foreach ($allowedFields as $field) {
            if (isset($data[$field]) && $data[$field] !== '') {
                $updateData[$field] = $data[$field];
            }
        }

        if (empty($updateData)) {
            return jsonError('没有有效的更新字段');
        }

        $this->model->update($userId, $updateData);

        return jsonSuccess(['success' => true], '修改成功');
    }

    /**
     * 绑定设备
     * POST /api/user/bind_device
     */
    public function bindDevice()
    {
        $userId = getCurrentUserId();
        $deviceId = input('post.device_id', '');

        if (empty($deviceId)) {
            return jsonError('设备ID不能为空');
        }

        // TODO: 实现设备绑定逻辑
        return jsonSuccess(['success' => true], '设备绑定成功');
    }
}
