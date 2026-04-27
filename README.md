# 摩托车驾驶证笔试在线练习系统

> 摩托车驾驶证笔试在线练习系统，包含 H5 移动端刷题 + ThinkPHP8 管理后台

## 📖 项目简介

本系统为摩托车驾驶证笔试学习提供完整的在线练习解决方案，包含学员使用的 H5 刷题端和管理员使用的后台管理系统。

## 🛠 技术栈

### H5 移动端
- **前端框架**：HTML5 + CSS3 + JavaScript
- **UI 库**：jQuery
- **通信**：Ajax + RESTful API
- **适配**：响应式设计，适配手机/平板/PC

### 管理后台
- **框架**：ThinkPHP 8.x
- **数据库**：MySQL 5.7+ / MySQL 8.0
- **缓存**：Redis（可选）
- **认证**：JWT Token

## 📁 目录结构

```
moto/
├── backend/          # ThinkPHP8 管理后台源码
│   ├── app/          # 应用目录（控制器/模型/服务）
│   ├── config/       # 配置文件
│   ├── route/        # 路由定义
│   ├── public/       # 入口文件及静态资源
│   └── ...
├── h5/               # H5 移动端 HTML + jQuery 源码
│   ├── css/          # 样式文件
│   ├── js/           # JavaScript 文件
│   ├── images/       # 图片资源
│   └── index.html    # 入口页面
├── docs/             # 项目文档
│   ├── api/          # 接口文档
│   ├── deploy/       # 部署文档
│   └── requirements/ # 需求文档
├── sql/              # 数据库 SQL 文件
│   └── init.sql      # 初始化脚本
├── README.md         # 项目说明文档
└── .gitignore        # Git 忽略配置
```

## 🚀 快速开始

### 环境要求

- PHP 8.1+
- MySQL 5.7+
- Node.js 16+（可选，用于构建工具）
- Composer 2.x

### 安装部署

详见 [部署文档](./docs/deploy/)

## 📋 功能模块

### H5 刷题端
- 章节练习
- 模拟考试
- 错题本
- 收藏题目
- 考试记录
- 成绩统计

### 管理后台
- 题库管理（题目增删改查）
- 分类管理
- 用户管理
- 成绩统计
- 系统设置

## 🔧 开发规范

- 遵循 PSR-12 代码规范
- 使用 Git Flow 分支管理
- 所有 API 遵循 RESTful 设计

## 📄 分支说明

| 分支 | 用途 |
|------|------|
| main | 主分支，稳定版本 |
| test | 测试分支 |
| dev | 开发分支 |

## 📝 许可证

MIT License

## 👤 作者

Simonchan-openclaw

## 🔗 链接

- GitHub 仓库：https://github.com/Simonchan-openclaw/moto
- 问题反馈：https://github.com/Simonchan-openclaw/moto/issues
