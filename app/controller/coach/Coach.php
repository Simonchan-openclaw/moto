<?php
namespace app\controller\coach;

use app\model\Coach as CoachModel;
use app\model\RechargeRecord as RechargeRecordModel;
use app\model\StudentActivation as StudentActivationModel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use think\Response;

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
     * 获取当前教练ID（从Token解析）
     */
    protected function getCurrentCoachId()
    {
        $token = request()->header('Authorization', '');
        $token = str_replace('Bearer ', '', $token);
        if (empty($token)) {
            return 0;
        }
        $decoded = verifyToken($token);
        if (!$decoded || !isset($decoded->user_id)) {
            return 0;
        }
        return intval($decoded->user_id);
    }

    /**
     * 公开接口：根据邀请码获取教练信息（无需认证）
     * GET /api/coach/check?invite_code=xxx
     */
    public function check()
    {
        $inviteCode = input('get.invite_code', '');
        
        if (empty($inviteCode)) {
            return jsonError('邀请码不能为空');
        }
        
        // 解析邀请码获取教练ID
        $coachId = 0;
        
        // 支持新格式：C开头+教练ID
        if (strpos($inviteCode, 'C') === 0) {
            $coachId = intval(substr($inviteCode, 1));
        }
        // 支持旧格式：纯数字
        elseif (is_numeric($inviteCode)) {
            $coachId = intval($inviteCode);
        }
        // 支持base64 JSON格式
        else {
            try {
                $decoded = json_decode(base64_decode($inviteCode), true);
                if (isset($decoded['coach_id'])) {
                    $coachId = intval($decoded['coach_id']);
                }
            } catch (\Exception $e) {
                return jsonError('无效的邀请码');
            }
        }
        
        if (!$coachId) {
            return jsonError('无效的邀请码');
        }
        
        // 查询教练信息
        $coach = $this->coachModel->findById($coachId);
        
        if (!$coach || $coach['status'] != 1) {
            return jsonError('教练不存在');
        }
        
        return jsonSuccess([
            'coach_id'  => $coach['id'],
            'real_name' => $coach['real_name'] ?: '教练' . $coach['id'],
        ]);
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
    /**
     * 教练注册
     * POST /api/coach/register
     */
    public function register()
    {
        $name = input('post.name', '');
        $phone = input('post.phone', '');
        $password = input('post.password', '');

        if (empty($name)) {
            return jsonError('姓名不能为空');
        }

        if (empty($phone) || empty($password)) {
            return jsonError('手机号和密码不能为空');
        }

        if (!preg_match('/^1[3-9]\d{9}$/', $phone)) {
            return jsonError('手机号格式不正确');
        }

        if (strlen($password) < 6) {
            return jsonError('密码至少6位');
        }

        try {
            $coachId = $this->coachModel->register($phone, $password, $name);
            return jsonSuccess(['coach_id' => $coachId], '注册成功');
        } catch (\Exception $e) {
            return jsonError($e->getMessage());
        }
    }

    /**
     * 获取教练信息（包含二维码）
     * GET /api/coach/info
     */
    public function getInfo()
    {
        $coachId = $this->getCurrentCoachId();
        if (!$coachId) {
            return jsonError('请先登录', 401);
        }
        $coach = $this->coachModel->findById($coachId);

        if (!$coach) {
            return jsonError('教练不存在');
        }

        // 生成邀请链接（直接使用教练ID作为邀请码）
        $inviteCode = 'C' . $coachId;  // C开头避免与数字ID混淆
        $inviteUrl = "https://moto.zd16688.com/h5/index.html?invite_code=" . urlencode($inviteCode);
        
        // 生成二维码图片（使用endroid/qr-code库）
        $qrCode = new QrCode($inviteUrl);
        $qrCode->setSize(300);
        $qrCode->setMargin(10);
        $writer = new PngWriter();
        $result = $writer->write($qrCode);
        $qrCodeBase64 = 'data:image/png;base64,' . base64_encode($result->getString());

        return jsonSuccess([
            'coach_id'    => $coach['id'],
            'phone'       => $coach['phone'],
            'real_name'   => $coach['real_name'],
            'balance'     => $coach['balance'],
            'invite_url'  => $inviteUrl,
            'invite_code' => $inviteCode,
            'qrcode_base64' => $qrCodeBase64,
        ]);
    }

    /**
     * 生成邀请二维码图片（直接输出PNG）
     * GET /api/coach/qrcode
     */
    public function qrCode()
    {
        $coachId = $this->getCurrentCoachId();
        if (!$coachId) {
            return jsonError('请先登录', 401);
        }

        // 生成邀请链接（直接使用教练ID作为邀请码）
        $inviteCode = 'C' . $coachId;  // C开头避免与数字ID混淆
        $inviteUrl = "https://moto.zd16688.com/h5/index.html?invite_code=" . urlencode($inviteCode);

        // 使用endroid/qr-code库生成二维码
        $qrCode = new QrCode($inviteUrl);
        $qrCode->setSize(300);
        $qrCode->setMargin(10);
        $writer = new PngWriter();
        $result = $writer->write($qrCode);

        return Response::create($result->getString(), 'image/png');
    }

    /**
     * 获取邀请学员列表
     * GET /api/coach/invite_list
     */
    public function getInviteList()
    {
        $coachId = $this->getCurrentCoachId();
        $page = input('get.page/d', 1);
        $pageSize = input('get.page_size/d', 20);

        $offset = ($page - 1) * $pageSize;

        // 获取邀请的用户列表
        $list = \think\facade\Db::query(
            "SELECT id, phone, nickname, create_time FROM user WHERE inv_coach_id = ? ORDER BY id DESC LIMIT ? OFFSET ?",
            [$coachId, $pageSize, $offset]
        );

        // 获取总数
        $totalResult = \think\facade\Db::query(
            "SELECT COUNT(*) as cnt FROM user WHERE inv_coach_id = ?",
            [$coachId]
        );
        $total = isset($totalResult[0]['cnt']) ? $totalResult[0]['cnt'] : 0;

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
        $coachId = $this->getCurrentCoachId();
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
        $coachId = $this->getCurrentCoachId();
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
        $coachId = $this->getCurrentCoachId();
        $page = input('get.page/d', 1);
        $pageSize = input('get.page_size/d', 20);

        $result = $this->rechargeModel->getListByCoach($coachId, $page, $pageSize);

        return jsonSuccess($result);
    }

    /**
     * 核实学员是否被教练邀请
     * POST /api/coach/verify_student
     * 返回状态：
     * - not_registered: 学员未注册（红色边框）
     * - self_invited: 本教练邀请的学员（绿色边框）
     * - other_invited: 其他教练邀请的学员（橙色边框）
     */
    public function verifyStudent()
    {
        $coachId = $this->getCurrentCoachId();
        if (!$coachId) {
            return jsonError('请先登录', 401);
        }
        
        $studentPhone = input('post.phone', '');
        $countryCode = input('post.country_code', '86');

        if (empty($studentPhone)) {
            return jsonError('手机号不能为空');
        }

        if (!preg_match('/^1[3-9]\d{9}$/', $studentPhone)) {
            return jsonError('手机号格式不正确');
        }

        $userModel = new \app\model\User();
        $student = $userModel->findByPhone($studentPhone, $countryCode);

        // 学员未注册
        if (!$student) {
            return jsonSuccess([
                'status'        => 'not_registered',
                'message'       => '该学员未在册',
                'border_color'  => 'red',
                'phone'         => $studentPhone
            ]);
        }

        // 学员已注册，判断邀请教练
        $invCoachId = intval(isset($student['inv_coach_id']) ? $student['inv_coach_id'] : 0);
        
        if ($invCoachId == 0) {
            // 没有邀请教练
            return jsonSuccess([
                'status'        => 'no_invitation',
                'message'       => '可激活其他学员',
                'border_color'  => 'orange',
                'phone'         => $studentPhone,
                'inv_coach_id'  => 0
            ]);
        } elseif ($invCoachId == $coachId) {
            // 是本教练邀请的学员
            $coachName = $this->coachModel->getCoachName($coachId);
            return jsonSuccess([
                'status'        => 'self_invited',
                'message'       => '可激活邀请学员',
                'border_color'  => 'green',
                'phone'         => $studentPhone,
                'inv_coach_id'  => $invCoachId,
                'coach_name'    => $coachName
            ]);
        } else {
            // 是其他教练邀请的学员
            $coachName = $this->coachModel->getCoachName($invCoachId);
            return jsonSuccess([
                'status'        => 'other_invited',
                'message'       => '可激活其他学员',
                'border_color'  => 'orange',
                'phone'         => $studentPhone,
                'inv_coach_id'  => $invCoachId,
                'coach_name'    => $coachName
            ]);
        }
    }

    /**
     * 激活学员
     * POST /api/coach/activate
     * 逻辑：
     * - 激活邀请学员（自己邀请的）：扣18余额
     * - 激活其他学员（其他教练邀请的）：扣36，其中18转给邀请教练
     */
    public function activate()
    {
        $coachId = $this->getCurrentCoachId();
        $studentPhone = input('post.student_phone', '');
        $countryCode = input('post.country_code', '86');

        if (empty($studentPhone)) {
            return jsonError('学员手机号不能为空');
        }

        if (!preg_match('/^1[3-9]\d{9}$/', $studentPhone)) {
            return jsonError('学员手机号格式不正确');
        }

        // 激活配置
        $activationFee = 36.00;    // 激活费36元
        $commission = 18.00;        // 佣金18元
        $expireDays = 90;          // VIP有效期90天

        // 查找学员信息
        $userModel = new \app\model\User();
        $student = $userModel->findByPhone($studentPhone, $countryCode);

        if (!$student) {
            return jsonError('该学员未注册');
        }

        // 检查学员的邀请教练
        $invCoachId = isset($student['inv_coach_id']) ? $student['inv_coach_id'] : 0;
        $isSelfInvited = ($invCoachId == $coachId);

        // 计算扣款金额
        if ($isSelfInvited) {
            // 自己邀请的学员：扣18（36-18佣金）
            $deductAmount = $activationFee - $commission;
        } else {
            // 其他教练邀请的学员：扣36
            $deductAmount = $activationFee;
        }

        // 检查余额
        $balance = $this->coachModel->getBalance($coachId);
        if ($balance < $deductAmount) {
            return jsonError("余额不足，当前余额 {$balance} 元，需要 {$deductAmount} 元");
        }

        // 扣除当前教练余额
        $this->coachModel->deductBalance($coachId, $deductAmount);

        // 如果是其他教练邀请的，转18元给邀请教练
        if (!$isSelfInvited && $invCoachId > 0) {
            $this->coachModel->addBalance($invCoachId, $commission);
        }

        // 直接激活学员账号（设置VIP到期时间）
        $expireAt = date('Y-m-d H:i:s', strtotime("+{$expireDays} days"));
        $userModel->activateVip($student['id'], $expireAt);

        // 记录激活日志
        $logData = [
            'coach_id'      => $coachId,
            'user_id'       => $student['id'],
            'student_phone' => $studentPhone,
            'amount'        => $deductAmount,
            'is_self_invited' => $isSelfInvited ? 1 : 0,
            'inv_coach_id'  => $invCoachId,
            'commission'    => (!$isSelfInvited && $invCoachId > 0) ? $commission : 0,
            'expire_at'     => $expireAt,
            'create_time'   => date('Y-m-d H:i:s')
        ];
        \think\facade\Db::name('activation_log')->insert($logData);

        // 获取最新余额
        $newBalance = $this->coachModel->getBalance($coachId);

        return jsonSuccess([
            'student_phone'   => $studentPhone,
            'amount'         => $deductAmount,
            'is_self_invited' => $isSelfInvited,
            'expire_at'      => $expireAt,
            'balance'        => $newBalance
        ], '激活成功');
    }

    /**
     * 获取激活记录列表
     * GET /api/coach/activation_list
     */
    public function activationList()
    {
        $coachId = $this->getCurrentCoachId();
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
        $coachId = $this->getCurrentCoachId();
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
