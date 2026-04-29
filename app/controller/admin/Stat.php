<?php
namespace app\controller\admin;

use think\facade\Db;

class Stat
{
    /**
     * 统计概览
     * GET /api/admin/stat/summary
     */
    public function summary()
    {
        // 用户总数
        $totalUsers = Db::query("SELECT COUNT(*) as cnt FROM user WHERE status = 1")[0]['cnt'] ?? 0;
        
        // 今日新增用户
        $todayUsers = Db::query(
            "SELECT COUNT(*) as cnt FROM user WHERE status = 1 AND DATE(create_time) = CURDATE()"
        )[0]['cnt'] ?? 0;

        // 题目总数
        $totalQuestions = Db::query(
            "SELECT COUNT(*) as cnt FROM question WHERE status = 1"
        )[0]['cnt'] ?? 0;

        // 考试记录总数
        $totalExams = Db::query("SELECT COUNT(*) as cnt FROM exam_record")[0]['cnt'] ?? 0;
        
        // 今日考试次数
        $todayExams = Db::query(
            "SELECT COUNT(*) as cnt FROM exam_record WHERE DATE(create_time) = CURDATE()"
        )[0]['cnt'] ?? 0;

        // 科目1平均分
        $avgScore1 = Db::query(
            "SELECT AVG(score) as avg FROM exam_record WHERE subject = 1"
        )[0]['avg'] ?? 0;

        // 科目4平均分
        $avgScore4 = Db::query(
            "SELECT AVG(score) as avg FROM exam_record WHERE subject = 4"
        )[0]['avg'] ?? 0;

        // 激活记录总数（来自activation_log）
        $activationCount = Db::query(
            "SELECT COUNT(*) as cnt FROM activation_log"
        )[0]['cnt'] ?? 0;

        return jsonSuccess([
            'total_users'      => $totalUsers,
            'today_users'      => $todayUsers,
            'total_questions'  => $totalQuestions,
            'total_exams'      => $totalExams,
            'today_exams'      => $todayExams,
            'avg_score_1'      => round($avgScore1, 2),
            'avg_score_4'      => round($avgScore4, 2),
            'activation_count' => $activationCount,
            'user_count'       => $totalUsers,
            'coach_count'      => Db::query("SELECT COUNT(*) as cnt FROM coach WHERE status = 1")[0]['cnt'] ?? 0,
            'question_count'   => $totalQuestions,
        ]);
    }
}
