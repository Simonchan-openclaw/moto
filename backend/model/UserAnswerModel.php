<?php
/**
 * 用户答题记录模型
 */

namespace Model;

require_once __DIR__ . '/../library/Db.php';

class UserAnswerModel
{
    private $db;
    private $table = 'user_answer';

    public function __construct()
    {
        $this->db = \Db::getInstance();
    }

    /**
     * 创建答题记录
     */
    public function create($userId, $questionId, $userAnswer, $isCorrect, $answerTime)
    {
        return $this->db->execute(
            "INSERT INTO {$this->table} (user_id, question_id, user_answer, is_correct, answer_time) VALUES (?, ?, ?, ?, ?)",
            [$userId, $questionId, $userAnswer, $isCorrect ? 1 : 0, $answerTime]
        );
    }

    /**
     * 获取用户答题统计
     */
    public function getStats($userId)
    {
        return $this->db->fetch(
            "SELECT 
                COUNT(*) as total,
                SUM(is_correct) as correct,
                AVG(answer_time) as avg_time
             FROM {$this->table} WHERE user_id = ?",
            [$userId]
        );
    }
}
