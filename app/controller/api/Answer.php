<?php
namespace app\controller\api;

use app\model\UserAnswer as UserAnswerModel;
use app\model\ErrorQuestion as ErrorQuestionModel;
use app\model\Question as QuestionModel;

class Answer
{
    protected $answerModel;
    protected $errorModel;
    protected $questionModel;

    public function __construct()
    {
        $this->answerModel = new UserAnswerModel();
        $this->errorModel = new ErrorQuestionModel();
        $this->questionModel = new QuestionModel();
    }

    /**
     * 提交答题
     * POST /api/answer/submit
     */
    public function submit()
    {
        $userId = getCurrentUserId();
        $data = input('post.');
        
        $questionId = intval($data['question_id'] ?? 0);
        $userAnswer = $data['user_answer'] ?? '';
        $answerTime = intval($data['answer_time'] ?? 0);

        if ($questionId <= 0) {
            return jsonError('题目ID不能为空');
        }

        if (empty($userAnswer)) {
            return jsonError('用户答案不能为空');
        }

        // 获取题目信息
        $question = $this->questionModel->getDetail($questionId);

        if (!$question) {
            return jsonError('题目不存在');
        }

        // 判断是否正确
        $isCorrect = (strtoupper($userAnswer) === strtoupper($question['answer']));

        // 保存答题记录
        $this->answerModel->create($userId, $questionId, $userAnswer, $isCorrect, $answerTime);

        // 如果答错，记录到错题本
        if (!$isCorrect) {
            $this->errorModel->add($userId, $questionId);
        }

        return jsonSuccess([
            'is_correct'     => $isCorrect,
            'correct_answer' => $question['answer'],
            'analysis'       => $question['analysis'] ?? ''
        ]);
    }

    /**
     * 获取错题列表
     * GET /api/answer/error_list
     */
    public function errorList()
    {
        $userId = getCurrentUserId();
        $page = input('get.page/d', 1);
        $pageSize = input('get.page_size/d', 20);

        $pageSize = min($pageSize, 50);

        $result = $this->errorModel->getList($userId, $page, $pageSize);

        return jsonSuccess($result);
    }

    /**
     * 清空错题本
     * DELETE /api/answer/error_clear
     */
    public function errorClear()
    {
        $userId = getCurrentUserId();

        $this->errorModel->clear($userId);

        return jsonSuccess(['success' => true], '错题本已清空');
    }
}
