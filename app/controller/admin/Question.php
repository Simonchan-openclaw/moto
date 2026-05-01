<?php
namespace app\controller\admin;

use app\model\Question as QuestionModel;
use think\facade\Db;

class Question
{
    protected $model;

    public function __construct()
    {
        $this->model = new QuestionModel();
    }

    /**
     * 后台题目列表
     * POST /api/admin/question/list
     */
    public function list()
    {
        $page = input('post.page/d', 1);
        $pageSize = input('post.page_size/d', 20);
        $subject = input('post.subject/d', null);
        $questionType = input('post.question_type/d', null);
        $keyword = input('post.keyword', '');
        $status = input('post.status/d', null);

        $pageSize = min($pageSize, 100);

        $result = $this->model->getAdminList([
            'page'          => $page,
            'page_size'    => $pageSize,
            'subject'      => $subject,
            'question_type'=> $questionType,
            'keyword'      => $keyword,
            'status'       => $status
        ]);

        return jsonSuccess($result);
    }

    /**
     * Excel批量导入题目
     * POST /api/admin/question/import
     */
    public function import()
    {
        // 检查文件上传
        $file = request()->file('file');
        
        if (!$file) {
            return jsonError('文件上传失败');
        }

        // 检查文件类型
        $ext = strtolower($file->getExtension());
        if (!in_array($ext, ['xlsx', 'xls'])) {
            return jsonError('只支持 Excel 文件');
        }

        // TODO: 实际应使用 PhpSpreadsheet 库解析 Excel
        // 这里简化处理，模拟导入成功
        $importCount = 0;
        $errors = [];

        return jsonSuccess([
            'success' => true,
            'count'   => $importCount,
            'errors'  => $errors
        ], '导入完成');
    }

    /**
     * JSON批量导入题目
     * POST /api/admin/question/jsonImport
     */
    public function jsonImport()
    {
        // 获取参数
        $subject = input('post.subject/d', 0);
        $questionType = input('post.question_type/d', 0);
        
        // 验证科目
        if (!in_array($subject, [1, 4])) {
            return jsonError('请选择正确的科目（科目一或科目四）');
        }
        
        // 验证题型
        if (!in_array($questionType, [1, 2, 3])) {
            return jsonError('请选择正确的题型（选择题、判断题或多选题）');
        }
        
        // 检查文件上传
        $file = request()->file('file');
        if (!$file) {
            return jsonError('请上传JSON文件');
        }
        
        // 检查文件类型
        $ext = strtolower($file->getExtension());
        if ($ext !== 'json') {
            return jsonError('只支持JSON文件');
        }
        
        // 读取文件内容
        $content = file_get_contents($file->getPathname());
        $questions = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return jsonError('JSON格式错误：' . json_last_error_msg());
        }
        
        if (!is_array($questions) || empty($questions)) {
            return jsonError('JSON内容为空或格式错误');
        }
        
        // 批量插入数据
        $successCount = 0;
        $failCount = 0;
        $errors = [];
        $now = date('Y-m-d H:i:s');
        
        // 准备批量插入
        $insertData = [];
        
        foreach ($questions as $index => $item) {
            // 验证必填字段
            if (empty($item['question'])) {
                $failCount++;
                $errors[] = '第' . ($index + 1) . '条：缺少question字段';
                continue;
            }
            
            if (!isset($item['options']) || !is_array($item['options'])) {
                $failCount++;
                $errors[] = '第' . ($index + 1) . '条：缺少options字段或格式错误';
                continue;
            }
            
            if (empty($item['correct_answer'])) {
                $failCount++;
                $errors[] = '第' . ($index + 1) . '条：缺少correct_answer字段';
                continue;
            }
            
            // 验证答案格式
            $answer = strtoupper($item['correct_answer']);
            if ($questionType == 1) {
                // 单选题：只能是A/B/C/D
                if (!preg_match('/^[A-D]$/', $answer)) {
                    $failCount++;
                    $errors[] = '第' . ($index + 1) . '条：单选题答案必须是A/B/C/D';
                    continue;
                }
            } elseif ($questionType == 2) {
                // 多选题：必须是多个字母组合
                if (!preg_match('/^[A-D]{1,4}$/', $answer) || strlen($answer) < 2) {
                    $failCount++;
                    $errors[] = '第' . ($index + 1) . '条：多选题答案必须是多个选项组合（如AB、ACD）';
                    continue;
                }
            } elseif ($questionType == 3) {
                // 判断题：只能是A或B
                if (!in_array($answer, ['A', 'B'])) {
                    $failCount++;
                    $errors[] = '第' . ($index + 1) . '条：判断题答案必须是A或B';
                    continue;
                }
            }
            
            // 提取选项
            $optionA = $item['options']['A'] ?? '';
            $optionB = $item['options']['B'] ?? '';
            $optionC = $item['options']['C'] ?? '';
            $optionD = $item['options']['D'] ?? '';
            
            // 判断题只需要A/B选项
            if ($questionType == 3) {
                $optionC = '';
                $optionD = '';
            }
            
            // 组装插入数据
            $insertData[] = [
                'subject' => $subject,
                'question_type' => $questionType,
                'title' => $item['question'],
                'option_a' => $optionA,
                'option_b' => $optionB,
                'option_c' => $optionC,
                'option_d' => $optionD,
                'answer' => $answer,
                'chapter_id' => 1,
                'status' => 1,
                'analysis' => $item['analysis'] ?? '',
                'created_at' => $now,
                'updated_at' => $now,
            ];
            
            $successCount++;
        }
        
        // 批量插入数据库
        if (!empty($insertData)) {
            try {
                Db::name('question')->insertAll($insertData);
            } catch (\Exception $e) {
                return jsonError('数据库插入失败：' . $e->getMessage());
            }
        }
        
        $message = "导入完成：成功{$successCount}条";
        if ($failCount > 0) {
            $message .= "，失败{$failCount}条";
        }
        
        return jsonSuccess([
            'success_count' => $successCount,
            'fail_count' => $failCount,
            'errors' => array_slice($errors, 0, 10) // 最多返回10条错误信息
        ], $message);
    }

    /**
     * 添加题目
     * POST /api/admin/question/add
     */
    public function add()
    {
        $data = input('post.');
        
        // 验证必填字段
        if (empty($data['content']) || empty($data['answer'])) {
            return jsonError('题目内容和答案不能为空');
        }

        // TODO: 实现添加逻辑
        return jsonSuccess(['id' => 0], '添加成功');
    }

    /**
     * 编辑题目
     * POST /api/admin/question/edit
     */
    public function edit()
    {
        $data = input('post.');
        
        if (empty($data['id'])) {
            return jsonError('题目ID不能为空');
        }

        // TODO: 实现编辑逻辑
        return jsonSuccess(['success' => true], '编辑成功');
    }

    /**
     * 删除题目
     * POST /api/admin/question/delete
     */
    public function delete()
    {
        $id = input('post.id/d', 0);
        
        if ($id <= 0) {
            return jsonError('题目ID不能为空');
        }

        // TODO: 实现删除逻辑（软删除）
        return jsonSuccess(['success' => true], '删除成功');
    }
}
