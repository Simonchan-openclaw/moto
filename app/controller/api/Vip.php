<?php
namespace app\controller\api;

use app\model\StudentActivation as StudentActivationModel;

class Vip
{
    protected $model;

    public function __construct()
    {
        $this->model = new StudentActivationModel();
    }

    /**
     * 获取VIP状态
     * GET /api/vip/status
     */
    public function status()
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
     * 激活VIP
     * POST /api/vip/activate
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
}
