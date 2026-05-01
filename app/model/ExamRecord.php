<?php
namespace app\model;

use think\facade\Db;

class ExamRecord
{
    protected $table = 'exam_record';

    /**
     * 创建考试记录
     */
    public function create($userId, $subject, $score, $totalQuestions, $correctCount, $timeUsed = 0)
    {
        $data = [
            'user_id'        => $userId,
            'subject'        => $subject,
            'score'          => $score,
            'total_questions'=> $totalQuestions,
            'correct_count'  => $correctCount,
            'time_used'      => $timeUsed,
            'create_time'    => date('Y-m-d H:i:s')
        ];
        
        return Db::name('exam_record')->insert($data, true);
    }

    /**
     * 获取考试记录列表
     */
    public function getList($userId, $page = 1, $pageSize = 20)
    {
        $offset = ($page - 1) * $pageSize;

        $total = Db::query(
            "SELECT COUNT(*) as cnt FROM {$this->table} WHERE user_id = ?",
            [$userId]
        )[0]['cnt'] ?? 0;

        $list = Db::query(
            "SELECT * FROM {$this->table} WHERE user_id = ? ORDER BY id DESC LIMIT ? OFFSET ?",
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
