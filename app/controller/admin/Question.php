<?php
namespace app\controller\admin;

use app\model\Question as QuestionModel;

class Question
{
    protected $model;

    public function __construct()
    {
        $this->model = new QuestionModel();
    }

    /**
     * 后台题目列表
     * POST /api/admin/question/list
     */
    public function list()
    {
        $page = input('post.page/d', 1);
        $pageSize = input('post.page_size/d', 20);
        $subject = input('post.subject/d', null);
        $questionType = input('post.question_type/d', null);
        $keyword = input('post.keyword', '');
        $status = input('post.status/d', null);

        $pageSize = min($pageSize, 100);

        $result = $this->model->getAdminList([
            'page'          => $page,
            'page_size'    => $pageSize,
            'subject'      => $subject,
            'question_type'=> $questionType,
            'keyword'      => $keyword,
            'status'       => $status
        ]);

        return jsonSuccess($result);
    }

    /**
     * Excel批量导入题目
     * POST /api/admin/question/import
     */
    public function import()
    {
        // 检查文件上传
        $file = request()->file('file');
        
        if (!$file) {
            return jsonError('文件上传失败');
        }

        // 检查文件类型
        $ext = strtolower($file->getExtension());
        if (!in_array($ext, ['xlsx', 'xls'])) {
            return jsonError('只支持 Excel 文件');
        }

        // TODO: 实际应使用 PhpSpreadsheet 库解析 Excel
        // 这里简化处理，模拟导入成功
        $importCount = 0;
        $errors = [];

        return jsonSuccess([
            'success' => true,
            'count'   => $importCount,
            'errors'  => $errors
        ], '导入完成');
    }

    /**
     * 添加题目
     * POST /api/admin/question/add
     */
    public function add()
    {
        $data = input('post.');
        
        // 验证必填字段
        if (empty($data['content']) || empty($data['answer'])) {
            return jsonError('题目内容和答案不能为空');
        }

        // TODO: 实现添加逻辑
        return jsonSuccess(['id' => 0], '添加成功');
    }

    /**
     * 编辑题目
     * POST /api/admin/question/edit
     */
    public function edit()
    {
        $data = input('post.');
        
        if (empty($data['id'])) {
            return jsonError('题目ID不能为空');
        }

        // TODO: 实现编辑逻辑
        return jsonSuccess(['success' => true], '编辑成功');
    }

    /**
     * 删除题目
     * POST /api/admin/question/delete
     */
    public function delete()
    {
        $id = input('post.id/d', 0);
        
        if ($id <= 0) {
            return jsonError('题目ID不能为空');
        }

        // TODO: 实现删除逻辑（软删除）
        return jsonSuccess(['success' => true], '删除成功');
    }
}
