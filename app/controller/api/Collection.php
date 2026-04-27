<?php
namespace app\controller\api;

use app\model\Collection as CollectionModel;

class Collection
{
    protected $model;

    public function __construct()
    {
        $this->model = new CollectionModel();
    }

    /**
     * 收藏/取消收藏
     * POST /api/collection/toggle
     */
    public function toggle()
    {
        $userId = getCurrentUserId();
        $data = input('post.');
        
        $questionId = intval($data['question_id'] ?? 0);
        $status = intval($data['status'] ?? 1);

        if ($questionId <= 0) {
            return jsonError('题目ID不能为空');
        }

        $this->model->toggle($userId, $questionId, $status);

        $message = $status == 1 ? '收藏成功' : '已取消收藏';

        return jsonSuccess(['success' => true], $message);
    }

    /**
     * 获取收藏列表
     * GET /api/collection/list
     */
    public function list()
    {
        $userId = getCurrentUserId();
        $page = input('get.page/d', 1);
        $pageSize = input('get.page_size/d', 20);

        $pageSize = min($pageSize, 50);

        $result = $this->model->getList($userId, $page, $pageSize);

        return jsonSuccess($result);
    }
}
