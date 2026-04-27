# 摩托车笔试题库系统 API 接口文档

> 版本：v1.1  
> 更新日期：2026-04-27  
> 基础路径：`/api`

---

## 目录

- [概述](#概述)
- [公共说明](#公共说明)
- [教练端激活模块](#教练端激活模块) ⭐ 新增
- [学员端激活模块](#学员端激活模块) ⭐ 新增
- [用户模块](#用户模块)
- [题目模块](#题目模块)
- [答题模块](#答题模块)
- [收藏模块](#收藏模块)
- [考试模块](#考试模块)
- [管理后台API](#管理后台api)

---

## 概述

本文档描述了摩托车笔试题库系统的全部 API 接口，供 H5 移动端和管理后台开发使用。

### 系统模块

| 模块 | 说明 |
|------|------|
| 用户模块 | 用户注册、登录、个人信息管理 |
| 题目模块 | 章节列表、题目查询、题目详情 |
| 答题模块 | 答题提交、错题本管理 |
| 收藏模块 | 题目收藏管理 |
| 考试模块 | 模拟考试、成绩记录 |
| **教练端激活模块** | **教练充值、激活学员、余额管理** ⭐ |
| **学员端激活模块** | **学员激活、设备绑定、状态查询** ⭐ |
| 管理后台API | 管理员登录、题目管理、批量导入 |

---

## 公共说明

### 认证方式

除登录接口外，所有接口需要在请求头中携带 Token 进行身份验证：

```
Authorization: Bearer {token}
```

> **教练端 Token 格式：** `Bearer {coach_id}`  
> **学员端 Token 格式：** `Bearer {user_id}`  
> 实际生产环境应使用 JWT 或 Server 端 Session。

### 响应格式

所有接口统一使用 JSON 格式返回，结构如下：

```json
{
  "code": 200,
  "message": "操作成功",
  "data": {}
}
```

### 响应码说明

| 响应码 | 说明 |
|--------|------|
| 200 | 成功 |
| 400 | 请求参数错误 |
| 401 | 未授权 / Token 失效 |
| 403 | 无权限访问 |
| 404 | 资源不存在 |
| 500 | 服务器内部错误 |

---

## 教练端激活模块

> **模块说明：** 教练预充值余额 → 输入学员手机号激活（每次扣18元）→ 学员设备绑定  
> **前置条件：** 教练需先注册/登录获取 Token

### 1. 教练登录

**接口名称：** 教练登录

**请求方式：** `POST`

**接口路径：** `/api/coach/login`

**功能描述：** 教练使用手机号和密码登录系统。

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| phone | string | 是 | 手机号（11位数字） |
| password | string | 是 | 登录密码 |

#### 请求示例

```json
{
  "phone": "13800138000",
  "password": "admin123"
}
```

#### 响应参数

| 参数名 | 类型 | 说明 |
|--------|------|------|
| token | string | 访问令牌 |
| coach_id | integer | 教练ID |
| phone | string | 手机号 |
| real_name | string | 真实姓名 |
| balance | decimal | 账户余额 |

#### 响应示例

```json
{
  "code": 200,
  "message": "登录成功",
  "data": {
    "token": "a1b2c3d4e5f6...",
    "coach_id": 1001,
    "phone": "13800138000",
    "real_name": "张教练",
    "balance": "180.00"
  }
}
```

---

### 2. 教练注册

**接口名称：** 教练注册

**请求方式：** `POST`

**接口路径：** `/api/coach/register`

**功能描述：** 教练注册新账号。

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| phone | string | 是 | 手机号（11位数字） |
| password | string | 是 | 登录密码（至少6位） |
| code | string | 是 | 手机验证码 |
| real_name | string | 否 | 真实姓名 |

#### 请求示例

```json
{
  "phone": "13800138000",
  "password": "admin123",
  "code": "123456",
  "real_name": "张教练"
}
```

#### 响应示例

```json
{
  "code": 200,
  "message": "注册成功",
  "data": {
    "coach_id": 1001
  }
}
```

---

### 3. 获取教练余额

**接口名称：** 获取教练余额

**请求方式：** `GET`

**接口路径：** `/api/coach/balance`

**功能描述：** 获取当前教练的账户余额和累计充值金额。

#### 请求头

```
Authorization: Bearer {coach_id}
```

#### 响应参数

| 参数名 | 类型 | 说明 |
|--------|------|------|
| balance | decimal | 当前余额（元） |
| total_recharged | decimal | 累计充值金额（元） |

#### 响应示例

```json
{
  "code": 200,
  "message": "操作成功",
  "data": {
    "balance": "180.00",
    "total_recharged": "500.00"
  }
}
```

---

### 4. 教练充值

**接口名称：** 教练充值

**请求方式：** `POST`

**接口路径：** `/api/coach/recharge`

**功能描述：** 教练账户充值（微信支付/支付宝），最低18元起充。

#### 请求头

```
Authorization: Bearer {coach_id}
```

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| amount | decimal | 是 | 充值金额（元），最低18元 |
| pay_method | integer | 否 | 支付方式：1=微信支付（默认）, 2=支付宝 |

#### 请求示例

```json
{
  "amount": "100.00",
  "pay_method": 1
}
```

#### 响应参数

| 参数名 | 类型 | 说明 |
|--------|------|------|
| record_id | integer | 充值记录ID |
| trade_no | string | 交易流水号 |
| amount | decimal | 充值金额 |
| balance | decimal | 充值后余额 |
| message | string | 提示信息 |

#### 响应示例

```json
{
  "code": 200,
  "message": "充值成功",
  "data": {
    "record_id": 5001,
    "trade_no": "WX202604271200001234",
    "amount": "100.00",
    "balance": "280.00",
    "message": "充值成功"
  }
}
```

#### 错误响应

```json
{
  "code": 400,
  "message": "最低充值金额为18元",
  "data": null
}
```

---

### 5. 教练激活学员

**接口名称：** 教练激活学员

**请求方式：** `POST`

**接口路径：** `/api/coach/activate`

**功能描述：** 教练输入学员手机号，系统扣除18元余额并生成激活码。

#### 请求头

```
Authorization: Bearer {coach_id}
```

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| student_phone | string | 是 | 学员手机号（11位数字） |

#### 请求示例

```json
{
  "student_phone": "13900139000"
}
```

#### 响应参数

| 参数名 | 类型 | 说明 |
|--------|------|------|
| activate_code | string | 激活码（16位字母数字） |
| student_phone | string | 学员手机号 |
| amount | decimal | 扣款金额（18.00元） |
| expire_at | string | 激活码到期时间 |
| balance | decimal | 扣款后余额 |
| message | string | 提示信息 |

#### 响应示例

```json
{
  "code": 200,
  "message": "激活码生成成功",
  "data": {
    "activate_code": "K7M9HX2QN4LPW8TZ",
    "student_phone": "13900139000",
    "amount": "18.00",
    "expire_at": "2026-05-27 12:00:00",
    "balance": "162.00",
    "message": "激活码生成成功，请发送给学员"
  }
}
```

#### 错误响应（余额不足）

```json
{
  "code": 400,
  "message": "余额不足，当前余额 0.00 元，需要 18.00 元",
  "data": {
    "balance": "0.00",
    "need": "18.00"
  }
}
```

---

### 6. 获取激活记录列表

**接口名称：** 获取激活记录列表

**请求方式：** `GET`

**接口路径：** `/api/coach/activation_list`

**功能描述：** 获取教练的所有激活记录，支持按状态筛选。

#### 请求头

```
Authorization: Bearer {coach_id}
```

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| page | integer | 否 | 页码（默认：1） |
| page_size | integer | 否 | 每页条数（默认：20，最大：50） |
| status | integer | 否 | 激活状态：0=待激活, 1=已激活, 2=已失效, 3=已退款 |

#### 请求示例

```
GET /api/coach/activation_list?page=1&page_size=20&status=0
```

#### 响应参数

| 参数名 | 类型 | 说明 |
|--------|------|------|
| list | array | 激活记录列表 |
| total | integer | 总条数 |
| page | integer | 当前页码 |
| page_size | integer | 每页条数 |
| total_pages | integer | 总页数 |

##### list 激活记录列表项

| 参数名 | 类型 | 说明 |
|--------|------|------|
| id | integer | 记录ID |
| student_phone | string | 学员手机号 |
| student_phone_mask | string | 学员手机号（脱敏：138****9000） |
| student_nickname | string | 学员昵称（已激活时） |
| activate_code | string | 激活码 |
| amount_deducted | decimal | 扣款金额 |
| activate_status | integer | 激活状态 |
| status_text | string | 状态文字 |
| activated_at | string | 激活时间 |
| expire_at | string | 到期时间 |
| create_time | string | 创建时间（教练操作时间） |

#### 响应示例

```json
{
  "code": 200,
  "message": "操作成功",
  "data": {
    "list": [
      {
        "id": 3001,
        "student_phone": "13900139000",
        "student_phone_mask": "139****9000",
        "student_nickname": "摩托学员",
        "activate_code": "K7M9HX2QN4LPW8TZ",
        "amount_deducted": "18.00",
        "activate_status": 1,
        "status_text": "已激活",
        "activated_at": "2026-04-27 10:30:00",
        "expire_at": "2026-05-27 10:30:00",
        "create_time": "2026-04-27 10:00:00"
      },
      {
        "id": 3002,
        "student_phone": "13700137000",
        "student_phone_mask": "137****7000",
        "student_nickname": null,
        "activate_code": "A8B3CD5E7F9H2J4K",
        "amount_deducted": "18.00",
        "activate_status": 0,
        "status_text": "待激活",
        "activated_at": null,
        "expire_at": "2026-05-27 12:00:00",
        "create_time": "2026-04-27 12:00:00"
      }
    ],
    "total": 50,
    "page": 1,
    "page_size": 20,
    "total_pages": 3
  }
}
```

---

### 7. 退款（作废激活码）

**接口名称：** 退款

**请求方式：** `POST`

**接口路径：** `/api/coach/refund`

**功能描述：** 教练对未激活的激活码进行退款，款项退回账户余额。

#### 请求头

```
Authorization: Bearer {coach_id}
```

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| activation_id | integer | 是 | 激活记录ID |

#### 请求示例

```json
{
  "activation_id": 3002
}
```

#### 响应参数

| 参数名 | 类型 | 说明 |
|--------|------|------|
| refund_amount | decimal | 退款金额 |
| balance | decimal | 退款后余额 |

#### 响应示例

```json
{
  "code": 200,
  "message": "退款成功",
  "data": {
    "refund_amount": "18.00",
    "balance": "180.00"
  }
}
```

#### 错误响应

```json
{
  "code": 400,
  "message": "已激活的记录无法退款",
  "data": null
}
```

---

### 8. 获取充值记录列表

**接口名称：** 获取充值记录列表

**请求方式：** `GET`

**接口路径：** `/api/coach/recharge_list`

**功能描述：** 获取教练的充值记录历史。

#### 请求头

```
Authorization: Bearer {coach_id}
```

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| page | integer | 否 | 页码（默认：1） |
| page_size | integer | 否 | 每页条数（默认：20，最大：50） |

#### 响应参数

| 参数名 | 类型 | 说明 |
|--------|------|------|
| list | array | 充值记录列表 |
| total | integer | 总条数 |
| page | integer | 当前页码 |
| page_size | integer | 每页条数 |
| total_pages | integer | 总页数 |

##### list 充值记录项

| 参数名 | 类型 | 说明 |
|--------|------|------|
| id | integer | 记录ID |
| amount | decimal | 充值金额 |
| pay_method | integer | 支付方式：1=微信支付, 2=支付宝 |
| trade_no | string | 交易流水号 |
| status | integer | 状态：0=失败, 1=成功, 2=退款 |
| create_time | string | 充值时间 |

#### 响应示例

```json
{
  "code": 200,
  "message": "操作成功",
  "data": {
    "list": [
      {
        "id": 5002,
        "amount": "100.00",
        "pay_method": 1,
        "trade_no": "WX202604271200001234",
        "status": 1,
        "create_time": "2026-04-27 12:00:00"
      }
    ],
    "total": 5,
    "page": 1,
    "page_size": 20,
    "total_pages": 1
  }
}
```

---

## 学员端激活模块

> **模块说明：** 学员输入激活码 → 前端获取设备ID → 提交激活 → 设备绑定成功  
> **前置条件：** 学员需先登录获取 Token（H5端），激活后即可使用题库功能

### 9. 验证激活码

**接口名称：** 验证激活码

**请求方式：** `POST`

**接口路径：** `/api/student/verify_code`

**功能描述：** 学员在激活前先验证激活码是否有效。

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| activate_code | string | 是 | 激活码（16位） |

#### 请求示例

```json
{
  "activate_code": "K7M9HX2QN4LPW8TZ"
}
```

#### 响应参数

| 参数名 | 类型 | 说明 |
|--------|------|------|
| status | integer | 激活码状态：0=待激活, 1=已激活, 2=已失效/过期 |
| status_text | string | 状态文字 |
| expire_at | string | 到期时间 |
| coach_name | string | 教练姓名 |
| amount | decimal | 扣款金额 |
| message | string | 提示信息 |

#### 响应示例

```json
{
  "code": 200,
  "message": "操作成功",
  "data": {
    "status": 0,
    "status_text": "待激活",
    "expire_at": "2026-05-27 12:00:00",
    "coach_name": "张教练",
    "amount": "18.00",
    "message": "激活码有效，可激活"
  }
}
```

---

### 10. 学员激活（绑定设备）

**接口名称：** 学员激活

**请求方式：** `POST`

**接口路径：** `/api/student/activate`

**功能描述：** 学员使用激活码激活题库，并绑定当前设备。一个激活码只能绑定一台设备。

#### 请求头

```
Authorization: Bearer {user_id}
Content-Type: application/json
```

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| activate_code | string | 是 | 激活码（16位） |
| device_id | string | 是 | 设备ID（H5前端生成，建议使用 uuid） |

#### 请求示例

```json
{
  "activate_code": "K7M9HX2QN4LPW8TZ",
  "device_id": "550e8400-e29b-41d4-a716-446655440000"
}
```

#### 响应参数

| 参数名 | 类型 | 说明 |
|--------|------|------|
| expire_at | string | 激活到期时间 |
| coach_phone | string | 教练手机号（脱敏） |
| message | string | 提示信息 |

#### 响应示例

```json
{
  "code": 200,
  "message": "激活成功",
  "data": {
    "expire_at": "2026-05-27 10:30:00",
    "coach_phone": "138****8000",
    "message": "激活成功"
  }
}
```

#### 错误响应（设备已被使用）

```json
{
  "code": 400,
  "message": "该设备已被学员 139****9000 激活使用",
  "data": null
}
```

#### 错误响应（激活码已过期）

```json
{
  "code": 400,
  "message": "激活码已过期",
  "data": null
}
```

---

### 11. 查询激活状态

**接口名称：** 查询激活状态

**请求方式：** `GET`

**接口路径：** `/api/student/check`

**功能描述：** 学员查询当前账号/设备的激活状态。

#### 请求头

```
Authorization: Bearer {user_id}
```

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| device_id | string | 是 | 设备ID |

#### 请求示例

```
GET /api/student/check?device_id=550e8400-e29b-41d4-a716-446655440000
```

#### 响应参数

| 参数名 | 类型 | 说明 |
|--------|------|------|
| activated | boolean | 是否已激活 |
| expire_at | string | 到期时间 |
| coach_phone | string | 教练手机号（脱敏） |
| activated_at | string | 激活时间 |
| message | string | 提示信息 |

#### 响应示例（已激活）

```json
{
  "code": 200,
  "message": "操作成功",
  "data": {
    "activated": true,
    "expire_at": "2026-05-27 10:30:00",
    "coach_phone": "138****8000",
    "activated_at": "2026-04-27 10:30:00",
    "message": "激活有效"
  }
}
```

#### 响应示例（未激活）

```json
{
  "code": 200,
  "message": "操作成功",
  "data": {
    "activated": false,
    "message": "未找到激活记录"
  }
}
```

#### 响应示例（已过期）

```json
{
  "code": 200,
  "message": "操作成功",
  "data": {
    "activated": false,
    "message": "激活已过期",
    "expire_at": "2026-04-27 10:30:00"
  }
}
```

---

## 用户模块

### 12. 发送验证码

**接口名称：** 发送手机验证码

**请求方式：** `POST`

**接口路径：** `/api/user/send_code`

**功能描述：** 向指定手机号发送登录/注册验证码，验证码有效期5分钟。

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| phone | string | 是 | 手机号（11位数字） |
| type | string | 是 | 用途类型：`login`（登录）/ `register`（注册） |

#### 请求示例

```json
{
  "phone": "13800138000",
  "type": "login"
}
```

#### 响应参数

| 参数名 | 类型 | 说明 |
|--------|------|------|
| success | boolean | 是否发送成功 |
| message | string | 提示信息 |
| code | string | 验证码（测试环境使用，生产环境不返回） |

#### 响应示例

```json
{
  "code": 200,
  "message": "验证码发送成功",
  "data": {
    "success": true,
    "message": "验证码发送成功",
    "code": "123456"
  }
}
```

---

### 13. 用户登录

**接口名称：** 手机号验证码登录/注册

**请求方式：** `POST`

**接口路径：** `/api/user/login`

**功能描述：** 使用手机号和验证码进行登录，如用户不存在则自动注册。

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| phone | string | 是 | 手机号（11位数字） |
| code | string | 是 | 验证码（6位数字） |

#### 请求示例

```json
{
  "phone": "13800138000",
  "code": "123456"
}
```

#### 响应参数

| 参数名 | 类型 | 说明 |
|--------|------|------|
| token | string | 访问令牌 |
| userInfo | object | 用户信息对象 |

##### userInfo 用户信息对象

| 参数名 | 类型 | 说明 |
|--------|------|------|
| id | integer | 用户ID |
| nickname | string | 昵称 |
| avatar | string | 头像URL |
| phone | string | 手机号 |
| create_time | string | 注册时间（格式：Y-m-d H:i:s） |

#### 响应示例

```json
{
  "code": 200,
  "message": "登录成功",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "userInfo": {
      "id": 10001,
      "nickname": "摩托学员",
      "avatar": "https://example.com/avatar/default.png",
      "phone": "13800138000",
      "create_time": "2026-04-27 10:00:00"
    }
  }
}
```

---

## 题目模块

### 14. 获取章节列表

**接口名称：** 获取章节树形列表

**请求方式：** `GET`

**接口路径：** `/api/question/chapters`

**功能描述：** 获取指定科目的章节列表，以树形结构返回。

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| subject | integer | 是 | 科目：1（科目一）/ 4（科目四） |

#### 请求示例

```
GET /api/question/chapters?subject=1
```

#### 响应参数

| 参数名 | 类型 | 说明 |
|--------|------|------|
| chapter_id | integer | 章节ID |
| chapter_name | string | 章节名称 |
| subject | integer | 所属科目 |
| parent_id | integer | 父章节ID（0表示顶级章节） |
| sort_order | integer | 排序序号 |
| children | array | 子章节列表 |

#### 响应示例

```json
{
  "code": 200,
  "message": "success",
  "data": [
    {
      "chapter_id": 1,
      "chapter_name": "道路交通安全法律、法规和规章",
      "subject": 1,
      "parent_id": 0,
      "sort_order": 1,
      "children": []
    }
  ]
}
```

---

### 15. 获取题目列表

**接口名称：** 获取题目列表（支持筛选）

**请求方式：** `GET`

**接口路径：** `/api/question/list`

**功能描述：** 获取题目列表，支持按科目、题型、章节、关键词筛选，支持分页。

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| subject | integer | 否 | 科目：1（科目一）/ 4（科目四） |
| question_type | integer | 否 | 题型：1（选择题）/ 2（判断题） |
| chapter_id | integer | 否 | 章节ID |
| keyword | string | 否 | 关键词搜索（题目内容） |
| page | integer | 否 | 页码（默认：1） |
| page_size | integer | 否 | 每页条数（默认：20，最大：50） |

#### 请求示例

```
GET /api/question/list?subject=1&question_type=1&page=1&page_size=20
```

#### 响应参数

| 参数名 | 类型 | 说明 |
|--------|------|------|
| list | array | 题目列表 |
| total | integer | 总条数 |
| page | integer | 当前页码 |
| page_size | integer | 每页条数 |
| total_pages | integer | 总页数 |

##### list 题目列表项

| 参数名 | 类型 | 说明 |
|--------|------|------|
| id | integer | 题目ID |
| subject | integer | 所属科目 |
| question_type | integer | 题型：1（选择题）/ 2（判断题） |
| chapter_id | integer | 所属章节ID |
| chapter_name | string | 所属章节名称 |
| content | string | 题目内容 |
| options | array | 选项列表（判断题无此字段） |
| is_collected | boolean | 是否已收藏 |

#### 响应示例

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "list": [
      {
        "id": 1001,
        "subject": 1,
        "question_type": 1,
        "chapter_id": 1,
        "chapter_name": "道路交通安全法律",
        "content": "机动车驾驶人应当依法遵守道路交通安全法律、法规的规定，按照（）操作。",
        "options": [
          {"option_key": "A", "option_content": "习惯"},
          {"option_key": "B", "option_content": "规定"},
          {"option_key": "C", "option_content": "心情"},
          {"option_key": "D", "option_content": "经验"}
        ],
        "is_collected": false
      }
    ],
    "total": 500,
    "page": 1,
    "page_size": 20,
    "total_pages": 25
  }
}
```

---

### 16. 获取题目详情

**接口名称：** 获取题目详情

**请求方式：** `GET`

**接口路径：** `/api/question/detail`

**功能描述：** 获取指定题目的完整信息，包括选项、答案、解析。

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| id | integer | 是 | 题目ID |

#### 请求示例

```
GET /api/question/detail?id=1001
```

#### 响应参数

| 参数名 | 类型 | 说明 |
|--------|------|------|
| id | integer | 题目ID |
| subject | integer | 所属科目：1（科目一）/ 4（科目四） |
| question_type | integer | 题型：1（选择题）/ 2（判断题） |
| chapter_id | integer | 所属章节ID |
| chapter_name | string | 所属章节名称 |
| content | string | 题目内容 |
| options | array | 选项列表 |
| answer | string | 正确答案（A/B/C/D 或 true/false） |
| analysis | string | 题目解析 |
| difficulty | integer | 难度等级：1（简单）/ 2（中等）/ 3（困难） |
| is_collected | boolean | 是否已收藏 |

#### 响应示例

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "id": 1001,
    "subject": 1,
    "question_type": 1,
    "chapter_id": 1,
    "chapter_name": "道路交通安全法律",
    "content": "机动车驾驶人应当依法遵守道路交通安全法律、法规的规定，按照（）操作。",
    "options": [
      {"option_key": "A", "option_content": "习惯"},
      {"option_key": "B", "option_content": "规定"},
      {"option_key": "C", "option_content": "心情"},
      {"option_key": "D", "option_content": "经验"}
    ],
    "answer": "B",
    "analysis": "根据《道路交通安全法》第二十二条规定，机动车驾驶人应当按照道路交通安全法律、法规的规定，按照操作规范安全驾驶、文明驾驶。",
    "difficulty": 1,
    "is_collected": false
  }
}
```

---

## 答题模块

### 17. 提交答题

**接口名称：** 提交单条答题记录

**请求方式：** `POST`

**接口路径：** `/api/answer/submit`

**功能描述：** 提交用户答题记录，系统自动判断对错并返回结果。

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| question_id | integer | 是 | 题目ID |
| user_answer | string | 是 | 用户答案（A/B/C/D 或 true/false） |
| answer_time | integer | 是 | 答题用时（秒） |

#### 请求示例

```json
{
  "question_id": 1001,
  "user_answer": "B",
  "answer_time": 15
}
```

#### 响应参数

| 参数名 | 类型 | 说明 |
|--------|------|------|
| is_correct | boolean | 是否答对 |
| correct_answer | string | 正确答案 |
| analysis | string | 题目解析 |

#### 响应示例

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "is_correct": true,
    "correct_answer": "B",
    "analysis": "根据《道路交通安全法》第二十二条规定..."
  }
}
```

---

### 18. 获取错题列表

**接口名称：** 获取用户错题列表

**请求方式：** `GET`

**接口路径：** `/api/answer/error_list`

**功能描述：** 获取当前用户的错题记录列表，用于错题复习。

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| page | integer | 否 | 页码（默认：1） |
| page_size | integer | 否 | 每页条数（默认：20，最大：50） |

#### 响应示例

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "list": [],
    "total": 0,
    "page": 1,
    "page_size": 20,
    "total_pages": 0
  }
}
```

---

## 收藏模块

### 19. 收藏/取消收藏

**接口名称：** 收藏或取消收藏题目

**请求方式：** `POST`

**接口路径：** `/api/collection/toggle`

**功能描述：** 切换题目的收藏状态。

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| question_id | integer | 是 | 题目ID |
| status | integer | 是 | 收藏状态：1（收藏）/ 0（取消收藏） |

#### 请求示例

```json
{
  "question_id": 1001,
  "status": 1
}
```

#### 响应示例

```json
{
  "code": 200,
  "message": "收藏成功",
  "data": {
    "success": true,
    "message": "题目已收藏"
  }
}
```

---

## 考试模块

### 20. 生成模拟试卷

**接口名称：** 生成模拟试卷

**请求方式：** `POST`

**接口路径：** `/api/exam/generate`

**功能描述：** 根据指定条件随机生成一套模拟试卷。

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| subject | integer | 是 | 科目：1（科目一）/ 4（科目四） |
| question_count | integer | 否 | 题目数量（默认：50，最大：100） |

#### 响应示例

```json
{
  "code": 200,
  "message": "试卷生成成功",
  "data": {
    "exam_id": "EXAM202604270001",
    "question_ids": [1001, 1005, 1008, 1010, 1015],
    "total_time": 45
  }
}
```

---

### 21. 提交试卷

**接口名称：** 提交试卷并计算成绩

**请求方式：** `POST`

**接口路径：** `/api/exam/submit`

**功能描述：** 提交用户作答的试卷，系统计算得分并保存考试记录。

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| exam_id | string | 是 | 试卷ID |
| answers | object | 是 | 答案集合，键为题目ID，值为用户答案 |
| time_used | integer | 是 | 实际用时（秒） |

#### 响应示例

```json
{
  "code": 200,
  "message": "提交成功",
  "data": {
    "score": 92,
    "correct_count": 46,
    "total_questions": 50,
    "exam_record_id": 8001,
    "wrong_questions": []
  }
}
```

---

### 22. 获取考试成绩记录

**接口名称：** 获取考试成绩列表

**请求方式：** `GET`

**接口路径：** `/api/exam/record_list`

**功能描述：** 获取当前用户的考试成绩历史记录。

#### 响应示例

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "list": [],
    "total": 0,
    "page": 1,
    "page_size": 20,
    "total_pages": 0
  }
}
```

---

## 管理后台API

### 23. 管理员登录

**接口名称：** 管理员登录

**请求方式：** `POST`

**接口路径：** `/api/admin/login`

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| username | string | 是 | 管理员用户名 |
| password | string | 是 | 登录密码（MD5加密传输） |

#### 响应示例

```json
{
  "code": 200,
  "message": "登录成功",
  "data": {
    "token": "eyJhbGciOiJIUzI1NiIsInR5cCI6IkpXVCJ9...",
    "adminInfo": {
      "id": 1,
      "username": "admin",
      "nickname": "系统管理员",
      "role": "admin",
      "create_time": "2026-01-01 00:00:00"
    }
  }
}
```

---

### 24. 后台题目列表

**接口名称：** 后台题目列表

**请求方式：** `GET`

**接口路径：** `/api/admin/question/list`

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| page | integer | 否 | 页码（默认：1） |
| page_size | integer | 否 | 每页条数（默认：20，最大：100） |
| subject | integer | 否 | 科目：1（科目一）/ 4（科目四） |
| question_type | integer | 否 | 题型：1（选择题）/ 2（判断题） |
| keyword | string | 否 | 关键词搜索 |
| status | integer | 否 | 状态：1（启用）/ 0（禁用） |

#### 响应示例

```json
{
  "code": 200,
  "message": "success",
  "data": {
    "list": [],
    "total": 0,
    "page": 1,
    "page_size": 20,
    "total_pages": 0
  }
}
```

---

### 25. Excel批量导入题目

**接口名称：** Excel批量导入题目

**请求方式：** `POST`

**接口路径：** `/api/admin/question/import`

**功能描述：** 通过Excel文件批量导入题目到题库。

#### 请求参数

| 参数名 | 类型 | 必填 | 说明 |
|--------|------|------|------|
| file | file | 是 | Excel文件（.xlsx/.xls格式） |

#### Excel模板格式

| 字段 | 说明 | 示例 |
|------|------|------|
| subject | 科目 | 1 |
| question_type | 题型 | 1 |
| chapter_id | 章节ID | 1 |
| content | 题目内容 | 机动车驾驶人应当... |
| option_a | 选项A | 习惯 |
| option_b | 选项B | 规定 |
| option_c | 选项C | 心情 |
| option_d | 选项D | 经验 |
| answer | 正确答案 | B |
| analysis | 题目解析 | 根据《道路交通安全法》... |
| difficulty | 难度等级 | 1 |

#### 响应示例

```json
{
  "code": 200,
  "message": "导入完成",
  "data": {
    "success": false,
    "count": 98,
    "errors": [
      {
        "row": 5,
        "message": "题目内容不能为空"
      }
    ]
  }
}
```

---

## 附录

### 数据字典

#### 科目枚举

| 值 | 说明 |
|----|------|
| 1 | 科目一（道路交通安全法律） |
| 4 | 科目四（安全文明驾驶常识） |

#### 题型枚举

| 值 | 说明 |
|----|------|
| 1 | 选择题（A/B/C/D） |
| 2 | 判断题（true/false） |

#### 状态枚举

| 值 | 说明 |
|----|------|
| 0 | 禁用 / 待激活 / 失败 |
| 1 | 启用 / 已激活 / 成功 |
| 2 | 已失效 / 已过期 |
| 3 | 已退款 |

#### 支付方式枚举

| 值 | 说明 |
|----|------|
| 1 | 微信支付 |
| 2 | 支付宝 |

#### 难度等级

| 值 | 说明 |
|----|------|
| 1 | 简单 |
| 2 | 中等 |
| 3 | 困难 |

---

## H5前端集成指南

### 设备ID生成方案

前端在用户首次打开应用时，需要生成一个唯一的设备ID：

```javascript
// 方案1：使用 uuid 库生成
const deviceId = uuid.v4(); // 例如: "550e8400-e29b-41d4-a716-446655440000"

// 方案2：使用 localStorage 持久化存储
let deviceId = localStorage.getItem('moto_device_id');
if (!deviceId) {
  deviceId = 'device_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
  localStorage.setItem('moto_device_id', deviceId);
}

// 方案3：使用 Web Crypto API
const deviceId = crypto.randomUUID();
```

### 激活流程前端示例

```javascript
// 1. 用户输入激活码
const activateCode = document.getElementById('activateCode').value;
const deviceId = localStorage.getItem('moto_device_id');

// 2. 调用验证接口
const verifyRes = await fetch('/api/student/verify_code', {
  method: 'POST',
  headers: { 'Content-Type': 'application/json' },
  body: JSON.stringify({ activate_code: activateCode })
});

// 3. 确认激活
if (verifyRes.data.status === 0) {
  const activateRes = await fetch('/api/student/activate', {
    method: 'POST',
    headers: { 
      'Content-Type': 'application/json',
      'Authorization': `Bearer ${userId}`
    },
    body: JSON.stringify({ 
      activate_code: activateCode, 
      device_id: deviceId 
    })
  });
}
```

---

> 文档结束
