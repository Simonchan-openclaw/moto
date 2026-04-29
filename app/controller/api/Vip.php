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
     * VIP状态查询
     * 通过JWT token验证用户身份
     * GET /api/vip/status
     */
    public function status()
    {
        $userId = $this->getCurrentUserId();

        if (!$userId) {
            return jsonError('用户未登录', 401);
        }

        $result = $this->model->checkUserActivation($userId);

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

    /**
     * 获取当前用户ID（从token）
     */
    protected function getCurrentUserId()
    {
        $token = request()->header('Authorization', '');
        if (empty($token)) {
            return 0;
        }
        $token = str_replace('Bearer ', '', $token);
        return getUserIdFromToken($token);
    }
}
