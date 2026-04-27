<?php
/**
 * 考试控制器
 */

namespace Controller;

require_once __DIR__ . '/../library/Db.php';
require_once __DIR__ . '/../library/Response.php';
require_once __DIR__ . '/../model/ExamRecordModel.php';
require_once __DIR__ . '/../model/QuestionModel.php';

class ExamController
{
    private $db;
    private $examModel;
    private $questionModel;

    public function __construct()
    {
        $this->db = \Db::getInstance();
        $this->examModel = new \ExamRecordModel();
        $this->questionModel = new \QuestionModel();
    }

    /**
     * 验证用户登录
     */
    private function authUser()
    {
        $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';

        if (strpos($token, 'Bearer ') === 0) {
            $userId = intval(str_replace('Bearer ', '', $token));
            if ($userId > 0) {
                return $userId;
            }
        }

        Response::unauthorized('请先登录');
    }

    /**
     * 生成模拟试卷
     * POST /api/exam/generate
     */
    public function generate()
    {
        $userId = $this->authUser();

        $data = json_decode(file_get_contents('php://input'), true);

        $subject = intval($data['subject'] ?? 1);
        $questionCount = intval($data['question_count'] ?? 50);

        if (!in_array($subject, [1, 4])) {
            Response::error('科目参数不正确');
        }

        // 限制题目数量
        $questionCount = min(max($questionCount, 10), 100);

        // 获取随机题目
        $questions = $this->questionModel->getRandomQuestions($subject, $questionCount);

        // 生成试卷ID
        $examId = 'EXAM' . date('YmdHis') . rand(1000, 9999);

        // 存储到缓存（实际应用Redis）
        $cacheFile = RUNTIME_PATH . 'exams/' . $examId . '.json';
        $dir = dirname($cacheFile);
        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $examData = [
            'user_id'       => $userId,
            'subject'       => $subject,
            'question_ids'   => array_column($questions, 'id'),
            'answers'       => [], // 用户答案
            'created_at'    => time(),
            'expire_at'     => time() + 3600 // 1小时有效期
        ];

        file_put_contents($cacheFile, json_encode($examData));

        Response::success([
            'exam_id'      => $examId,
            'question_ids' => $examData['question_ids'],
            'total_time'   => 45 // 分钟
        ], '试卷生成成功');
    }

    /**
     * 提交试卷
     * POST /api/exam/submit
     */
    public function submit()
    {
        $userId = $this->authUser();

        $data = json_decode(file_get_contents('php://input'), true);

        $examId = $data['exam_id'] ?? '';
        $answers = $data['answers'] ?? [];
        $timeUsed = intval($data['time_used'] ?? 0);

        if (empty($examId)) {
            Response::error('试卷ID不能为空');
        }

        // 读取试卷数据
        $cacheFile = RUNTIME_PATH . 'exams/' . $examId . '.json';
        if (!file_exists($cacheFile)) {
            Response::error('试卷不存在或已过期');
        }

        $examData = json_decode(file_get_contents($cacheFile), true);

        // 检查用户权限
        if ($examData['user_id'] != $userId) {
            Response::error('无权提交此试卷');
        }

        // 检查是否过期
        if ($examData['expire_at'] < time()) {
            Response::error('试卷已过期');
        }

        // 获取题目信息并评分
        $questionIds = $examData['question_ids'];
        $questions = $this->questionModel->getDetails($questionIds);

        $correctCount = 0;
        $wrongQuestions = [];

        foreach ($questions as $question) {
            $qId = $question['id'];
            $userAnswer = $answers[$qId] ?? '';
            $correctAnswer = strtoupper($question['answer']);
            $userAnswerUpper = strtoupper($userAnswer);

            if ($userAnswerUpper === $correctAnswer) {
                $correctCount++;
            } else {
                $wrongQuestions[] = [
                    'question_id'    => $qId,
                    'user_answer'    => $userAnswer,
                    'correct_answer' => $correctAnswer
                ];
            }
        }

        $totalQuestions = count($questions);
        $score = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100, 2) : 0;

        // 保存考试记录
        $recordId = $this->examModel->create($userId, $examData['subject'], $score, $totalQuestions, $correctCount, $timeUsed);

        // 删除缓存
        unlink($cacheFile);

        Response::success([
            'score'          => $score,
            'correct_count'  => $correctCount,
            'total_questions'=> $totalQuestions,
            'exam_record_id' => $recordId,
            'wrong_questions'=> $wrongQuestions
        ], '提交成功');
    }

    /**
     * 获取考试成绩列表
     * GET /api/exam/record_list
     */
    public function recordList()
    {
        $userId = $this->authUser();
        $page = intval($_GET['page'] ?? 1);
        $pageSize = intval($_GET['page_size'] ?? 20);

        $pageSize = min($pageSize, 50);

        $result = $this->examModel->getList($userId, $page, $pageSize);

        Response::success($result);
    }
}
