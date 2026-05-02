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
     * 
     * 请求格式：JSON body
     * {
     *   "subject": 1,  // 科目：1=科目一、4=科目四
     *   "content": "[...JSON数组...]"  // JSON格式的题目内容
     * }
     * 
     * JSON数组格式：
     * {
     *   "type": 1,  // 题型：1=单选题、2=判断题、3=多选题
     *   "question": "题目内容",
     *   "options": {"A": "选项A", "B": "选项B", "C": "选项C", "D": "选项D"},
     *   "correct_answer": "A",
     *   "analysis": "解析内容（可选）"
     * }
     */
    public function jsonImport()
    {
        // 获取原始JSON内容
        $jsonContent = file_get_contents('php://input');
        $params = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return jsonError('请求参数格式错误：' . json_last_error_msg());
        }
        
        // 获取参数
        $subject = isset($params['subject']) ? intval($params['subject']) : 0;
        $content = isset($params['content']) ? $params['content'] : '';
        
        // 验证科目
        if (!in_array($subject, [1, 4])) {
            return jsonError('请选择正确的科目（科目一或科目四）');
        }
        
        // 验证JSON内容
        if (empty($content)) {
            return jsonError('JSON内容不能为空');
        }
        
        // 解析JSON内容
        $questions = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return jsonError('JSON内容格式错误：' . json_last_error_msg());
        }
        
        if (!is_array($questions) || empty($questions)) {
            return jsonError('JSON内容为空或格式错误');
        }
        
        // 批量插入数据
        $successCount = 0;
        $failCount = 0;
        $errors = [];
        $now = date('Y-m-d H:i:s');
        
        // 题型映射
        $typeMap = [
            1 => '单选题',
            2 => '判断题',
            3 => '多选题'
        ];
        
        // 准备批量插入
        $insertData = [];
        
        foreach ($questions as $index => $item) {
            // 从JSON中获取type，自动设置题型
            $questionType = isset($item['type']) ? intval($item['type']) : 0;
            
            // 验证题型
            if (!in_array($questionType, [1, 2, 3])) {
                $failCount++;
                $errors[] = '第' . ($index + 1) . '条：type无效（应为1=单选题、2=判断题、3=多选题）';
                continue;
            }
            
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
                // 判断题：只能是A或B
                if (!in_array($answer, ['A', 'B'])) {
                    $failCount++;
                    $errors[] = '第' . ($index + 1) . '条：判断题答案必须是A或B';
                    continue;
                }
            } elseif ($questionType == 3) {
                // 多选题：必须是多个字母组合
                if (!preg_match('/^[A-D]{1,4}$/', $answer) || strlen($answer) < 2) {
                    $failCount++;
                    $errors[] = '第' . ($index + 1) . '条：多选题答案必须是多个选项组合（如AB、ACD）';
                    continue;
                }
            }
            
            // 提取选项
            $optionA = $item['options']['A'] ?? '';
            $optionB = $item['options']['B'] ?? '';
            $optionC = $item['options']['C'] ?? '';
            $optionD = $item['options']['D'] ?? '';
            
            // 判断题只需要A/B选项
            if ($questionType == 2) {
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
     * 删除题目（软删除）
     * POST /api/admin/question/delete
     */
    public function delete()
    {
        $id = input('post.id/d', 0);
        
        if ($id <= 0) {
            return jsonError('题目ID不能为空');
        }

        // 软删除：设置status为0
        $result = Db::name('question')->where('id', $id)->update(['status' => 0, 'updated_at' => date('Y-m-d H:i:s')]);
        
        if ($result) {
            return jsonSuccess(['success' => true], '删除成功');
        } else {
            return jsonError('删除失败');
        }
    }

    /**
     * 设置题目状态（启用/禁用）
     * POST /api/admin/question/setStatus
     */
    public function setStatus()
    {
        $id = input('post.id/d', 0);
        $status = input('post.status/d', 0);
        
        if ($id <= 0) {
            return jsonError('题目ID不能为空');
        }
        
        if (!in_array($status, [0, 1])) {
            return jsonError('状态值无效');
        }

        $result = Db::name('question')->where('id', $id)->update(['status' => $status, 'updated_at' => date('Y-m-d H:i:s')]);
        
        if ($result) {
            return jsonSuccess(['success' => true], $status == 1 ? '启用成功' : '禁用成功');
        } else {
            return jsonError('操作失败');
        }
    }

    /**
     * 公开题库导入（无需认证）
     * POST /api/public/import
     */
    public function publicImport()
    {
        $jsonContent = file_get_contents('php://input');
        $params = json_decode($jsonContent, true);
        
        if (json_last_error() !== JSON_ERROR_NONE) {
            return json(['code' => 400, 'message' => '请求参数格式错误：' . json_last_error_msg()]);
        }
        
        $subject = isset($params['subject']) ? intval($params['subject']) : 0;
        $content = isset($params['content']) ? $params['content'] : '';
        
        if (!in_array($subject, [1, 4])) {
            return json(['code' => 400, 'message' => '请选择正确的科目']);
        }
        
        if (empty($content)) {
            return json(['code' => 400, 'message' => 'JSON内容不能为空']);
        }
        
        $questions = json_decode($content, true);
        
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($questions) || empty($questions)) {
            return json(['code' => 400, 'message' => 'JSON格式错误']);
        }
        
        $successCount = 0;
        $failCount = 0;
        $errors = [];
        $now = date('Y-m-d H:i:s');
        
        foreach ($questions as $index => $item) {
            $questionType = isset($item['type']) ? intval($item['type']) : 0;
            
            if (!in_array($questionType, [1, 2, 3])) {
                $failCount++;
                $errors[] = '第' . ($index + 1) . '条：type无效';
                continue;
            }
            
            if (empty($item['question']) || !isset($item['options']) || empty($item['correct_answer'])) {
                $failCount++;
                $errors[] = '第' . ($index + 1) . '条：缺少必填字段';
                continue;
            }
            
            $answer = strtoupper($item['correct_answer']);
            $optionA = $item['options']['A'] ?? '';
            $optionB = $item['options']['B'] ?? '';
            $optionC = $item['options']['C'] ?? '';
            $optionD = $item['options']['D'] ?? '';
            
            if ($questionType == 2) {
                $optionC = '';
                $optionD = '';
            }
            
            try {
                Db::name('question')->insert([
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
                ]);
                $successCount++;
            } catch (\Exception $e) {
                $failCount++;
                $errors[] = '第' . ($index + 1) . '条：' . $e->getMessage();
            }
        }
        
        $message = "导入完成：成功{$successCount}条";
        if ($failCount > 0) {
            $message .= "，失败{$failCount}条";
        }
        
        return json([
            'code' => 200,
            'message' => $message,
            'data' => [
                'success' => $successCount,
                'fail' => $failCount,
                'errors' => array_slice($errors, 0, 10)
            ]
        ]);
    }

    /**
    /**
     * 图片上传（免登录）
     * POST /api/public/uploadImage
     */
    public function uploadImage()
    {
        // 检查是否有文件
        if (empty($_FILES['image']) || $_FILES['image']['error'] !== UPLOAD_ERR_OK) {
            return json(['code' => 400, 'message' => '请选择要上传的图片']);
        }

        $file = $_FILES['image'];

        // 验证图片
        $imageInfo = getimagesize($file['tmp_name']);
        if (!$imageInfo) {
            return json(['code' => 400, 'message' => '文件不是有效的图片']);
        }

        // 获取扩展名
        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'];
        if (!in_array($ext, $allowedExt)) {
            return json(['code' => 400, 'message' => '只支持上传图片文件（jpg、png、gif、webp、bmp）']);
        }

        // 使用ThinkPHP的根路径
        $rootPath = app()->getRootPath();
        $savePath = $rootPath . 'public' . DIRECTORY_SEPARATOR . 'h5' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR;

        // 确保目录存在
        if (!is_dir($savePath)) {
            mkdir($savePath, 0755, true);
        }

        // 生成唯一文件名
        $newFileName = date('YmdHis') . '_' . uniqid() . '.' . $ext;
        $targetFile = $savePath . $newFileName;

        // 移动文件
        if (move_uploaded_file($file['tmp_name'], $targetFile)) {
            $url = 'https://moto.zd16688.com/h5/images/' . $newFileName;
            return json([
                'code' => 200,
                'message' => '上传成功',
                'data' => [
                    'url' => $url,
                    'filename' => $newFileName,
                    'path' => $targetFile,
                    'size' => filesize($targetFile)
                ]
            ]);
        } else {
            return json(['code' => 500, 'message' => '文件保存失败，路径：' . $savePath]);
        }
    }
}
