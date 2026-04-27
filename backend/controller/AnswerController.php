<?php
/**
 * 答题控制器
 */

namespace Controller;

require_once __DIR__ . '/../library/Db.php';
require_once __DIR__ . '/../library/Response.php';
require_once __DIR__ . '/../model/UserAnswerModel.php';
require_once __DIR__ . '/../model/ErrorQuestionModel.php';
require_once __DIR__ . '/../model/QuestionModel.php';

class AnswerController
{
    private $db;
    private $answerModel;
    private $errorModel;
    private $questionModel;

    public function __construct()
    {
        $this->db = \Db::getInstance();
        $this->answerModel = new \UserAnswerModel();
        $this->errorModel = new \ErrorQuestionModel();
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
     * 提交答题
     * POST /api/answer/submit
     */
    public function submit()
    {
        $userId = $this->authUser();

        $data = json_decode(file_get_contents('php://input'), true);

        $questionId = intval($data['question_id'] ?? 0);
        $userAnswer = $data['user_answer'] ?? '';
        $answerTime = intval($data['answer_time'] ?? 0);

        if ($questionId <= 0) {
            Response::error('题目ID不能为空');
        }

        if (empty($userAnswer)) {
            Response::error('用户答案不能为空');
        }

        // 获取题目信息
        $question = $this->questionModel->getDetail($questionId);

        if (!$question) {
            Response::error('题目不存在');
        }

        // 判断是否正确
        $isCorrect = (strtoupper($userAnswer) === strtoupper($question['answer']));

        // 保存答题记录
        $this->answerModel->create($userId, $questionId, $userAnswer, $isCorrect, $answerTime);

        // 如果答错，记录到错题本
        if (!$isCorrect) {
            $this->errorModel->add($userId, $questionId);
        }

        Response::success([
            'is_correct'     => $isCorrect,
            'correct_answer' => $question['answer'],
            'analysis'       => $question['analysis']
        ]);
    }

    /**
     * 获取错题列表
     * GET /api/answer/error_list
     */
    public function errorList()
    {
        $userId = $this->authUser();
        $page = intval($_GET['page'] ?? 1);
        $pageSize = intval($_GET['page_size'] ?? 20);

        $pageSize = min($pageSize, 50);

        $result = $this->errorModel->getList($userId, $page, $pageSize);

        Response::success($result);
    }

    /**
     * 清空错题本
     * DELETE /api/answer/error_clear
     */
    public function errorClear()
    {
        $userId = $this->authUser();

        $this->errorModel->clear($userId);

        Response::success(['success' => true], '错题本已清空');
    }
}
