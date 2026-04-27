<?php
namespace app\model;

use think\facade\Db;

class UserAnswer
{
    protected $table = 'user_answer';

    /**
     * 创建答题记录
     */
    public function create($userId, $questionId, $userAnswer, $isCorrect, $answerTime = 0)
    {
        Db::execute(
            "INSERT INTO {$this->table} (user_id, question_id, user_answer, is_correct, answer_time, create_time) 
             VALUES (?, ?, ?, ?, ?, NOW())",
            [$userId, $questionId, $userAnswer, $isCorrect ? 1 : 0, $answerTime]
        );
        return Db::getLastInsID();
    }
}
