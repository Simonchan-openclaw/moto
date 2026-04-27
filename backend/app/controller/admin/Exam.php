<?php
namespace app\controller\admin;

use think\facade\Db;

class Exam
{
    /**
     * 考试记录列表
     * GET /api/admin/exam/records
     */
    public function records()
    {
        $page = input('get.page/d', 1);
        $pageSize = input('get.page_size/d', 20);
        $subject = input('get.subject/d', null);

        $pageSize = min($pageSize, 100);
        $offset = ($page - 1) * $pageSize;

        $where = [];
        $params = [];

        if ($subject !== null) {
            $where[] = 'e.subject = ?';
            $params[] = $subject;
        }

        $whereSql = empty($where) ? '' : 'WHERE ' . implode(' AND ', $where);

        // 获取总数
        $total = Db::query(
            "SELECT COUNT(*) as cnt FROM exam_record e {$whereSql}",
            $params
        )[0]['cnt'] ?? 0;

        // 获取列表
        $list = Db::query(
            "SELECT e.*, u.phone, u.nickname 
             FROM exam_record e 
             LEFT JOIN user u ON e.user_id = u.id 
             {$whereSql} 
             ORDER BY e.id DESC 
             LIMIT ? OFFSET ?",
            array_merge($params, [$pageSize, $offset])
        );

        return jsonSuccess([
            'list'        => $list,
            'total'       => $total,
            'page'        => $page,
            'page_size'   => $pageSize,
            'total_pages' => ceil($total / $pageSize)
        ]);
    }
}
