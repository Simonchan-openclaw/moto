<?php
namespace app\model;

use think\Model;
use think\facade\Db;

class Chapter extends Model
{
    protected $name = 'chapter';
    protected $pk = 'id';

    /**
     * 获取章节列表
     */
    public function getListBySubject($subject)
    {
        $result = Db::query(
            "SELECT * FROM {$this->name} WHERE subject = ? ORDER BY sort ASC, id ASC",
            [$subject]
        );
        return $result;
    }
}
