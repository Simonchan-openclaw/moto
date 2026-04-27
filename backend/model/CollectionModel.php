<?php
/**
 * 收藏模型
 */

namespace Model;

require_once __DIR__ . '/../library/Db.php';

class CollectionModel
{
    private $db;
    private $table = 'collection';

    public function __construct()
    {
        $this->db = \Db::getInstance();
    }

    /**
     * 检查是否已收藏
     */
    public function isCollected($userId, $questionId)
    {
        $record = $this->db->fetch(
            "SELECT id FROM {$this->table} WHERE user_id = ? AND question_id = ? AND status = 1",
            [$userId, $questionId]
        );

        return $record ? true : false;
    }

    /**
     * 切换收藏状态
     */
    public function toggle($userId, $questionId, $status = 1)
    {
        $exists = $this->db->fetch(
            "SELECT id FROM {$this->table} WHERE user_id = ? AND question_id = ?",
            [$userId, $questionId]
        );

        if ($exists) {
            // 更新状态
            $this->db->execute(
                "UPDATE {$this->table} SET status = ? WHERE id = ?",
                [$status, $exists['id']]
            );
        } else {
            // 新增收藏
            $this->db->execute(
                "INSERT INTO {$this->table} (user_id, question_id, status) VALUES (?, ?, ?)",
                [$userId, $questionId, $status]
            );
        }
    }

    /**
     * 获取收藏列表
     */
    public function getList($userId, $page = 1, $pageSize = 20)
    {
        $offset = ($page - 1) * $pageSize;

        // 获取总数
        $total = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM {$this->table} WHERE user_id = ? AND status = 1",
            [$userId]
        );

        // 获取列表
        $sql = "SELECT col.*, q.subject, q.question_type, q.chapter_id, c.name as chapter_name,
                       q.title as content, q.option_a, q.option_b, q.option_c, q.option_d, q.answer, q.analysis
                FROM {$this->table} col
                LEFT JOIN question q ON col.question_id = q.id
                LEFT JOIN chapter c ON q.chapter_id = c.id
                WHERE col.user_id = ? AND col.status = 1
                ORDER BY col.created_at DESC
                LIMIT ? OFFSET ?";

        $list = $this->db->fetchAll($sql, [$userId, $pageSize, $offset]);

        // 处理选项
        foreach ($list as &$item) {
            $item['content'] = $item['title'];
            $item['options'] = $this->formatOptions($item);
            $item['create_time'] = $item['created_at'];
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
}
