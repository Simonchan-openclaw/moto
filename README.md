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

## 🚀 部署指南

### 环境要求
- PHP 8.1+
- MySQL 5.7+ / MySQL 8.0
- Nginx / Apache
- Composer 2.x

### 1. 服务器环境配置

#### PHP 环境
```bash
# 安装 PHP 8.1+
sudo apt update
sudo apt install php8.1 php8.1-fpm php8.1-mysql php8.1-cli php8.1-curl php8.1-json php8.1-mbstring php8.1-xml php8.1-zip
```

#### MySQL 数据库
```bash
# 安装 MySQL
sudo apt install mysql-server

# 登录 MySQL 创建数据库
mysql -u root -p
CREATE DATABASE moto_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

### 2. 项目部署

#### 方式一：Git 拉取（推荐）
```bash
# 克隆仓库
git clone https://github.com/Simonchan-openclaw/moto.git

# 进入项目目录
cd moto

# 切换到 main 分支
git checkout main
```

#### 方式二：直接部署后端
```bash
# 进入后端目录
cd backend

# 安装 Composer 依赖
composer install

# 复制环境配置
cp .env.example .env

# 编辑 .env 配置数据库连接
# DB_HOST=localhost
# DB_DATABASE=moto_db
# DB_USERNAME=your_db_user
# DB_PASSWORD=your_db_password
```

### 3. 数据库初始化

```bash
# 导入数据库 SQL
mysql -u root -p moto_db < ../sql/moto_db.sql

# 或在后端目录执行
php think migrate:run
```

### 4. Nginx 配置

```nginx
server {
    listen 80;
    server_name your-domain.com;
    root /path/to/moto/backend/public;

    location / {
        index index.php;
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
    }

    # H5 静态文件（可选，反向代理）
    location /h5 {
        alias /path/to/moto/h5;
        index index.html;
    }
}
```

### 5. H5 前端部署

H5 前端为纯静态文件，可直接部署到任意 Web 服务器或 CDN：

```bash
# 方式一：Nginx 部署
location /exam {
    alias /path/to/moto/h5;
    index index.html;
    try_files $uri $uri/ /exam/index.html;
}

# 方式二：直接复制到 Web 服务器
cp -r h5/* /var/www/html/
```

### 6. 权限配置

```bash
# 设置目录权限
chmod -R 755 backend/
chmod -R 755 h5/

# 设置存储目录权限
chmod -R 777 backend/runtime/
chmod -R 777 backend/storage/
```

### 7. 管理后台访问

- 管理员登录地址：`https://your-domain.com/admin`
- 默认账号：`admin`
- 默认密码：`admin123`

### 8. API 接口地址

- 开发环境：`http://localhost:8080/api/`
- 生产环境：`https://your-domain.com/api/`

### 9. 验证部署

```bash
# 测试数据库连接
php think db:show

# 访问 API 健康检查
curl https://your-domain.com/api/index/health
```

---

### 目录结构参考

```
moto/
├── backend/          # ThinkPHP8 后端（管理后台）
│   ├── app/          # 应用目录
│   ├── public/       # Web 入口
│   ├── runtime/      # 运行时目录
│   └── .env          # 环境配置
├── h5/               # H5 移动端（纯静态）
│   ├── css/
│   ├── js/
│   └── index.html
├── docs/             # 文档
│   └── api.md        # API 接口文档
├── sql/              # 数据库 SQL
│   └── moto_db.sql
└── README.md
```

### 常见问题

**Q: 提示权限不足？**
```bash
chmod -R 777 backend/runtime/
```

**Q: 数据库连接失败？**
检查 `.env` 文件中的数据库配置是否正确。

**Q: H5 无法访问后端 API？**
检查 Nginx 反向代理配置，确保 `/api/` 指向后端。

