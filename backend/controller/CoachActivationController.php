<?php
/**
 * 教练端激活控制器
 */

require_once __DIR__ . '/../model/CoachModel.php';
require_once __DIR__ . '/../model/RechargeRecordModel.php';
require_once __DIR__ . '/../model/StudentActivationModel.php';
require_once __DIR__ . '/../library/Response.php';

class CoachActivationController
{
    private $coachModel;
    private $rechargeModel;
    private $activationModel;

    public function __construct()
    {
        $this->coachModel     = new CoachModel();
        $this->rechargeModel  = new RechargeRecordModel();
        $this->activationModel = new StudentActivationModel();
    }

    /**
     * 验证教练登录
     */
    private function authCoach()
    {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (empty($token)) {
            Response::unauthorized('请先登录');
        }

        // 简化验证：实际应解析Token获取教练ID
        // 这里假设Token格式为 "Bearer {coach_id}"
        if (strpos($token, 'Bearer ') === 0) {
            $coachId = intval(str_replace('Bearer ', '', $token));
            if ($coachId <= 0) {
                Response::unauthorized('无效的登录状态');
            }
            return $coachId;
        }

        Response::unauthorized('无效的登录状态');
    }

    /**
     * 教练登录
     * POST /api/coach/login
     */
    public function login()
    {
        $phone    = $_POST['phone'] ?? '';
        $password = $_POST['password'] ?? '';

        if (empty($phone) || empty($password)) {
            Response::error('手机号和密码不能为空');
        }

        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            Response::error('手机号格式不正确');
        }

        $coach = $this->coachModel->verifyLogin($phone, $password);

        if (!$coach) {
            Response::error('手机号或密码错误');
        }

        // 生成Token
        $token = bin2hex(random_bytes(32));

        Response::success([
            'token'      => $token,
            'coach_id'   => $coach['id'],
            'phone'      => $coach['phone'],
            'real_name'  => $coach['real_name'],
            'balance'    => $coach['balance']
        ], '登录成功');
    }

    /**
     * 教练注册
     * POST /api/coach/register
     */
    public function register()
    {
        $phone    = $_POST['phone'] ?? '';
        $password = $_POST['password'] ?? '';
        $code     = $_POST['code'] ?? '';
        $realName = $_POST['real_name'] ?? '';

        if (empty($phone) || empty($password) || empty($code)) {
            Response::error('手机号、验证码和密码不能为空');
        }

        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            Response::error('手机号格式不正确');
        }

        if (strlen($password) < 6) {
            Response::error('密码至少6位');
        }

        // TODO: 验证验证码（实际应接入短信服务）
        // 这里简化处理，测试验证码为 123456
        if ($code !== '123456') {
            Response::error('验证码错误');
        }

        try {
            $coachId = $this->coachModel->register($phone, $password, $realName);
            Response::success(['coach_id' => $coachId], '注册成功');
        } catch (Exception $e) {
            Response::error($e->getMessage());
        }
    }

    /**
     * 获取教练余额
     * GET /api/coach/balance
     */
    public function getBalance()
    {
        $coachId = $this->authCoach();

        $coach = $this->coachModel->findById($coachId);

        if (!$coach) {
            Response::error('教练不存在');
        }

        Response::success([
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
        $coachId   = $this->authCoach();
        $amount    = floatval($_POST['amount'] ?? 0);
        $payMethod = intval($_POST['pay_method'] ?? 1); // 1=微信 2=支付宝

        // 获取最低充值金额配置
        $minAmount = 18.00;

        if ($amount < $minAmount) {
            Response::error("最低充值金额为 {$minAmount} 元");
        }

        // TODO: 调用支付接口（微信支付/支付宝）
        // 这里简化处理，模拟支付成功
        $tradeNo = 'WX' . date('YmdHis') . rand(1000, 9999);

        // 创建充值记录
        $recordId = $this->rechargeModel->create($coachId, $amount, $payMethod, $tradeNo, 1);

        // 增加教练余额
        $this->coachModel->addBalance($coachId, $amount);

        // 获取最新余额
        $balance = $this->coachModel->getBalance($coachId);

        Response::success([
            'record_id'   => $recordId,
            'trade_no'    => $tradeNo,
            'amount'      => $amount,
            'balance'     => $balance,
            'message'     => '充值成功'
        ], '充值成功');
    }

    /**
     * 获取充值记录列表
     * GET /api/coach/recharge_list
     */
    public function rechargeList()
    {
        $coachId  = $this->authCoach();
        $page     = intval($_GET['page'] ?? 1);
        $pageSize = intval($_GET['page_size'] ?? 20);

        $result = $this->rechargeModel->getListByCoach($coachId, $page, $pageSize);

        Response::success($result);
    }

    /**
     * 激活学员
     * POST /api/coach/activate
     */
    public function activate()
    {
        $coachId     = $this->authCoach();
        $studentPhone = $_POST['student_phone'] ?? '';

        if (empty($studentPhone)) {
            Response::error('学员手机号不能为空');
        }

        if (!preg_match('/^1[3-9]\d{9}$/', $studentPhone)) {
            Response::error('学员手机号格式不正确');
        }

        // 获取激活配置
        $activationPrice = 18.00;
        $expireDays = 30;

        // 检查余额是否足够
        $balance = $this->coachModel->getBalance($coachId);

        if ($balance < $activationPrice) {
            Response::error("余额不足，当前余额 {$balance} 元，需要 {$activationPrice} 元", 400, [
                'balance' => $balance,
                'need'    => $activationPrice
            ]);
        }

        // 开启事务
        $db = \Db::getInstance();
        $db->beginTransaction();

        try {
            // 扣除余额
            $deducted = $this->coachModel->deductBalance($coachId, $activationPrice);

            if (!$deducted) {
                throw new Exception('余额扣除失败');
            }

            // 创建激活记录
            $result = $this->activationModel->createActivation($coachId, $studentPhone, $activationPrice, $expireDays);

            $db->commit();

            // 获取最新余额
            $newBalance = $this->coachModel->getBalance($coachId);

            Response::success([
                'activate_code' => $result['activate_code'],
                'student_phone' => $studentPhone,
                'amount'        => $activationPrice,
                'expire_at'     => $result['expire_at'],
                'balance'       => $newBalance,
                'message'       => '激活码生成成功，请发送给学员'
            ], '激活码生成成功');

        } catch (Exception $e) {
            $db->rollBack();
            Response::error('激活失败: ' . $e->getMessage());
        }
    }

    /**
     * 获取激活记录列表
     * GET /api/coach/activation_list
     */
    public function activationList()
    {
        $coachId  = $this->authCoach();
        $page     = intval($_GET['page'] ?? 1);
        $pageSize = intval($_GET['page_size'] ?? 20);
        $status   = isset($_GET['status']) ? intval($_GET['status']) : null;

        $result = $this->activationModel->getListByCoach($coachId, $page, $pageSize, $status);

        Response::success($result);
    }

    /**
     * 退款（作废激活码）
     * POST /api/coach/refund
     */
    public function refund()
    {
        $coachId       = $this->authCoach();
        $activationId = intval($_POST['activation_id'] ?? 0);

        if ($activationId <= 0) {
            Response::error('激活记录ID不能为空');
        }

        $result = $this->activationModel->refund($activationId, $coachId);

        if (!$result['success']) {
            Response::error($result['message']);
        }

        // 退还余额
        $this->coachModel->addBalance($coachId, $result['amount']);

        $newBalance = $this->coachModel->getBalance($coachId);

        Response::success([
            'refund_amount' => $result['amount'],
            'balance'       => $newBalance
        ], '退款成功');
    }
}
