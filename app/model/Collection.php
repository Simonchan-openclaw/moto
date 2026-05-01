<?php
namespace app\model;

use think\facade\Db;

class Collection
{
    protected $table = 'collection';

    /**
     * 检查是否已收藏
     */
    public function isCollected($userId, $questionId)
    {
        $result = Db::query(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE user_id = ? AND question_id = ?",
            [$userId, $questionId]
        );
        return ($result[0]['cnt'] ?? 0) > 0;
    }

    /**
     * 收藏/取消收藏
     */
    public function toggle($userId, $questionId, $status)
    {
        if ($status == 1) {
            // 添加收藏
            $exists = $this->isCollected($userId, $questionId);
            if (!$exists) {
                Db::execute(
                    "INSERT INTO {$this->table} (user_id, question_id, created_at) VALUES (?, ?, NOW())",
                    [$userId, $questionId]
                );
            }
        } else {
            // 取消收藏
            Db::execute(
                "DELETE FROM {$this->table} WHERE user_id = ? AND question_id = ?",
                [$userId, $questionId]
            );
        }
    }

    /**
     * 获取收藏列表
     */
    public function getList($userId, $page = 1, $pageSize = 20)
    {
        $offset = ($page - 1) * $pageSize;

        $total = Db::query(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE user_id = ?",
            [$userId]
        )[0]['cnt'] ?? 0;

        $list = Db::query(
            "SELECT c.*, q.title as question_title, q.subject, q.question_type 
             FROM {$this->table} c 
             LEFT JOIN question q ON c.question_id = q.id 
             WHERE c.user_id = ? 
             ORDER BY c.id DESC 
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
}
