<?php
namespace app\controller\api;

use app\model\Question as QuestionModel;
use app\model\Chapter as ChapterModel;
use app\model\Collection as CollectionModel;

class Question
{
    protected $questionModel;
    protected $chapterModel;
    protected $collectionModel;

    public function __construct()
    {
        $this->questionModel = new QuestionModel();
        $this->chapterModel = new ChapterModel();
        $this->collectionModel = new CollectionModel();
    }

    /**
     * 获取章节列表
     * GET /api/question/chapters
     */
    public function chapters()
    {
        $subject = input('get.subject/d', 1);

        if (!in_array($subject, [1, 4])) {
            return jsonError('科目参数不正确');
        }

        $chapters = $this->chapterModel->getListBySubject($subject);

        // 转换为树形结构
        $tree = $this->buildTree($chapters);

        return jsonSuccess($tree);
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
    public function list()
    {
        $subject = input('get.subject/d', null);
        $questionType = input('get.question_type/d', null);
        $chapterId = input('get.chapter_id/d', null);
        $keyword = input('get.keyword', '');
        $page = input('get.page/d', 1);
        $pageSize = input('get.page_size/d', 20);

        // 获取用户ID（如果有）
        $userId = getCurrentUserId();

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

        return jsonSuccess($result);
    }

    /**
     * 获取题目详情
     * GET /api/question/detail
     */
    public function detail()
    {
        $questionId = input('get.id/d', 0);

        if ($questionId <= 0) {
            return jsonError('题目ID不能为空');
        }

        $userId = getCurrentUserId();

        $question = $this->questionModel->getDetail($questionId);

        if (!$question) {
            return jsonError('题目不存在');
        }

        // 检查是否已收藏
        $question['is_collected'] = $this->collectionModel->isCollected($userId, $questionId);

        return jsonSuccess($question);
    }
}
