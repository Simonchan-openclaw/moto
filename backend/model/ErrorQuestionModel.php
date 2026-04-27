<?php
/**
 * 错题模型
 */

namespace Model;

require_once __DIR__ . '/../library/Db.php';

class ErrorQuestionModel
{
    private $db;
    private $table = 'error_question';

    public function __construct()
    {
        $this->db = \Db::getInstance();
    }

    /**
     * 添加错题
     */
    public function add($userId, $questionId)
    {
        // 检查是否已存在
        $exists = $this->db->fetch(
            "SELECT id, error_count FROM {$this->table} WHERE user_id = ? AND question_id = ?",
            [$userId, $questionId]
        );

        if ($exists) {
            // 更新错题次数
            $this->db->execute(
                "UPDATE {$this->table} SET error_count = error_count + 1, updated_at = NOW() WHERE id = ?",
                [$exists['id']]
            );
        } else {
            // 新增错题
            $this->db->execute(
                "INSERT INTO {$this->table} (user_id, question_id) VALUES (?, ?)",
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

        // 获取总数
        $total = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM {$this->table} WHERE user_id = ? AND is_mastered = 0",
            [$userId]
        );

        // 获取列表
        $sql = "SELECT eq.*, q.subject, q.question_type, q.chapter_id, c.name as chapter_name,
                       q.title as content, q.option_a, q.option_b, q.option_c, q.option_d, q.answer, q.analysis
                FROM {$this->table} eq
                LEFT JOIN question q ON eq.question_id = q.id
                LEFT JOIN chapter c ON q.chapter_id = c.id
                WHERE eq.user_id = ? AND eq.is_mastered = 0
                ORDER BY eq.updated_at DESC
                LIMIT ? OFFSET ?";

        $list = $this->db->fetchAll($sql, [$userId, $pageSize, $offset]);

        // 处理选项
        foreach ($list as &$item) {
            $item['content'] = $item['title'];
            $item['options'] = $this->formatOptions($item);
            $item['wrong_count'] = $item['error_count'];
            $item['last_wrong_time'] = $item['updated_at'];
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

    /**
     * 清空错题本
     */
    public function clear($userId)
    {
        return $this->db->execute(
            "DELETE FROM {$this->table} WHERE user_id = ?",
            [$userId]
        );
    }

    /**
     * 标记为已掌握
     */
    public function markMastered($userId, $questionId)
    {
        return $this->db->execute(
            "UPDATE {$this->table} SET is_mastered = 1 WHERE user_id = ? AND question_id = ?",
            [$userId, $questionId]
        );
    }
}
