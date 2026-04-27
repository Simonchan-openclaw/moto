<?php
/**
 * 管理后台题目控制器
 */

namespace Controller;

require_once __DIR__ . '/../library/Db.php';
require_once __DIR__ . '/../library/Response.php';
require_once __DIR__ . '/../model/QuestionModel.php';

class AdminQuestionController
{
    private $db;
    private $model;

    public function __construct()
    {
        $this->db = \Db::getInstance();
        $this->model = new \QuestionModel();
    }

    /**
     * 验证管理员登录
     */
    private function authAdmin()
    {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (strpos($token, 'Bearer ') === 0) {
            $adminId = intval(str_replace('Bearer ', '', $token));
            if ($adminId > 0) {
                return $adminId;
            }
        }

        Response::unauthorized('请先登录');
    }

    /**
     * 后台题目列表
     * GET /api/admin/question/list
     */
    public function list()
    {
        $this->authAdmin();

        $page = intval($_GET['page'] ?? 1);
        $pageSize = intval($_GET['page_size'] ?? 20);
        $subject = isset($_GET['subject']) ? intval($_GET['subject']) : null;
        $questionType = isset($_GET['question_type']) ? intval($_GET['question_type']) : null;
        $keyword = $_GET['keyword'] ?? '';
        $status = isset($_GET['status']) ? intval($_GET['status']) : null;

        $pageSize = min($pageSize, 100);

        $result = $this->model->getAdminList([
            'page'          => $page,
            'page_size'    => $pageSize,
            'subject'      => $subject,
            'question_type'=> $questionType,
            'keyword'      => $keyword,
            'status'       => $status
        ]);

        Response::success($result);
    }

    /**
     * Excel批量导入题目
     * POST /api/admin/question/import
     */
    public function import()
    {
        $this->authAdmin();

        // 检查文件上传
        if (!isset($_FILES['file']) || $_FILES['file']['error'] !== UPLOAD_ERR_OK) {
            Response::error('文件上传失败');
        }

        $file = $_FILES['file'];

        // 检查文件类型
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, ['xlsx', 'xls'])) {
            Response::error('只支持 Excel 文件');
        }

        // TODO: 实际应使用 PhpSpreadsheet 库解析 Excel
        // 这里简化处理，模拟导入成功
        $importCount = 0;
        $errors = [];

        Response::success([
            'success' => true,
            'count'   => $importCount,
            'errors'  => $errors
        ], '导入完成');
    }
}
