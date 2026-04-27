<?php
/**
 * 学员端激活控制器
 */

require_once __DIR__ . '/../model/StudentActivationModel.php';
require_once __DIR__ . '/../model/UserModel.php';
require_once __DIR__ . '/../library/Response.php';

class StudentActivationController
{
    private $activationModel;
    private $userModel;

    public function __construct()
    {
        $this->activationModel = new StudentActivationModel();
    }

    /**
     * 验证用户登录
     */
    private function authUser()
    {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (empty($token)) {
            Response::unauthorized('请先登录');
        }

        // 简化验证：实际应解析Token获取用户ID
        if (strpos($token, 'Bearer ') === 0) {
            $userId = intval(str_replace('Bearer ', '', $token));
            if ($userId <= 0) {
                Response::unauthorized('无效的登录状态');
            }
            return $userId;
        }

        Response::unauthorized('无效的登录状态');
    }

    /**
     * 激活（绑定设备）
     * POST /api/student/activate
     */
    public function activate()
    {
        $data = json_decode(file_get_contents('php://input'), true);

        $activateCode = $data['activate_code'] ?? '';
        $deviceId      = $data['device_id'] ?? '';
        $userId        = $this->authUser();

        if (empty($activateCode)) {
            Response::error('激活码不能为空');
        }

        if (empty($deviceId)) {
            Response::error('设备ID不能为空');
        }

        // 设备ID格式验证（应为32位UUID或类似格式）
        if (strlen($deviceId) < 16) {
            Response::error('无效的设备ID');
        }

        $result = $this->activationModel->activate($activateCode, $deviceId, $userId);

        if (!$result['success']) {
            Response::error($result['message']);
        }

        Response::success([
            'expire_at'   => $result['expire_at'],
            'coach_phone' => $result['coach_phone'],
            'message'     => '激活成功'
        ], '激活成功');
    }

    /**
     * 查询激活状态
     * GET /api/student/check
     */
    public function check()
    {
        $userId   = $this->authUser();
        $deviceId = $_GET['device_id'] ?? '';

        if (empty($deviceId)) {
            Response::error('设备ID不能为空');
        }

        $result = $this->activationModel->checkUserActivation($userId, $deviceId);

        Response::success($result);
    }

    /**
     * 获取激活码信息（验证激活码是否有效）
     * POST /api/student/verify_code
     */
    public function verifyCode()
    {
        $data = json_decode(file_get_contents('php://input'), true);
        $activateCode = $data['activate_code'] ?? '';

        if (empty($activateCode)) {
            Response::error('激活码不能为空');
        }

        require_once __DIR__ . '/../library/Db.php';
        $db = Db::getInstance();

        $record = $db->fetch(
            "SELECT sa.*, c.real_name as coach_name FROM student_activation sa
             LEFT JOIN coach c ON sa.coach_id = c.id
             WHERE sa.activate_code = ?",
            [$activateCode]
        );

        if (!$record) {
            Response::error('激活码无效');
        }

        $statusText = [0 => '待激活', 1 => '已激活', 2 => '已失效', 3 => '已退款'];
        $status = $record['activate_status'];

        // 检查是否过期
        $expired = false;
        if ($status == 0 && strtotime($record['expire_at']) < time()) {
            $expired = true;
            $statusText[0] = '已过期';
        }

        Response::success([
            'status'       => $expired ? 2 : $status,
            'status_text'  => $expired ? '已过期' : ($statusText[$status] ?? '未知'),
            'expire_at'    => $record['expire_at'],
            'coach_name'   => $record['coach_name'] ?? '',
            'amount'       => $record['amount_deducted'],
            'message'      => $expired ? '激活码已过期' : ($status == 0 ? '激活码有效，可激活' : "激活码已{$statusText[$status]}")
        ]);
    }
}
