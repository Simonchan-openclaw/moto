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
        $data = [
            'user_id'     => $userId,
            'question_id' => $questionId,
            'user_answer' => $userAnswer,
            'is_correct'  => $isCorrect ? 1 : 0,
            'answer_time' => $answerTime,
            'created_at'  => date('Y-m-d H:i:s')
        ];
        
        return Db::name('user_answer')->insert($data, true);
    }
}
