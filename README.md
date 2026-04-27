# 摩托车驾驶证笔试在线练习系统

> 摩托车驾驶证笔试在线练习系统，包含 H5 移动端刷题 + 教练端管理 + ThinkPHP8 管理后台

## 📖 项目简介

本系统为摩托车驾驶证笔试学习提供完整的在线练习解决方案，包含学员使用的 H5 刷题端、教练端激活管理和管理后台。

## 🛠 技术栈

### H5 移动端
- **前端框架**：HTML5 + CSS3 + JavaScript（原生）
- **通信**：Ajax + RESTful API
- **适配**：响应式设计，适配手机/平板/PC

### 管理后台
- **框架**：ThinkPHP 8.x（原生 PHP 实现）
- **数据库**：MySQL 5.7+ / MySQL 8.0
- **认证**：Token 认证

## 📁 目录结构

```
moto/
├── backend/          # 后端 API 源码
│   ├── controller/    # 控制器
│   ├── model/        # 数据模型
│   ├── library/      # 公共类库
│   ├── public/       # 入口文件
│   ├── config.php    # 配置文件
│   └── router.php    # 路由配置
├── h5/               # H5 移动端
│   ├── index.html    # 学员端入口
│   ├── coach.html    # 教练端入口
│   ├── css/          # 样式文件
│   └── js/           # JavaScript 文件
├── docs/             # 项目文档
│   └── api.md        # API 接口文档
├── sql/              # 数据库 SQL 文件
│   └── moto_db.sql   # 数据库建表脚本
└── README.md         # 项目说明文档
```

## 🚀 功能模块

### H5 学员端
- ✅ 用户登录/注册
- ✅ 激活码激活（设备绑定）
- ✅ 章节练习
- ✅ 模拟考试（限时作答）
- ✅ 错题本
- ✅ 收藏题目
- ✅ 考试成绩记录

### H5 教练端
- ✅ 教练登录/注册
- ✅ 账户余额查询
- ✅ 在线充值（微信/支付宝）
- ✅ 激活学员（输入手机号扣款生成激活码）
- ✅ 激活记录管理
- ✅ 充值记录查询
- ✅ 退款功能

### 管理后台
- ⏳ 管理员登录
- ⏳ 题库管理（题目增删改查）
- ⏳ Excel 批量导入题目
- ⏳ 章节管理
- ⏳ 成绩统计

## 💰 商业模式

**激活码授权模式：**
- 教练预充值余额
- 输入学员手机号激活，每次扣 **18元**
- 激活码有效期 **30天**
- 一个激活码只能绑定**一台设备**

## 📋 API 接口

详细接口文档请查看 [API 文档](./docs/api.md)

### 教练端 API
| 接口 | 说明 |
|------|------|
| POST /api/coach/login | 教练登录 |
| POST /api/coach/register | 教练注册 |
| GET /api/coach/balance | 获取余额 |
| POST /api/coach/recharge | 账户充值 |
| POST /api/coach/activate | 激活学员 |
| GET /api/coach/activation_list | 激活记录列表 |

### 学员端 API
| 接口 | 说明 |
|------|------|
| POST /api/user/login | 用户登录 |
| POST /api/user/send_code | 发送验证码 |
| POST /api/student/activate | 学员激活 |
| GET /api/student/check | 查询激活状态 |
| GET /api/question/chapters | 获取章节列表 |
| GET /api/question/list | 获取题目列表 |
| POST /api/answer/submit | 提交答题 |
| POST /api/exam/generate | 生成试卷 |
| POST /api/exam/submit | 提交试卷 |

## 🗄️ 数据库表结构

| 表名 | 说明 |
|------|------|
| user | 学员用户表 |
| coach | 教练表 |
| question | 题目表 |
| chapter | 章节表 |
| user_answer | 答题记录表 |
| error_question | 错题表 |
| collection | 收藏表 |
| exam_record | 考试成绩表 |
| recharge_record | 充值记录表 |
| student_activation | 学员激活记录表 |
| admin | 管理员表 |
| system_config | 系统配置表 |

## 🔧 部署说明

### 1. 数据库部署
```bash
mysql -u root -p < sql/moto_db.sql
```

### 2. 后端部署
- 配置 `backend/config.php` 中的数据库连接
- 确保 Web 服务器指向 `backend/public/` 目录

### 3. H5 前端部署
- 修改 `h5/js/config.js` 中的 `API_BASE` 为实际后端地址

### 4. 默认账号
- **超级管理员**：admin / admin123
- **教练端测试账号**：13800138000 / 123456
- **学员端验证码**：123456（测试环境）

## 📝 开发信息

- **版本**：v1.1
- **更新日期**：2026-04-27
- **Phase 1**：数据库设计、API 文档、后端基础架构 ✅
- **Phase 2**：H5 前端开发、ThinkPHP8 集成 ⏳

## 🔗 链接

- GitHub 仓库：https://github.com/Simonchan-openclaw/moto
- 问题反馈：https://github.com/Simonchan-openclaw/moto/issues

## 📄 许可证

MIT License

## 👤 作者

Simonchan-openclaw
