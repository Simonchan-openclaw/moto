<?php
namespace app\controller\api;

use app\model\ExamRecord as ExamRecordModel;
use app\model\Question as QuestionModel;

class Exam
{
    protected $examModel;
    protected $questionModel;

    public function __construct()
    {
        $this->examModel = new ExamRecordModel();
        $this->questionModel = new QuestionModel();
    }

    /**
     * 生成模拟试卷
     * POST /api/exam/generate
     */
    public function generate()
    {
        $userId = getCurrentUserId();
        $data = input('post.');
        
        $subject = intval($data['subject'] ?? 1);
        $questionCount = intval($data['question_count'] ?? 50);

        if (!in_array($subject, [1, 4])) {
            return jsonError('科目参数不正确');
        }

        // 限制题目数量
        $questionCount = min(max($questionCount, 10), 100);

        // 获取随机题目
        $questions = $this->questionModel->getRandomQuestions($subject, $questionCount);

        // 生成试卷ID
        $examId = 'EXAM' . date('YmdHis') . rand(1000, 9999);

        // 存储到缓存
        $cacheDir = runtime_path() . 'exams/';
        if (!is_dir($cacheDir)) {
            mkdir($cacheDir, 0755, true);
        }
        $cacheFile = $cacheDir . $examId . '.json';

        $examData = [
            'user_id'       => $userId,
            'subject'       => $subject,
            'question_ids'   => array_column($questions, 'id'),
            'answers'       => [],
            'created_at'    => time(),
            'expire_at'     => time() + 3600
        ];

        file_put_contents($cacheFile, json_encode($examData));

        return jsonSuccess([
            'exam_id'      => $examId,
            'question_ids' => $examData['question_ids'],
            'total_time'   => 45
        ], '试卷生成成功');
    }

    /**
     * 提交试卷
     * POST /api/exam/submit
     */
    public function submit()
    {
        $userId = getCurrentUserId();
        $data = input('post.');
        
        $examId = $data['exam_id'] ?? '';
        $answers = $data['answers'] ?? [];
        $timeUsed = intval($data['time_used'] ?? 0);

        if (empty($examId)) {
            return jsonError('试卷ID不能为空');
        }

        // 读取试卷数据
        $cacheFile = runtime_path() . 'exams/' . $examId . '.json';
        if (!file_exists($cacheFile)) {
            return jsonError('试卷不存在或已过期');
        }

        $examData = json_decode(file_get_contents($cacheFile), true);

        // 检查用户权限
        if ($examData['user_id'] != $userId) {
            return jsonError('无权提交此试卷');
        }

        // 检查是否过期
        if ($examData['expire_at'] < time()) {
            return jsonError('试卷已过期');
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
            
            // 处理用户答案
            if (is_array($userAnswer)) {
                // 多选题：排序后比较
                $userAnswerStr = implode('', array_map('strtoupper', $userAnswer));
                $correctAnswerStr = implode('', array_map('strtoupper', str_split($correctAnswer)));
                $isCorrect = ($userAnswerStr === $correctAnswerStr);
            } else {
                // 单选题/判断题
                $userAnswerUpper = strtoupper($userAnswer);
                $isCorrect = ($userAnswerUpper === $correctAnswer);
            }

            if ($isCorrect) {
                $correctCount++;
            } else {
                $wrongQuestions[] = [
                    'question_id'    => $qId,
                    'user_answer'    => is_array($userAnswer) ? implode('', $userAnswer) : $userAnswer,
                    'correct_answer' => $correctAnswer
                ];
            }
        }

        $totalQuestions = count($questions);
        $score = $totalQuestions > 0 ? round(($correctCount / $totalQuestions) * 100, 2) : 0;

        // 保存考试记录
        $recordId = $this->examModel->create($userId, $examData['subject'], $score, $totalQuestions, $correctCount, $timeUsed);

        // 删除缓存
        @unlink($cacheFile);

        return jsonSuccess([
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
        $userId = getCurrentUserId();
        $page = input('get.page/d', 1);
        $pageSize = input('get.page_size/d', 20);

        $pageSize = min($pageSize, 50);

        $result = $this->examModel->getList($userId, $page, $pageSize);

        return jsonSuccess($result);
    }
}
