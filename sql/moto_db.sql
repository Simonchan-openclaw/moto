-- ============================================================
-- 摩托车笔试题库系统 数据库建表脚本
-- 字符集: utf8mb4 | 排序规则: utf8mb4_unicode_ci
-- MySQL 5.7/8.0 兼容
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ----------------------------
-- 1. 用户表 (user)
-- ----------------------------
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '用户ID',
  `phone` varchar(20) NOT NULL COMMENT '手机号',
  `password` varchar(255) NOT NULL COMMENT '密码(加密存储)',
  `nickname` varchar(50) DEFAULT '' COMMENT '昵称',
  `avatar` varchar(255) DEFAULT '' COMMENT '头像URL',
  `device_id` varchar(100) DEFAULT '' COMMENT '设备码(绑定设备)',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '账号状态: 1=正常, 0=禁用',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(50) DEFAULT NULL COMMENT '最后登录IP',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '注册时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_phone` (`phone`),
  KEY `idx_status` (`status`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='用户表';

-- ----------------------------
-- 2. 题目表 (question)
-- ----------------------------
DROP TABLE IF EXISTS `question`;
CREATE TABLE `question` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '题目ID',
  `subject` tinyint(1) NOT NULL COMMENT '科目: 1=科目一, 4=科目四',
  `question_type` tinyint(1) NOT NULL COMMENT '题型: 1=单选, 2=多选, 3=判断',
  `chapter_id` bigint unsigned NOT NULL COMMENT '所属章节ID',
  `title` text NOT NULL COMMENT '题干',
  `option_a` varchar(500) DEFAULT '' COMMENT '选项A(JSON格式)',
  `option_b` varchar(500) DEFAULT '' COMMENT '选项B(JSON格式)',
  `option_c` varchar(500) DEFAULT '' COMMENT '选项C(JSON格式)',
  `option_d` varchar(500) DEFAULT '' COMMENT '选项D(JSON格式)',
  `answer` varchar(10) NOT NULL COMMENT '正确答案',
  `analysis` text COMMENT '解析',
  `image` varchar(255) DEFAULT '' COMMENT '图片路径',
  `keywords` varchar(255) DEFAULT '' COMMENT '关键词',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '上下线状态: 1=上线, 0=下线',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_subject` (`subject`),
  KEY `idx_question_type` (`question_type`),
  KEY `idx_chapter_id` (`chapter_id`),
  KEY `idx_status` (`status`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='题目表';

-- ----------------------------
-- 3. 章节表 (chapter)
-- ----------------------------
DROP TABLE IF EXISTS `chapter`;
CREATE TABLE `chapter` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '章节ID',
  `subject` tinyint(1) NOT NULL COMMENT '科目: 1=科目一, 4=科目四',
  `parent_id` bigint unsigned NOT NULL DEFAULT 0 COMMENT '父级章节ID, 0表示顶级',
  `name` varchar(100) NOT NULL COMMENT '章节名称',
  `sort` int(11) NOT NULL DEFAULT 0 COMMENT '排序(数值越小越靠前)',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '启用状态: 1=启用, 0=禁用',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_subject` (`subject`),
  KEY `idx_parent_id` (`parent_id`),
  KEY `idx_sort` (`sort`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='章节表';

-- ----------------------------
-- 4. 答题记录表 (user_answer)
-- ----------------------------
DROP TABLE IF EXISTS `user_answer`;
CREATE TABLE `user_answer` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `question_id` bigint unsigned NOT NULL COMMENT '题目ID',
  `user_answer` varchar(50) NOT NULL COMMENT '用户答案',
  `is_correct` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否正确: 1=正确, 0=错误',
  `answer_time` int(11) NOT NULL DEFAULT 0 COMMENT '答题用时(秒)',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '答题时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_question_id` (`question_id`),
  KEY `idx_user_question` (`user_id`, `question_id`),
  KEY `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='答题记录表';

-- ----------------------------
-- 5. 错题表 (error_question)
-- ----------------------------
DROP TABLE IF EXISTS `error_question`;
CREATE TABLE `error_question` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `question_id` bigint unsigned NOT NULL COMMENT '题目ID',
  `error_count` int(11) NOT NULL DEFAULT 1 COMMENT '错题次数',
  `is_mastered` tinyint(1) NOT NULL DEFAULT 0 COMMENT '是否已掌握: 1=已掌握, 0=未掌握',
  `knowledge_point` varchar(255) DEFAULT '' COMMENT '知识点标注',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '首次错题时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_question` (`user_id`, `question_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_question_id` (`question_id`),
  KEY `idx_is_mastered` (`is_mastered`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='错题表';

-- ----------------------------
-- 6. 收藏表 (collection)
-- ----------------------------
DROP TABLE IF EXISTS `collection`;
CREATE TABLE `collection` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '收藏ID',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `question_id` bigint unsigned NOT NULL COMMENT '题目ID',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '收藏状态: 1=收藏, 0=取消',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '收藏时间',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_user_question` (`user_id`, `question_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_question_id` (`question_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='收藏表';

-- ----------------------------
-- 7. 考试成绩表 (exam_record)
-- ----------------------------
DROP TABLE IF EXISTS `exam_record`;
CREATE TABLE `exam_record` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `user_id` bigint unsigned NOT NULL COMMENT '用户ID',
  `subject` tinyint(1) NOT NULL COMMENT '考试科目',
  `score` decimal(5,2) NOT NULL DEFAULT 0 COMMENT '分数',
  `total_questions` int(11) NOT NULL DEFAULT 0 COMMENT '总题数',
  `correct_count` int(11) NOT NULL DEFAULT 0 COMMENT '正确题数',
  `time_used` int(11) NOT NULL DEFAULT 0 COMMENT '考试用时(秒)',
  `submit_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '交卷时间',
  `created_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  PRIMARY KEY (`id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_subject` (`subject`),
  KEY `idx_submit_time` (`submit_time`),
  KEY `idx_user_subject` (`user_id`, `subject`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='考试成绩表';

-- ----------------------------
-- 8. 管理员表 (admin)
-- ----------------------------
DROP TABLE IF EXISTS `admin`;
CREATE TABLE `admin` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '管理员ID',
  `username` varchar(50) NOT NULL COMMENT '账号(唯一)',
  `password` varchar(255) NOT NULL COMMENT '密码(加密存储)',
  `real_name` varchar(50) DEFAULT '' COMMENT '真实姓名',
  `role_id` int(11) NOT NULL DEFAULT 1 COMMENT '角色ID',
  `role_name` varchar(50) DEFAULT '管理员' COMMENT '角色名称',
  `login_count` int(11) NOT NULL DEFAULT 0 COMMENT '登录次数',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(50) DEFAULT NULL COMMENT '最后登录IP',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态: 1=正常, 0=禁用',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_username` (`username`),
  KEY `idx_role_id` (`role_id`),
  KEY `idx_status` (`status`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='管理员表';

-- ----------------------------
-- 9. 系统配置表 (system_config)
-- ----------------------------
DROP TABLE IF EXISTS `system_config`;
CREATE TABLE `system_config` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '配置ID',
  `config_key` varchar(100) NOT NULL COMMENT '配置键(唯一)',
  `config_value` text COMMENT '配置值',
  `config_name` varchar(100) NOT NULL COMMENT '配置名称',
  `group_name` varchar(50) NOT NULL DEFAULT 'default' COMMENT '配置分组',
  `description` varchar(255) DEFAULT '' COMMENT '描述',
  `updated_at` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_config_key` (`config_key`),
  KEY `idx_group_name` (`group_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='系统配置表';

-- ============================================================
-- 教练激活模块 - 新增表
-- ============================================================

-- ----------------------------
-- 10. 教练表 (coach)
-- ----------------------------
DROP TABLE IF EXISTS `coach`;
CREATE TABLE `coach` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '教练ID',
  `phone` varchar(20) NOT NULL COMMENT '手机号',
  `password` varchar(255) NOT NULL COMMENT '密码(加密存储)',
  `real_name` varchar(50) DEFAULT '' COMMENT '真实姓名',
  `avatar` varchar(255) DEFAULT '' COMMENT '头像URL',
  `balance` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '账户余额(元)',
  `total_recharged` decimal(10,2) NOT NULL DEFAULT 0.00 COMMENT '累计充值金额(元)',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态: 1=正常, 0=禁用',
  `last_login_time` datetime DEFAULT NULL COMMENT '最后登录时间',
  `last_login_ip` varchar(50) DEFAULT NULL COMMENT '最后登录IP',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '注册时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  UNIQUE KEY `uk_phone` (`phone`),
  KEY `idx_status` (`status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='教练表';

-- ----------------------------
-- 11. 教练充值记录表 (recharge_record)
-- ----------------------------
DROP TABLE IF EXISTS `recharge_record`;
CREATE TABLE `recharge_record` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `coach_id` bigint unsigned NOT NULL COMMENT '教练ID',
  `amount` decimal(10,2) NOT NULL COMMENT '充值金额(元)',
  `pay_method` tinyint(1) NOT NULL DEFAULT 1 COMMENT '支付方式: 1=微信支付, 2=支付宝',
  `trade_no` varchar(64) DEFAULT '' COMMENT '交易流水号',
  `status` tinyint(1) NOT NULL DEFAULT 1 COMMENT '状态: 0=失败, 1=成功, 2=退款',
  `remark` varchar(255) DEFAULT '' COMMENT '备注',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '充值时间',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_coach_id` (`coach_id`),
  KEY `idx_status` (`status`),
  KEY `idx_create_time` (`create_time`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='教练充值记录表';

-- ----------------------------
-- 12. 学员激活记录表 (student_activation)
-- ----------------------------
DROP TABLE IF EXISTS `student_activation`;
CREATE TABLE `student_activation` (
  `id` bigint unsigned NOT NULL AUTO_INCREMENT COMMENT '记录ID',
  `coach_id` bigint unsigned NOT NULL COMMENT '教练ID',
  `student_phone` varchar(20) NOT NULL COMMENT '学员手机号',
  `activate_code` varchar(32) DEFAULT '' COMMENT '激活码(激活时生成)',
  `device_id` varchar(128) DEFAULT '' COMMENT '设备ID(绑定时记录)',
  `user_id` bigint unsigned DEFAULT NULL COMMENT '关联的用户ID(学员激活后)',
  `amount_deducted` decimal(10,2) NOT NULL DEFAULT 18.00 COMMENT '扣款金额(元)',
  `activate_status` tinyint(1) NOT NULL DEFAULT 0 COMMENT '激活状态: 0=待激活, 1=已激活, 2=已失效, 3=已退款',
  `expire_days` int(11) NOT NULL DEFAULT 30 COMMENT '有效期天数',
  `activated_at` datetime DEFAULT NULL COMMENT '激活时间',
  `expire_at` datetime DEFAULT NULL COMMENT '到期时间',
  `deactivate_reason` varchar(100) DEFAULT '' COMMENT '失效原因',
  `create_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT '创建时间(教练操作时间)',
  `update_time` datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP COMMENT '更新时间',
  PRIMARY KEY (`id`),
  KEY `idx_coach_id` (`coach_id`),
  KEY `idx_student_phone` (`student_phone`),
  KEY `idx_activate_code` (`activate_code`),
  KEY `idx_device_id` (`device_id`),
  KEY `idx_user_id` (`user_id`),
  KEY `idx_activate_status` (`activate_status`),
  KEY `idx_expire_at` (`expire_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='学员激活记录表';

-- ============================================================
-- 初始化数据
-- ============================================================

-- 插入超级管理员账号 (密码: admin123)
-- bcrypt加密后的哈希值
INSERT INTO `admin` (`username`, `password`, `real_name`, `role_id`, `role_name`, `status`) VALUES
('admin', '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', '系统管理员', 1, '超级管理员', 1);

-- 插入默认系统配置
INSERT INTO `system_config` (`config_key`, `config_value`, `config_name`, `group_name`, `description`) VALUES
('app_name', '摩托车笔试题库', '系统名称', 'basic', '系统显示名称'),
('app_version', '1.0.0', '系统版本', 'basic', '当前系统版本号'),
('exam_passing_score', '90', '及格分数', 'exam', '考试成绩及格分数线'),
('exam_time_limit', '2700', '考试时限(秒)', 'exam', '科目一/四考试时间限制(45分钟)'),
('question_per_exam', '50', '每套试卷题数', 'exam', '模拟考试随机抽题数量'),
('sms_verify_expire', '600', '验证码有效期(秒)', 'sms', '短信验证码有效期(10分钟)'),
('sms_resend_interval', '60', '短信发送间隔(秒)', 'sms', '同一手机号60秒内不可重复获取'),
('wx_appid', '', '微信AppID', 'wechat', '微信小程序AppID'),
('wx_secret', '', '微信AppSecret', 'wechat', '微信小程序AppSecret'),
('activation_price', '18.00', '单次激活价格(元)', 'activation', '教练激活学员的费用'),
('min_recharge_amount', '18.00', '最低充值金额(元)', 'activation', '教练单次充值最低金额'),
('activation_expire_days', '30', '激活码有效期(天)', 'activation', '激活码生成后的有效天数');

-- 插入科目一章节数据
INSERT INTO `chapter` (`subject`, `parent_id`, `name`, `sort`, `status`) VALUES
(1, 0, '道路交通安全法律、法规和规章', 1, 1),
(1, 0, '道路交通信号及其含义', 2, 1),
(1, 0, '安全行车、文明驾驶知识', 3, 1),
(1, 0, '机动车构造与维护常识', 4, 1),
(1, 0, '安全驾驶行为与应急处置', 5, 1);

-- 插入科目四章节数据
INSERT INTO `chapter` (`subject`, `parent_id`, `name`, `sort`, `status`) VALUES
(4, 0, '安全驾驶基础知识', 1, 1),
(4, 0, '道路通行规则', 2, 1),
(4, 0, '违法行为处罚', 3, 1),
(4, 0, '事故处理与应急救援', 4, 1),
(4, 0, '机动车保险常识', 5, 1);

SET FOREIGN_KEY_CHECKS = 1;

-- ============================================================
-- 更新已有数据库：添加设备码字段
-- 如已存在数据库，执行此语句
-- ============================================================
-- ALTER TABLE `user` ADD COLUMN `device_id` varchar(100) DEFAULT '' COMMENT '设备码(绑定设备)' AFTER `avatar`;
-- ALTER TABLE `user` ADD INDEX `idx_device_id` (`device_id`);

-- ============================================================
-- 脚本执行完成
-- 数据库创建成功，包含12张数据表及初始化数据
-- ============================================================
