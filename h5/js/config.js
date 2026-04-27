/**
 * API 配置文件
 */

var Config = {
    // API 基础地址（根据实际部署环境修改）
    API_BASE: 'http://localhost:8080/backend/public/index.php/api/',

    // 是否开启调试模式
    DEBUG: true,

    // 激活配置
    ACTIVATION: {
        PRICE: 18.00,           // 单次激活价格
        EXPIRE_DAYS: 30,         // 有效期天数
        REQUIRED: true          // 是否必须激活
    },

    // 考试配置
    EXAM: {
        QUESTION_COUNT: 50,     // 每套试卷题目数量
        TIME_LIMIT: 45,          // 考试时长（分钟）
        PASSING_SCORE: 90       // 及格分数
    }
};
