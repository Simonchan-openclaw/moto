<?php
namespace app\controller\coach;

use app\model\Coach as CoachModel;
use app\model\RechargeRecord as RechargeRecordModel;
use app\model\StudentActivation as StudentActivationModel;

class Coach
{
    protected $coachModel;
    protected $rechargeModel;
    protected $activationModel;

    public function __construct()
    {
        $this->coachModel = new CoachModel();
        $this->rechargeModel = new RechargeRecordModel();
        $this->activationModel = new StudentActivationModel();
    }

    /**
     * 教练登录
     * POST /api/coach/login
     */
    public function login()
    {
        $phone = input('post.phone', '');
        $password = input('post.password', '');

        if (empty($phone) || empty($password)) {
            return jsonError('手机号和密码不能为空');
        }

        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return jsonError('手机号格式不正确');
        }

        $coach = $this->coachModel->verifyLogin($phone, $password);

        if (!$coach) {
            return jsonError('手机号或密码错误');
        }

        // 生成Token
        $token = createToken($coach['id'], ['type' => 'coach']);

        return jsonSuccess([
            'token'     => $token,
            'coach_id'  => $coach['id'],
            'phone'     => $coach['phone'],
            'real_name' => $coach['real_name'],
            'balance'   => $coach['balance']
        ], '登录成功');
    }

    /**
     * 教练注册
     * POST /api/coach/register
     */
    public function register()
    {
        $phone = input('post.phone', '');
        $password = input('post.password', '');
        $code = input('post.code', '');
        $realName = input('post.real_name', '');

        if (empty($phone) || empty($password) || empty($code)) {
            return jsonError('手机号、验证码和密码不能为空');
        }

        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return jsonError('手机号格式不正确');
        }

        if (strlen($password) < 6) {
            return jsonError('密码至少6位');
        }

        // TODO: 验证验证码
        if ($code !== '123456') {
            return jsonError('验证码错误');
        }

        try {
            $coachId = $this->coachModel->register($phone, $password, $realName);
            return jsonSuccess(['coach_id' => $coachId], '注册成功');
        } catch (\Exception $e) {
            return jsonError($e->getMessage());
        }
    }

    /**
     * 获取教练信息（包含二维码URL）
     * GET /api/coach/info
     */
    public function getInfo()
    {
        $coachId = getCurrentUserId();
        $coach = $this->coachModel->findById($coachId);

        if (!$coach) {
            return jsonError('教练不存在');
        }

        // 生成邀请链接
        $inviteCode = base64_encode(json_encode(['coach_id' => $coachId, 'time' => time()]));
        $inviteUrl = "https://moto.zd16688.com/h5/index.html?invite_code=" . urlencode($inviteCode);
        
        // 使用中国可访问的二维码API
        $qrCodeUrl = "https://api.uomg.com/api/qrcode?text=" . urlencode($inviteUrl) . "&size=300";

        return jsonSuccess([
            'coach_id'    => $coach['id'],
            'phone'       => $coach['phone'],
            'real_name'   => $coach['real_name'],
            'balance'     => $coach['balance'],
            'invite_url'  => $inviteUrl,
            'invite_code' => $inviteCode,
            'qrcode_url' => $qrCodeUrl,
        ]);
    }

    /**
     * 生成邀请二维码图片（直接输出PNG）
     * GET /api/coach/qrcode
     */
    public function qrCode()
    {
        $coachId = getCurrentUserId();
        if (!$coachId) {
            header('HTTP/1.1 401 Unauthorized');
            exit('Unauthorized');
        }

        // 生成邀请链接
        $inviteCode = base64_encode(json_encode(['coach_id' => $coachId, 'time' => time()]));
        $inviteUrl = "https://moto.zd16688.com/h5/index.html?invite_code=" . urlencode($inviteCode);

        // 使用QR Server API获取二维码图片
        $qrUrl = "https://api.qrserver.com/v1/create-qr-code/?size=300x300&margin=10&format=png&data=" . urlencode($inviteUrl);

        $context = stream_context_create([
            'http' => [
                'timeout' => 10,
                'user_agent' => 'Mozilla/5.0'
            ]
        ]);

        $imageData = @file_get_contents($qrUrl, false, $context);

        if ($imageData !== false && strlen($imageData) > 100) {
            header('Content-Type: image/png');
            header('Cache-Control: max-age=3600, public');
            echo $imageData;
        } else {
            // 返回空白图片
            header('Content-Type: image/png');
            echo base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNk+M9QDwADhgGAWjR9awAAAABJRU5ErkJggg==');
        }
        exit;
    }

    /**
     * 获取邀请学员列表
     * GET /api/coach/invite_list
     */
    public function getInviteList()
    {
        $coachId = getCurrentUserId();
        $page = input('get.page/d', 1);
        $pageSize = input('get.page_size/d', 20);

        $offset = ($page - 1) * $pageSize;

        // 获取邀请的用户列表
        $list = \think\facade\Db::query(
            "SELECT id, phone, nickname, create_time FROM user WHERE inv_coach_id = ? ORDER BY id DESC LIMIT ? OFFSET ?",
            [$coachId, $pageSize, $offset]
        );

        // 获取总数
        $total = \think\facade\Db::query(
            "SELECT COUNT(*) as cnt FROM user WHERE inv_coach_id = ?",
            [$coachId]
        )[0]['cnt'] ?? 0;

        // 脱敏手机号
        foreach ($list as &$item) {
            if (!empty($item['phone'])) {
                $item['phone_mask'] = substr($item['phone'], 0, 3) . '****' . substr($item['phone'], -4);
            }
        }

        return jsonSuccess([
            'list'       => $list,
            'total'      => $total,
            'page'       => $page,
            'page_size'  => $pageSize,
            'total_pages'=> ceil($total / $pageSize)
        ]);
    }

    /**
     * 获取教练余额
     * GET /api/coach/balance
     */
    public function getBalance()
    {
        $coachId = getCurrentUserId();
        $coach = $this->coachModel->findById($coachId);

        if (!$coach) {
            return jsonError('教练不存在');
        }

        return jsonSuccess([
            'balance'         => $coach['balance'],
            'total_recharged' => $coach['total_recharged']
        ]);
    }

    /**
     * 教练充值
     * POST /api/coach/recharge
     */
    public function recharge()
    {
        $coachId = getCurrentUserId();
        $amount = floatval(input('post.amount', 0));
        $payMethod = intval(input('post.pay_method', 1));

        $minAmount = 18.00;

        if ($amount < $minAmount) {
            return jsonError("最低充值金额为 {$minAmount} 元");
        }

        // TODO: 调用支付接口
        $tradeNo = 'WX' . date('YmdHis') . rand(1000, 9999);

        // 创建充值记录
        $recordId = $this->rechargeModel->create($coachId, $amount, $payMethod, $tradeNo, 1);

        // 增加教练余额
        $this->coachModel->addBalance($coachId, $amount);

        // 获取最新余额
        $balance = $this->coachModel->getBalance($coachId);

        return jsonSuccess([
            'record_id' => $recordId,
            'trade_no'  => $tradeNo,
            'amount'    => $amount,
            'balance'   => $balance,
            'message'   => '充值成功'
        ], '充值成功');
    }

    /**
     * 获取充值记录列表
     * GET /api/coach/recharge_list
     */
    public function rechargeList()
    {
        $coachId = getCurrentUserId();
        $page = input('get.page/d', 1);
        $pageSize = input('get.page_size/d', 20);

        $result = $this->rechargeModel->getListByCoach($coachId, $page, $pageSize);

        return jsonSuccess($result);
    }

    /**
     * 激活学员
     * POST /api/coach/activate
     */
    public function activate()
    {
        $coachId = getCurrentUserId();
        $studentPhone = input('post.student_phone', '');

        if (empty($studentPhone)) {
            return jsonError('学员手机号不能为空');
        }

        if (!preg_match('/^1[3-9]\d{9}$/', $studentPhone)) {
            return jsonError('学员手机号格式不正确');
        }

        // 获取激活配置
        $activationPrice = 38.00; // 有邀请人的学员激活费用38元
        $inviteReward = 20.00;    // 邀请教练奖励20元
        $expireDays = 90;         // VIP有效期90天

        // 检查余额
        $balance = $this->coachModel->getBalance($coachId);

        if ($balance < $activationPrice) {
            return jsonError("余额不足，当前余额 {$balance} 元，需要 {$activationPrice} 元");
        }

        // 查找学员信息（检查是否有邀请教练）
        $userModel = new \app\model\User();
        $student = $userModel->findByPhone($studentPhone);
        $invCoachId = 0;
        $hasInvited = false;

        if ($student && !empty($student['inv_coach_id']) && $student['inv_coach_id'] != $coachId) {
            // 学员有邀请教练，且不是当前教练
            $invCoachId = $student['inv_coach_id'];
            $hasInvited = true;
        }

        // 扣除当前教练余额
        $deducted = $this->coachModel->deductBalance($coachId, $activationPrice);

        if (!$deducted) {
            return jsonError('余额扣除失败');
        }

        // 如果有邀请教练，给邀请教练20元奖励
        if ($hasInvited && $invCoachId > 0) {
            $this->coachModel->addBalance($invCoachId, $inviteReward);
            
            // 记录邀请奖励
            \think\facade\Db::execute(
                "INSERT INTO coach_invite_reward (coach_id, student_phone, inviter_coach_id, reward_amount, create_time)
                 VALUES (?, ?, ?, ?, NOW())",
                [$invCoachId, $studentPhone, $coachId, $inviteReward]
            );
        }

        // 创建激活码
        $activateCode = strtoupper(substr(md5($coachId . time() . rand(1000, 9999)), 0, 8));
        $expireAt = date('Y-m-d H:i:s', strtotime("+{$expireDays} days"));

        // 写入数据库
        \think\facade\Db::execute(
            "INSERT INTO student_activation (coach_id, student_phone, activate_code, amount_deducted, expire_at, create_time)
             VALUES (?, ?, ?, ?, ?, NOW())",
            [$coachId, $studentPhone, $activateCode, $activationPrice, $expireAt]
        );

        // 获取最新余额
        $newBalance = $this->coachModel->getBalance($coachId);

        // 获取邀请教练信息
        $inviterInfo = null;
        if ($hasInvited && $invCoachId > 0) {
            $inviter = $this->coachModel->findById($invCoachId);
            if ($inviter) {
                $inviterInfo = [
                    'name' => $inviter['real_name'] ?: '教练' . $invCoachId,
                    'reward' => $inviteReward
                ];
            }
        }

        return jsonSuccess([
            'activate_code'  => $activateCode,
            'student_phone'  => $studentPhone,
            'amount'        => $activationPrice,
            'invite_reward' => $hasInvited ? $inviteReward : 0,
            'inviter_info'  => $inviterInfo,
            'expire_at'     => $expireAt,
            'balance'       => $newBalance,
            'message'      => $hasInvited ? '激活码生成成功，已给邀请教练奖励20元' : '激活码生成成功，请发送给学员'
        ], '激活码生成成功');
    }

    /**
     * 获取激活记录列表
     * GET /api/coach/activation_list
     */
    public function activationList()
    {
        $coachId = getCurrentUserId();
        $page = input('get.page/d', 1);
        $pageSize = input('get.page_size/d', 20);
        $status = input('get.status/d', null);

        $result = $this->activationModel->getListByCoach($coachId, $page, $pageSize, $status);

        return jsonSuccess($result);
    }

    /**
     * 退款
     * POST /api/coach/refund
     */
    public function refund()
    {
        $coachId = getCurrentUserId();
        $activationId = input('post.activation_id/d', 0);

        if ($activationId <= 0) {
            return jsonError('激活记录ID不能为空');
        }

        $result = $this->activationModel->refund($activationId, $coachId);

        if (!$result['success']) {
            return jsonError($result['message']);
        }

        // 退还余额
        $this->coachModel->addBalance($coachId, $result['amount']);

        $newBalance = $this->coachModel->getBalance($coachId);

        return jsonSuccess([
            'refund_amount' => $result['amount'],
            'balance'       => $newBalance
        ], '退款成功');
    }
}
