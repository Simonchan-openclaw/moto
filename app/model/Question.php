<?php
namespace app\model;

use think\Model;
use think\facade\Db;

class Question extends Model
{
    protected $name = 'question';
    protected $pk = 'id';

    /**
     * 获取题目列表
     */
    public function getList($params = [])
    {
        $where = ['status = 1'];
        $whereParams = [];

        if (!empty($params['subject'])) {
            $where[] = 'subject = ?';
            $whereParams[] = $params['subject'];
        }

        if (!empty($params['question_type'])) {
            $where[] = 'question_type = ?';
            $whereParams[] = $params['question_type'];
        }

        if (!empty($params['chapter_id'])) {
            $where[] = 'chapter_id = ?';
            $whereParams[] = $params['chapter_id'];
        }

        if (!empty($params['keyword'])) {
            $where[] = '(content LIKE ? OR keywords LIKE ?)';
            $whereParams[] = '%' . $params['keyword'] . '%';
            $whereParams[] = '%' . $params['keyword'] . '%';
        }

        $page = max(1, $params['page'] ?? 1);
        $pageSize = min(50, max(1, $params['page_size'] ?? 20));
        $offset = ($page - 1) * $pageSize;

        $whereSql = implode(' AND ', $where);

        // 获取总数
        $total = Db::query(
            "SELECT COUNT(*) as cnt FROM {$this->name} WHERE {$whereSql}",
            $whereParams
        )[0]['cnt'] ?? 0;

        // 获取列表
        $list = Db::query(
            "SELECT q.*, c.name as chapter_name 
             FROM {$this->name} q 
             LEFT JOIN chapter c ON q.chapter_id = c.id 
             WHERE {$whereSql} 
             ORDER BY q.id DESC 
             LIMIT ? OFFSET ?",
            array_merge($whereParams, [$pageSize, $offset])
        );

        // 处理选项
        foreach ($list as &$item) {
            $item['options'] = $this->formatOptions($item);
            $item['content'] = $item['title'];
            unset($item['title'], $item['option_a'], $item['option_b'], $item['option_c'], $item['option_d']);
        }

        return [
            'list'        => $list,
            'total'       => $total,
            'page'        => $page,
            'page_size'   => $pageSize,
            'total_pages' => ceil($total / $pageSize)
        ];
    }

    /**
     * 获取题目详情
     */
    public function getDetail($questionId)
    {
        $question = Db::query(
            "SELECT q.*, c.name as chapter_name 
             FROM {$this->name} q 
             LEFT JOIN chapter c ON q.chapter_id = c.id 
             WHERE q.id = ?",
            [$questionId]
        );

        if (empty($question)) {
            return null;
        }

        $question = $question[0];
        $question['content'] = $question['title'];
        $question['options'] = $this->formatOptions($question);
        unset($question['title']);

        return $question;
    }

    /**
     * 批量获取题目详情
     */
    public function getDetails($questionIds)
    {
        if (empty($questionIds)) {
            return [];
        }

        $placeholders = implode(',', array_fill(0, count($questionIds), '?'));
        $questions = Db::query(
            "SELECT * FROM {$this->name} WHERE id IN ({$placeholders}) AND status = 1",
            $questionIds
        );

        return $questions;
    }

    /**
     * 获取随机题目
     */
    public function getRandomQuestions($subject, $count)
    {
        $questions = Db::query(
            "SELECT id FROM {$this->name} WHERE subject = ? AND status = 1 ORDER BY RAND() LIMIT ?",
            [$subject, $count]
        );

        $ids = array_column($questions, 'id');
        return $this->getDetails($ids);
    }

    /**
     * 格式化选项
     */
    private function formatOptions($question)
    {
        $options = [];

        if (!empty($question['option_a'])) {
            $options[] = ['option_key' => 'A', 'option_content' => $question['option_a']];
        }
        if (!empty($question['option_b'])) {
            $options[] = ['option_key' => 'B', 'option_content' => $question['option_b']];
        }
        if (!empty($question['option_c'])) {
            $options[] = ['option_key' => 'C', 'option_content' => $question['option_c']];
        }
        if (!empty($question['option_d'])) {
            $options[] = ['option_key' => 'D', 'option_content' => $question['option_d']];
        }

        return $options;
    }

    /**
     * 后台题目列表
     */
    public function getAdminList($params = [])
    {
        $where = [];
        $whereParams = [];

        if (!empty($params['subject'])) {
            $where[] = 'q.subject = ?';
            $whereParams[] = $params['subject'];
        }

        if (!empty($params['question_type'])) {
            $where[] = 'q.question_type = ?';
            $whereParams[] = $params['question_type'];
        }

        if (!empty($params['keyword'])) {
            $where[] = '(q.title LIKE ? OR q.keywords LIKE ?)';
            $whereParams[] = '%' . $params['keyword'] . '%';
            $whereParams[] = '%' . $params['keyword'] . '%';
        }

        if ($params['status'] !== null) {
            $where[] = 'q.status = ?';
            $whereParams[] = $params['status'];
        }

        $page = max(1, $params['page'] ?? 1);
        $pageSize = min(100, max(1, $params['page_size'] ?? 20));
        $offset = ($page - 1) * $pageSize;

        $whereSql = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        // 获取总数
        $total = Db::query(
            "SELECT COUNT(*) as cnt FROM {$this->name} q {$whereSql}",
            $whereParams
        )[0]['cnt'] ?? 0;

        // 获取列表
        $list = Db::query(
            "SELECT q.*, c.name as chapter_name 
             FROM {$this->name} q 
             LEFT JOIN chapter c ON q.chapter_id = c.id 
             {$whereSql}
             ORDER BY q.id DESC 
             LIMIT ? OFFSET ?",
            array_merge($whereParams, [$pageSize, $offset])
        );

        // 处理选项
        foreach ($list as &$item) {
            $item['content'] = $item['title'];
            $item['options'] = $this->formatOptions($item);
            unset($item['title']);
        }

        return [
            'list'        => $list,
            'total'       => $total,
            'page'        => $page,
            'page_size'   => $pageSize,
            'total_pages' => ceil($total / $pageSize)
        ];
    }
}
