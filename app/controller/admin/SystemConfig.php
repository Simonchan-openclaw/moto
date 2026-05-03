<?php
namespace app\controller\admin;

use app\model\SystemConfig as SystemConfigModel;
use think\facade\Db;

/**
 * 系统配置管理
 */
class SystemConfig
{
    /**
     * 获取所有配置
     * GET /admin/system_config/list
     */
    public function list()
    {
        $list = Db::query(
            "SELECT * FROM system_config ORDER BY config_group ASC, id ASC"
        );
        
        return jsonSuccess([
            'list' => $list,
            'groups' => [
                'activation' => '激活配置',
                'default' => '默认配置'
            ]
        ]);
    }

    /**
     * 更新配置
     * POST /admin/system_config/update
     */
    public function update()
    {
        $key = input('post.config_key', '');
        $value = input('post.config_value', '');

        if (empty($key)) {
            return jsonError('配置键不能为空');
        }

        SystemConfigModel::setValue($key, $value);

        return jsonSuccess(['key' => $key, 'value' => $value], '配置更新成功');
    }

    /**
     * 批量更新配置
     * POST /admin/system_config/batchUpdate
     */
    public function batchUpdate()
    {
        $configs = input('post.configs', []);

        if (!is_array($configs) || empty($configs)) {
            return jsonError('配置数据不能为空');
        }

        foreach ($configs as $config) {
            if (isset($config['config_key']) && isset($config['config_value'])) {
                SystemConfigModel::setValue($config['config_key'], $config['config_value']);
            }
        }

        return jsonSuccess(null, '配置更新成功');
    }

    /**
     * 获取激活配置
     * GET /admin/system_config/activation
     */
    public function activation()
    {
        $config = SystemConfigModel::getActivationConfig();
        return jsonSuccess($config);
    }
}
