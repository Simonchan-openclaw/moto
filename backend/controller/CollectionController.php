<?php
/**
 * 收藏控制器
 */

namespace Controller;

require_once __DIR__ . '/../library/Db.php';
require_once __DIR__ . '/../library/Response.php';
require_once __DIR__ . '/../model/CollectionModel.php';

class CollectionController
{
    private $db;
    private $model;

    public function __construct()
    {
        $this->db = \Db::getInstance();
        $this->model = new \CollectionModel();
    }

    /**
     * 验证用户登录
     */
    private function authUser()
    {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (strpos($token, 'Bearer ') === 0) {
            $userId = intval(str_replace('Bearer ', '', $token));
            if ($userId > 0) {
                return $userId;
            }
        }

        Response::unauthorized('请先登录');
    }

    /**
     * 收藏/取消收藏
     * POST /api/collection/toggle
     */
    public function toggle()
    {
        $userId = $this->authUser();

        $data = json_decode(file_get_contents('php://input'), true);

        $questionId = intval($data['question_id'] ?? 0);
        $status = intval($data['status'] ?? 1);

        if ($questionId <= 0) {
            Response::error('题目ID不能为空');
        }

        $this->model->toggle($userId, $questionId, $status);

        $message = $status == 1 ? '收藏成功' : '已取消收藏';

        Response::success(['success' => true], $message);
    }

    /**
     * 获取收藏列表
     * GET /api/collection/list
     */
    public function getList()
    {
        $userId = $this->authUser();
        $page = intval($_GET['page'] ?? 1);
        $pageSize = intval($_GET['page_size'] ?? 20);

        $pageSize = min($pageSize, 50);

        $result = $this->model->getList($userId, $page, $pageSize);

        Response::success($result);
    }
}
