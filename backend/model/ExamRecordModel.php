<?php
/**
 * 考试成绩模型
 */

namespace Model;

require_once __DIR__ . '/../library/Db.php';

class ExamRecordModel
{
    private $db;
    private $table = 'exam_record';

    public function __construct()
    {
        $this->db = \Db::getInstance();
    }

    /**
     * 创建考试记录
     */
    public function create($userId, $subject, $score, $totalQuestions, $correctCount, $timeUsed)
    {
        $this->db->execute(
            "INSERT INTO {$this->table} (user_id, subject, score, total_questions, correct_count, time_used) VALUES (?, ?, ?, ?, ?, ?)",
            [$userId, $subject, $score, $totalQuestions, $correctCount, $timeUsed]
        );

        return $this->db->lastInsertId();
    }

    /**
     * 获取考试记录列表
     */
    public function getList($userId, $page = 1, $pageSize = 20)
    {
        $offset = ($page - 1) * $pageSize;

        // 获取总数
        $total = $this->db->fetchColumn(
            "SELECT COUNT(*) FROM {$this->table} WHERE user_id = ?",
            [$userId]
        );

        // 获取列表
        $list = $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY submit_time DESC LIMIT ? OFFSET ?",
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
     * 获取考试记录详情
     */
    public function getDetail($recordId, $userId)
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE id = ? AND user_id = ?",
            [$recordId, $userId]
        );
    }
}
