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
        $activationPrice = 18.00;
        $expireDays = 30;

        // 检查余额
        $balance = $this->coachModel->getBalance($coachId);

        if ($balance < $activationPrice) {
            return jsonError("余额不足，当前余额 {$balance} 元，需要 {$activationPrice} 元");
        }

        // 扣除余额
        $deducted = $this->coachModel->deductBalance($coachId, $activationPrice);

        if (!$deducted) {
            return jsonError('余额扣除失败');
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

        return jsonSuccess([
            'activate_code'  => $activateCode,
            'student_phone'  => $studentPhone,
            'amount'         => $activationPrice,
            'expire_at'      => $expireAt,
            'balance'        => $newBalance,
            'message'        => '激活码生成成功，请发送给学员'
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
