<?php
namespace app\model;

use think\facade\Db;

class ErrorQuestion
{
    protected $table = 'error_question';

    /**
     * 添加错题
     */
    public function add($userId, $questionId)
    {
        // 检查是否已存在
        $exists = Db::query(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE user_id = ? AND question_id = ?",
            [$userId, $questionId]
        )[0]['cnt'] ?? 0;

        if (!$exists) {
            Db::execute(
                "INSERT INTO {$this->table} (user_id, question_id, created_at) VALUES (?, ?, NOW())",
                [$userId, $questionId]
            );
        }
    }

    /**
     * 获取错题列表
     */
    public function getList($userId, $page = 1, $pageSize = 20)
    {
        $offset = ($page - 1) * $pageSize;

        $total = Db::query(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE user_id = ?",
            [$userId]
        )[0]['cnt'] ?? 0;

        $list = Db::query(
            "SELECT e.*, q.title as question_title, q.subject, q.question_type, q.answer, q.analysis 
             FROM {$this->table} e 
             LEFT JOIN question q ON e.question_id = q.id 
             WHERE e.user_id = ? 
             ORDER BY e.id DESC 
             LIMIT ? OFFSET ?",
            [$userId, $pageSize, $offset]
        );

        return [
            'list'        => $list,
            'total'       => $total,
            'page'        => $page,
            'page_size'   => $pageSize,
            'total_pages' => ceil($total / $pageSize)
        ];
    }

    /**
     * 清空错题本
     */
    public function clear($userId)
    {
        Db::execute("DELETE FROM {$this->table} WHERE user_id = ?", [$userId]);
    }
}
