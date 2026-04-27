<?php
namespace app\controller\student;

use app\model\StudentActivation as StudentActivationModel;

class Student
{
    protected $model;

    public function __construct()
    {
        $this->model = new StudentActivationModel();
    }

    /**
     * 激活（绑定设备）
     * POST /api/student/activate
     */
    public function activate()
    {
        $userId = getCurrentUserId();
        $data = input('post.');
        
        $activateCode = $data['activate_code'] ?? '';
        $deviceId = $data['device_id'] ?? '';

        if (empty($activateCode)) {
            return jsonError('激活码不能为空');
        }

        if (empty($deviceId)) {
            return jsonError('设备ID不能为空');
        }

        if (strlen($deviceId) < 16) {
            return jsonError('无效的设备ID');
        }

        $result = $this->model->activate($activateCode, $deviceId, $userId);

        if (!$result['success']) {
            return jsonError($result['message']);
        }

        return jsonSuccess([
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
        $userId = getCurrentUserId();
        $deviceId = input('get.device_id', '');

        if (empty($deviceId)) {
            return jsonError('设备ID不能为空');
        }

        $result = $this->model->checkUserActivation($userId, $deviceId);

        return jsonSuccess($result);
    }

    /**
     * 获取激活码信息
     * POST /api/student/verify_code
     */
    public function verifyCode()
    {
        $data = input('post.');
        $activateCode = $data['activate_code'] ?? '';

        if (empty($activateCode)) {
            return jsonError('激活码不能为空');
        }

        $record = \think\facade\Db::query(
            "SELECT sa.*, c.real_name as coach_name FROM student_activation sa
             LEFT JOIN coach c ON sa.coach_id = c.id
             WHERE sa.activate_code = ?",
            [$activateCode]
        );

        if (!$record) {
            return jsonError('激活码无效');
        }

        $record = $record[0];
        $statusText = [0 => '待激活', 1 => '已激活', 2 => '已失效', 3 => '已退款'];
        $status = $record['activate_status'];

        // 检查是否过期
        $expired = false;
        if ($status == 0 && strtotime($record['expire_at']) < time()) {
            $expired = true;
        }

        return jsonSuccess([
            'status'       => $expired ? 2 : $status,
            'status_text'  => $expired ? '已过期' : ($statusText[$status] ?? '未知'),
            'expire_at'    => $record['expire_at'],
            'coach_name'   => $record['coach_name'] ?? '',
            'amount'       => $record['amount_deducted'],
            'message'      => $expired ? '激活码已过期' : ($status == 0 ? '激活码有效，可激活' : "激活码已{$statusText[$status]}")
        ]);
    }
}
