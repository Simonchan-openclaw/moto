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

        // 今日激活次数
        $todayActivation = Db::query(
            "SELECT COUNT(*) as cnt FROM activation_log WHERE DATE(create_time) = CURDATE()"
        )[0]['cnt'] ?? 0;

        // 本周激活次数
        $weekActivation = Db::query(
            "SELECT COUNT(*) as cnt FROM activation_log WHERE YEARWEEK(create_time, 1) = YEARWEEK(CURDATE(), 1)"
        )[0]['cnt'] ?? 0;

        // 激活总额（教练总扣款）
        $totalActivationAmount = Db::query(
            "SELECT COALESCE(SUM(amount), 0) as total FROM activation_log"
        )[0]['total'] ?? 0;

        // 近7天激活趋势
        $trendResult = Db::query(
            "SELECT DATE(create_time) as date, COUNT(*) as count 
             FROM activation_log 
             WHERE create_time >= DATE_SUB(CURDATE(), INTERVAL 7 DAY)
             GROUP BY DATE(create_time)
             ORDER BY date ASC"
        );
        
        // 构建7天趋势数组
        $trend = [];
        for ($i = 6; $i >= 0; $i--) {
            $date = date('Y-m-d', strtotime("-{$i} days"));
            $count = 0;
            foreach ($trendResult as $row) {
                if ($row['date'] == $date) {
                    $count = $row['count'];
                    break;
                }
            }
            $trend[] = ['date' => $date, 'count' => $count];
        }

        return jsonSuccess([
            'total_users'          => $totalUsers,
            'today_users'          => $todayUsers,
            'total_questions'      => $totalQuestions,
            'total_exams'          => $totalExams,
            'today_exams'          => $todayExams,
            'avg_score_1'          => round($avgScore1, 2),
            'avg_score_4'          => round($avgScore4, 2),
            'activation_count'    => $activationCount,
            'today_activation'    => $todayActivation,
            'week_activation'     => $weekActivation,
            'total_activation_amount' => $totalActivationAmount,
            'activation_trend'    => $trend,
            'user_count'          => $totalUsers,
            'coach_count'         => Db::query("SELECT COUNT(*) as cnt FROM coach WHERE status = 1")[0]['cnt'] ?? 0,
            'question_count'      => $totalQuestions,
        ]);
    }
}
