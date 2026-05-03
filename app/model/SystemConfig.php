<?php
namespace app\model;

use think\facade\Db;

/**
 * 系统配置模型
 */
class SystemConfig
{
    protected static $table = 'system_config';

    /**
     * 获取配置值
     */
    public static function getValue($key, $default = null)
    {
        $config = Db::query(
            "SELECT config_value FROM " . self::$table . " WHERE config_key = ?",
            [$key]
        );
        
        if (empty($config)) {
            return $default;
        }
        
        return $config[0]['config_value'] ?? $default;
    }

    /**
     * 设置配置值
     */
    public static function setValue($key, $value)
    {
        $exists = Db::query(
            "SELECT id FROM " . self::$table . " WHERE config_key = ?",
            [$key]
        );
        
        if (empty($exists)) {
            return Db::execute(
                "INSERT INTO " . self::$table . " (config_key, config_value, create_time) VALUES (?, ?, NOW())",
                [$key, $value]
            );
        } else {
            return Db::execute(
                "UPDATE " . self::$table . " SET config_value = ?, update_time = NOW() WHERE config_key = ?",
                [$value, $key]
            );
        }
    }

    /**
     * 获取所有配置
     */
    public static function getAll()
    {
        $list = Db::query("SELECT * FROM " . self::$table . " ORDER BY id ASC");
        $result = [];
        foreach ($list as $item) {
            $result[$item['config_key']] = $item['config_value'];
        }
        return $result;
    }

    /**
     * 获取激活相关配置
     */
    public static function getActivationConfig()
    {
        return [
            'self_invite_fee'  => floatval(self::getValue('activation_self_invite_fee', 18.00)),
            'other_invite_fee' => floatval(self::getValue('activation_other_invite_fee', 28.00)),
            'transfer_amount'  => floatval(self::getValue('activation_transfer_amount', 10.00)),
            'expire_days'      => intval(self::getValue('activation_expire_days', 90)),
        ];
    }
}
