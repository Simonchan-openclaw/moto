<?php
namespace app\controller\coach;

use app\model\Coach as CoachModel;
use app\model\RechargeRecord as RechargeRecordModel;
use app\model\StudentActivation as StudentActivationModel;
use Endroid\QrCode\QrCode;
use Endroid\QrCode\Writer\PngWriter;
use think\Env;
use think\facade\Log;
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

        $result = $this->coachModel->verifyLogin($phone, $password);

        if (!$result['success']) {
            return jsonError($result['message']);
        }

        $coach = $result['coach'];

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
     * 实际支付金额 = 充值金额 ÷ 0.994
     */
    public function recharge()
    {
        $coachId = $this->getCurrentCoachId();
        $amount = floatval(input('post.amount', 0));
        $payMethod = intval(input('post.pay_method', 1)); // 1=微信, 2=支付宝

        $minAmount = 18.00;

        if ($amount < $minAmount) {
            return jsonError("最低充值金额为 {$minAmount} 元");
        }

        // 计算实际支付金额 (扣除0.6%通道费后，平台收到的是充值金额)
        // 即：充值金额 = 实际支付金额 * 0.994
        // 所以：实际支付金额 = 充值金额 / 0.994
        $actualPayAmount = round($amount / 0.994, 2);

        // 生成订单号
        $tradeNo = 'C' . $coachId . date('YmdHis') . rand(100, 999);

        // 支付方式映射
        $payTypeMap = [
            1 => 'wxpay',   // 微信
            2 => 'alipay',  // 支付宝
        ];
        $payType = $payTypeMap[$payMethod] ?? 'wxpay';

        // 易支付配置
        $pid = config('payment.yipay.pid', '1006');
        $key = config('payment.yipay.key', 'sMxhHZTTwHwssbWBLLbSGXmm9T2x2g94');
        $notifyUrl = request()->domain() . '/api/coach/rechargeNotify';
        $returnUrl = request()->domain() . '/h5/coach.html?page=recharge-success';

        // 签名参数
        $params = [
            'pid' => $pid,
            'type' => $payType,
            'out_trade_no' => $tradeNo,
            'notify_url' => $notifyUrl,
            'return_url' => $returnUrl,
            'name' => 'Coach Balance Recharge',
            'money' => strval($actualPayAmount),
            'clientip' => request()->ip(),
            'device' => 'mobile',
            'param' => $coachId,
        ];

        // 生成签名（SDK标准格式）
        ksort($params);
        reset($params);
        $signStr = '';
        foreach ($params as $k => $v) {
            if ($k != 'sign' && $k != 'sign_type' && $v != '') {
                $signStr .= $k . '=' . $v . '&';
            }
        }
        $signStr .= 'key=' . $key;
        $sign = md5($signStr);
        
        // 日志记录
        Log::info('【易支付充值】教练ID:' . $coachId . ',充值金额:' . $amount . ',实付金额:' . $actualPayAmount . ',订单号:' . $tradeNo . ',签名字符串:' . $signStr . ',签名:' . $sign);

        // 调用易支付API
        $apiUrl = 'https://icu.zd16688.com/mapi.php';
        $params['sign'] = $sign;
        $params['sign_type'] = 'MD5';

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $apiUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
        $response = curl_exec($ch);
        curl_close($ch);

        $payResult = json_decode($response, true);

        if ($payResult && isset($payResult['code']) && $payResult['code'] == 1) {
            // 创建充值记录（待支付状态）
            $this->rechargeModel->create($coachId, $amount, $payMethod, $tradeNo, 0);
            
            Log::info('【易支付】发起支付成功,订单号:'.$tradeNo.',返回:'.json_encode($payResult));

            return jsonSuccess([
                'trade_no' => $tradeNo,
                'amount' => $amount,
                'actual_pay' => $actualPayAmount,
                'payurl' => $payResult['payurl'] ?? '',
                'qrcode' => $payResult['qrcode'] ?? '',
                'message' => '正在跳转支付...'
            ]);
        } else {
            return jsonError('发起支付失败：' . ($payResult['msg'] ?? '未知错误'));
        }
    }

    /**
     * 充值异步回调
     * GET /api/coach/rechargeNotify
     */
    public function rechargeNotify()
    {
        $pid = config('payment.yipay.pid', '1006');
        $key = config('payment.yipay.key', 'sMxhHZTTwHwssbWBLLbSGXmm9T2x2g94');

        // 接收回调参数
        $trade_no = input('get.trade_no/s', '');
        $out_trade_no = input('get.out_trade_no/s', '');
        $pay_type = input('get.type/s', '');
        $pay_money = input('get.money/f', 0);
        $trade_status = input('get.trade_status/s', '');
        $param = input('get.param/s', '');
        $sign = input('get.sign/s', '');

        // 验证签名
        $params = [
            'pid' => $pid,
            'trade_no' => $trade_no,
            'out_trade_no' => $out_trade_no,
            'type' => $pay_type,
            'money' => $pay_money,
            'trade_status' => $trade_status,
        ];
        ksort($params);
        $signStr = '';
        foreach ($params as $k => $v) {
            if ($v !== '' && $k != 'sign' && $k != 'sign_type') {
                $signStr .= $k . '=' . $v . '&';
            }
        }
        $signStr .= 'key=' . $key;
        $checkSign = md5($signStr);

        if ($sign != $checkSign) {
            Log::error('【易支付回调】签名验证失败,订单号:'.$out_trade_no);
            return 'fail';
        }

        Log::info('【易支付回调】收到回调,订单号:'.$out_trade_no.',状态:'.$trade_status.',金额:'.$pay_money);

        // 验证支付状态
        if ($trade_status == 'TRADE_SUCCESS') {
            // 从订单号获取教练ID
            preg_match('/^C(\d+)/', $out_trade_no, $matches);
            $coachId = isset($matches[1]) ? intval($matches[1]) : 0;

            if ($coachId > 0) {
                // 获取充值记录
                $record = \think\facade\Db::name('recharge_record')
                    ->where('trade_no', $out_trade_no)
                    ->where('status', 0)
                    ->find();

                if ($record) {
                    // 更新充值记录为已支付
                    \think\facade\Db::name('recharge_record')
                        ->where('id', $record['id'])
                        ->update(['status' => 1, 'pay_time' => date('Y-m-d H:i:s')]);
                    
                    // 增加教练余额
                    $this->coachModel->addBalance($coachId, $record['amount']);
                    
                    Log::info('【易支付回调】充值成功,教练ID:'.$coachId.',订单号:'.$out_trade_no.',充值余额:'.$record['amount']);
                } else {
                    Log::error('【易支付回调】充值记录不存在或已处理,订单号:'.$out_trade_no);
                }
            }
        }

        return 'success';
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
