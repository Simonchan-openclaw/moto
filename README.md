# 摩托车驾驶证笔试在线练习系统

> 摩托车驾驶证笔试在线练习系统，包含 H5 移动端刷题 + ThinkPHP8 管理后台

## 📖 项目简介

本系统为摩托车驾驶证笔试学习提供完整的在线练习解决方案，包含学员使用的 H5 刷题端和管理员使用的后台管理系统。

## 🛠 技术栈

### H5 移动端
- **前端框架**：HTML5 + CSS3 + JavaScript（原生）
- **UI 库**：jQuery
- **通信**：Ajax + RESTful API
- **适配**：响应式设计，适配手机/平板/PC

### 管理后台
- **框架**：ThinkPHP 8.x
- **数据库**：MySQL 5.7+ / MySQL 8.0
- **认证**：JWT Token
- **依赖管理**：Composer

## 📁 目录结构

```
moto/
├── backend/                    # ThinkPHP8 后端源码
│   ├── app/                   # 应用目录
│   │   ├── controller/        # 控制器
│   │   │   ├── api/           # API接口控制器
│   │   │   ├── admin/         # 管理后台控制器
│   │   │   ├── coach/         # 教练端控制器
│   │   │   └── student/       # 学员端控制器
│   │   ├── model/             # 数据模型
│   │   ├── middleware/         # 中间件
│   │   ├── exception/          # 异常处理
│   │   └── common.php         # 公共函数
│   ├── config/                # 配置文件
│   │   ├── app.php            # 应用配置
│   │   ├── database.php       # 数据库配置
│   │   ├── jwt.php            # JWT配置
│   │   └── middleware.php     # 中间件配置
│   ├── route/                 # 路由定义
│   │   └── app.php            # 路由规则
│   ├── public/                # Web入口目录（必须！）
│   │   └── index.php          # 入口文件
│   ├── runtime/               # 运行时目录（需777权限）
│   ├── storage/               # 存储目录（需777权限）
│   ├── composer.json          # Composer依赖定义
│   ├── .env.example           # 环境变量示例
│   └── .htaccess              # Apache伪静态配置
├── h5/                        # H5 移动端（纯静态）
│   ├── index.html              # 学员端入口
│   ├── css/                   # 样式文件
│   ├── js/                    # JavaScript 文件
│   └── images/                # 图片资源
├── docs/                      # 项目文档
│   └── api.md                 # API 接口文档
├── sql/                       # 数据库 SQL 文件
│   └── moto_db.sql            # 数据库建表脚本
└── README.md                  # 项目说明文档
```

## 🚀 功能模块

### H5 学员端
- ✅ 用户登录/注册（手机验证码）
- ✅ 设备码绑定（UUID永久绑定）
- ✅ 章节练习（科目一/科目四）
- ✅ 模拟考试（限时作答）
- ✅ 错题本
- ✅ 收藏题目
- ✅ VIP激活（18元/90天）
- ✅ 考试成绩记录

### 管理后台
- ✅ 管理员登录
- ✅ 题库管理（题目增删改查）
- ✅ Excel 批量导入题目
- ✅ 用户管理
- ✅ 成绩统计

## 💰 VIP 定价

- **激活价格**：18元/次
- **有效期**：90天
- **绑定规则**：UUID设备码永久绑定（不支持换绑）

## 📋 API 接口

详细接口文档请查看 [API 文档](./docs/api.md)

### 学员端 API
| 接口 | 说明 | 认证 |
|------|------|------|
| POST /api/user/send_code | 发送验证码 | 否 |
| POST /api/user/login | 用户登录 | 否 |
| GET /api/vip/status | VIP状态查询 | 否 |
| POST /api/user/info | 获取用户信息 | 是 |
| GET /api/question/chapters | 获取章节列表 | 是 |
| GET /api/question/list | 获取题目列表 | 是 |
| POST /api/answer/submit | 提交答题 | 是 |
| GET /api/answer/error_list | 错题列表 | 是 |
| POST /api/collection/toggle | 收藏/取消 | 是 |
| POST /api/exam/generate | 生成试卷 | 是 |
| POST /api/exam/submit | 提交试卷 | 是 |
| POST /api/vip/activate | 激活VIP | 是 |

### 管理后台 API
| 接口 | 说明 | 认证 |
|------|------|------|
| POST /api/admin/login | 管理员登录 | 否 |
| POST /api/admin/question/list | 题目列表 | 是 |
| POST /api/admin/question/add | 新增题目 | 是 |
| POST /api/admin/question/edit | 编辑题目 | 是 |
| POST /api/admin/question/delete | 删除题目 | 是 |
| POST /api/admin/question/import | Excel导入 | 是 |
| GET /api/admin/user/list | 用户列表 | 是 |
| GET /api/admin/stat/summary | 数据统计 | 是 |

---

## 🚀 部署指南

### 环境要求

| 软件 | 版本要求 |
|------|---------|
| PHP | >= 8.1 |
| MySQL | 5.7+ / 8.0 |
| Nginx / Apache | 最新稳定版 |
| Composer | 2.x |
| Git | 最新版 |

### 1. 服务器环境配置

#### 安装 PHP 8.1+

```bash
# Ubuntu/Debian
sudo apt update
sudo apt install -y php8.1 php8.1-fpm php8.1-mysql php8.1-cli php8.1-curl php8.1-json php8.1-mbstring php8.1-xml php8.1-zip php8.1-gd

# CentOS/RHEL
sudo yum install epel-release
sudo yum install -y php81-php php81-php-fpm php81-php-mysqlnd php81-php-cli php81-php-curl php81-php-mbstring php81-php-xml php81-php-zip php81-php-gd
```

#### 安装 MySQL

```bash
# Ubuntu/Debian
sudo apt install -y mysql-server

# CentOS
sudo yum install -y mysql-server

# 启动服务
sudo systemctl start mysql
sudo systemctl enable mysql
```

#### 安装 Composer

```bash
curl -sS https://getcomposer.org/installer | php
sudo mv composer.phar /usr/local/bin/composer
chmod +x /usr/local/bin/composer
composer --version
```

### 2. 创建数据库

```bash
# 登录 MySQL
mysql -u root -p

# 创建数据库
CREATE DATABASE moto_db DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;

# 创建用户（可选，更安全）
CREATE USER 'moto_user'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON moto_db.* TO 'moto_user'@'localhost';
FLUSH PRIVILEGES;

EXIT;
```

### 3. 项目部署

#### 方式一：Git 拉取（推荐）

```bash
# 进入网站目录
cd /www/wwwroot/moto.zd16688.com

# 克隆仓库
git clone https://github.com/Simonchan-openclaw/moto.git .

# 切换到后端目录
cd moto/backend

# 安装 Composer 依赖
composer install --no-dev

# 返回上级目录
cd ..
```

#### 方式二：手动上传

将项目文件上传到服务器，解压到网站目录。

### 4. 导入数据库

```bash
# 导入 SQL 文件
mysql -u root -p moto_db < /www/wwwroot/moto.zd16688.com/moto/sql/moto_db.sql

# 如果指定了数据库用户
mysql -u moto_user -p moto_db < /www/wwwroot/moto.zd16688.com/moto/sql/moto_db.sql
```

### 5. 配置环境变量

```bash
# 进入后端目录
cd /www/wwwroot/moto.zd16688.com/moto/backend

# 复制环境变量示例文件
cp .env.example .env

# 编辑配置文件
vi .env
```

#### .env 配置文件说明

```ini
# 应用配置
APP_DEBUG = false

# 数据库配置（必填）
DB_HOST = 127.0.0.1
DB_DATABASE = moto_db
DB_USERNAME = your_db_username
DB_PASSWORD = your_db_password
DB_PORT = 3306

# JWT配置
JWT_SECRET = moto_exam_jwt_secret_key_2024
JWT_EXPIRE = 604800

# Redis配置（可选，如不使用可留空）
REDIS_HOST = 127.0.0.1
REDIS_PORT = 6379
REDIS_PASSWORD =
```

### 6. 设置目录权限

```bash
cd /www/wwwroot/moto.zd16688.com/moto/backend

# 设置运行时目录权限
chmod -R 777 runtime/

# 设置存储目录权限
chmod -R 777 storage/

# 设置配置目录权限（可选）
chmod -R 755 config/
```

### 7. Nginx 配置

> **重要**：ThinkPHP8 的 Web 根目录必须是 `backend/public/`，不是 `backend/`！

```nginx
server {
    listen 80;
    server_name moto.zd16688.com;
    root /www/wwwroot/moto.zd16688.com/moto/backend/public;
    index index.php index.html;

    # 防止访问 .env 文件
    location ~ /\.env {
        deny all;
    }

    # API 请求处理
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # PHP-FPM 处理
    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.1-fpm.sock;
        fastcgi_index index.php;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        
        # PHP 配置
        fastcgi_connect_timeout 300;
        fastcgi_send_timeout 300;
        fastcgi_read_timeout 300;
    }

    # H5 静态文件（将 /h5 路径映射到 H5 目录）
    location /h5 {
        alias /www/wwwroot/moto.zd16688.com/moto/h5;
        index index.html;
        try_files $uri $uri/ /h5/index.html;
        
        # 静态文件缓存
        location ~* \.(js|css|png|jpg|jpeg|gif|ico|svg|woff|woff2|ttf|eot)$ {
            expires 30d;
            add_header Cache-Control "public, immutable";
        }
    }

    # 禁止访问隐藏文件
    location ~ /\. {
        deny all;
    }

    # 日志配置
    access_log /var/log/nginx/moto_access.log;
    error_log /var/log/nginx/moto_error.log;
}
```

### 8. HTTPS 配置（推荐）

```nginx
server {
    listen 443 ssl http2;
    server_name moto.zd16688.com;
    root /www/wwwroot/moto.zd16688.com/moto/backend/public;
    index index.php index.html;

    # SSL 证书配置
    ssl_certificate /path/to/your/ssl/cert.pem;
    ssl_certificate_key /path/to/your/ssl/cert.key;
    ssl_session_timeout 1d;
    ssl_session_cache shared:SSL:50m;
    ssl_protocols TLSv1.2 TLSv1.3;
    ssl_ciphers ECDHE-ECDSA-AES128-GCM-SHA256:ECDHE-RSA-AES128-GCM-SHA256;
    ssl_prefer_server_ciphers off;

    # 其他配置同上...
}

# HTTP 自动跳转 HTTPS
server {
    listen 80;
    server_name moto.zd16688.com;
    return 301 https://$server_name$request_uri;
}
```

### 9. PHP-FPM 配置

确保 PHP-FPM 使用正确的用户和权限：

```bash
# 检查 PHP-FPM 配置文件
sudo vi /etc/php/8.1/fpm/pool.d/www.conf

# 确保以下配置正确
user = www-data
group = www-data
listen = /var/run/php/php8.1-fpm.sock
listen.owner = www-data
listen.group = www-data

# 重启 PHP-FPM
sudo systemctl restart php8.1-fpm
```

### 10. 测试部署

```bash
# 重启 Nginx
sudo systemctl restart nginx

# 测试 API 健康检查
curl http://moto.zd16688.com/api/index/health

# 预期返回：
# {"msg":"moto exam api running","version":"1.0.0"}

# 测试 H5 访问
curl http://moto.zd16688.com/h5/

# 测试数据库连接（在后端目录执行）
cd /www/wwwroot/moto.zd16688.com/moto/backend
php think
```

### 11. 管理后台访问

| 项目 | 地址 |
|------|------|
| API 基础地址 | `https://moto.zd16688.com/api/` |
| H5 学员端 | `https://moto.zd16688.com/h5/` |
| 默认管理员账号 | `admin` |
| 默认管理员密码 | `admin123` |

> ⚠️ **重要**：首次登录后请立即修改默认密码！

### 12. 常见问题

#### Q1: 提示 "No input file specified"

```bash
# 检查 Nginx 配置中的 fastcgi_param SCRIPT_FILENAME
# 确保路径指向正确的 public/index.php

# 检查 PHP-FPM socket 路径
ls -la /var/run/php/
```

#### Q2: 提示权限不足

```bash
cd /www/wwwroot/moto.zd16688.com/moto/backend
chmod -R 777 runtime/
chmod -R 777 storage/
chmod -R 755 .env
```

#### Q3: 数据库连接失败

```bash
# 检查 .env 数据库配置
cat .env | grep DB_

# 测试数据库连接
mysql -h your_db_host -u your_db_user -p your_db_name
```

#### Q4: 页面空白

```bash
# 开启调试模式查看错误
vi .env
# 将 APP_DEBUG = false 改为 APP_DEBUG = true

# 查看 runtime/log/ 目录下的日志文件
tail -f runtime/log/$(date +Y-m-d).log
```

#### Q5: H5 无法访问后端 API

```bash
# 检查 Nginx 反向代理配置
# 确保 /api/ 请求指向 backend/public/index.php

# 检查 H5 的 API 地址配置
vi /www/wwwroot/moto.zd16688.com/moto/h5/js/config.js
```

### 13. 更新部署

```bash
cd /www/wwwroot/moto.zd16688.com/moto

# 拉取最新代码
git pull origin main

# 更新 Composer 依赖（如有更新）
cd backend
composer update --no-dev

# 清除缓存
php think clear
```

---

## 📝 开发指南

### 添加新接口

1. 在 `app/controller/api/` 目录创建或修改控制器
2. 在 `route/app.php` 添加路由规则
3. 使用 `jsonSuccess()` 和 `jsonError()` 返回响应

### 示例：新增接口

```php
// app/controller/api/Example.php
<?php
namespace app\controller\api;

class Example
{
    public function hello()
    {
        return jsonSuccess(['msg' => 'Hello Moto!']);
    }
}
```

```php
// route/app.php 添加路由
Route::get('example/hello', 'api.Example/hello');
```

### 模型使用

```php
// 使用 ThinkPHP ORM
$user = \app\model\User::find(1);
$list = \app\model\User::where('status', 1)->select();

// 创建记录
$user = \app\model\User::create([
    'phone' => '13800138000',
    'nickname' => '测试用户'
]);
```

---

## 🔗 相关链接

- GitHub 仓库：https://github.com/Simonchan-openclaw/moto
- API 文档：https://github.com/Simonchan-openclaw/moto/blob/main/docs/api.md
- 问题反馈：https://github.com/Simonchan-openclaw/moto/issues

---

## 📄 许可证

MIT License

## 👤 作者

Simonchan-openclaw
