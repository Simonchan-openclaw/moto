<?php
/**
 * 章节模型
 */

namespace Model;

require_once __DIR__ . '/../library/Db.php';

class ChapterModel
{
    private $db;
    private $table = 'chapter';

    public function __construct()
    {
        $this->db = \Db::getInstance();
    }

    /**
     * 获取章节列表
     */
    public function getListBySubject($subject)
    {
        return $this->db->fetchAll(
            "SELECT * FROM {$this->table} WHERE subject = ? AND status = 1 ORDER BY sort ASC, id ASC",
            [$subject]
        );
    }

    /**
     * 获取章节详情
     */
    public function getDetail($chapterId)
    {
        return $this->db->fetch(
            "SELECT * FROM {$this->table} WHERE id = ?",
            [$chapterId]
        );
    }
}
