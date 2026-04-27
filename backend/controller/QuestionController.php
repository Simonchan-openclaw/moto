<?php
/**
 * 题目控制器
 */

namespace Controller;

require_once __DIR__ . '/../library/Db.php';
require_once __DIR__ . '/../library/Response.php';
require_once __DIR__ . '/../model/QuestionModel.php';
require_once __DIR__ . '/../model/ChapterModel.php';
require_once __DIR__ . '/../model/CollectionModel.php';

class QuestionController
{
    private $db;
    private $questionModel;
    private $chapterModel;
    private $collectionModel;

    public function __construct()
    {
        $this->db = \Db::getInstance();
        $this->questionModel = new \QuestionModel();
        $this->chapterModel = new \ChapterModel();
        $this->collectionModel = new \CollectionModel();
    }

    /**
     * 获取章节列表
     * GET /api/question/chapters
     */
    public function getChapters()
    {
        $subject = intval($_GET['subject'] ?? 1);

        if (!in_array($subject, [1, 4])) {
            Response::error('科目参数不正确');
        }

        $chapters = $this->chapterModel->getListBySubject($subject);

        // 转换为树形结构
        $tree = $this->buildTree($chapters);

        Response::success($tree);
    }

    /**
     * 递归构建树形结构
     */
    private function buildTree($items, $parentId = 0)
    {
        $tree = [];

        foreach ($items as $item) {
            if ($item['parent_id'] == $parentId) {
                $node = [
                    'chapter_id'   => $item['id'],
                    'chapter_name' => $item['name'],
                    'subject'      => $item['subject'],
                    'parent_id'    => $item['parent_id'],
                    'sort_order'   => $item['sort'],
                    'children'     => $this->buildTree($items, $item['id'])
                ];
                $tree[] = $node;
            }
        }

        return $tree;
    }

    /**
     * 获取题目列表
     * GET /api/question/list
     */
    public function getList()
    {
        $subject = isset($_GET['subject']) ? intval($_GET['subject']) : null;
        $questionType = isset($_GET['question_type']) ? intval($_GET['question_type']) : null;
        $chapterId = isset($_GET['chapter_id']) ? intval($_GET['chapter_id']) : null;
        $keyword = $_GET['keyword'] ?? '';
        $page = intval($_GET['page'] ?? 1);
        $pageSize = intval($_GET['page_size'] ?? 20);

        // 获取用户ID（如果有）
        $userId = $this->getUserId();

        // 限制页大小
        $pageSize = min($pageSize, 50);

        $result = $this->questionModel->getList([
            'subject'      => $subject,
            'question_type'=> $questionType,
            'chapter_id'   => $chapterId,
            'keyword'      => $keyword,
            'page'         => $page,
            'page_size'    => $pageSize,
            'user_id'      => $userId
        ]);

        Response::success($result);
    }

    /**
     * 获取题目详情
     * GET /api/question/detail
     */
    public function getDetail()
    {
        $questionId = intval($_GET['id'] ?? 0);

        if ($questionId <= 0) {
            Response::error('题目ID不能为空');
        }

        $userId = $this->getUserId();

        $question = $this->questionModel->getDetail($questionId);

        if (!$question) {
            Response::error('题目不存在');
        }

        // 检查是否已收藏
        $question['is_collected'] = $this->collectionModel->isCollected($userId, $questionId);

        Response::success($question);
    }

    /**
     * 获取用户ID（如果有）
     */
    private function getUserId()
    {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (strpos($token, 'Bearer ') === 0) {
            return intval(str_replace('Bearer ', '', $token));
        }

        return 0;
    }
}
