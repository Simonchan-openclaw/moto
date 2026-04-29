/**
 * API 请求工具
 */

var API = {
    /**
     * 基础请求方法
     */
    request: function(url, method, data, showLoading) {
        return new Promise(function(resolve, reject) {
            if (showLoading !== false) {
                App.showLoading();
            }

            var xhr = new XMLHttpRequest();
            var apiUrl = Config.API_BASE + url;

            xhr.open(method, apiUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/json');

            // 添加 Token
            var token = localStorage.getItem('token');
            if (token) {
                xhr.setRequestHeader('Authorization', 'Bearer ' + token);
            }

            xhr.onload = function() {
                if (showLoading !== false) {
                    App.hideLoading();
                }

                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.code === 200) {
                        resolve(response);
                    } else {
                        if (response.code === 401) {
                            App.logout();
                            App.showToast('请先登录');
                        } else {
                            reject(response);
                        }
                    }
                } else {
                    reject({ message: '网络请求失败' });
                }
            };

            xhr.onerror = function() {
                if (showLoading !== false) {
                    App.hideLoading();
                }
                reject({ message: '网络连接失败' });
            };

            if (data && (method === 'POST' || method === 'PUT')) {
                xhr.send(JSON.stringify(data));
            } else {
                xhr.send();
            }
        });
    },

    // ==================== 用户模块 ====================

    /**
     * 发送验证码
     */
    sendCode: function(phone, type) {
        return this.request('user/send_code', 'POST', {
            phone: phone,
            type: type || 'login'
        }, true);
    },

    /**
     * 用户登录
     */
    login: function(phone, code, countryCode) {
        return this.request('user/login', 'POST', {
            phone: phone,
            code: code,
            country_code: countryCode || '86'
        }, true);
    },

    /**
     * 用户注册
     */
    register: function(phone, name, password, deviceId, inviteCode, countryCode) {
        return this.request('user/register', 'POST', {
            phone: phone,
            name: name,
            password: password,
            device_id: deviceId || '',
            invite_code: inviteCode || '',
            country_code: countryCode || '86'
        }, true);
    },

    /**
     * 获取教练信息（公开接口）
     */
    getCoachInfo: function(coachId, inviteCode) {
        return this.request('coach/check?invite_code=' + encodeURIComponent(inviteCode || 'C' + coachId), 'GET', {}, false);
    },

    /**
     * 获取VIP激活状态
     * 通过JWT token验证，无需额外参数
     */
    getVipStatus: function() {
        return this.request('vip/status', 'GET', {}, true);
    },

    /**
     * 激活VIP
     */
    activateVip: function(activateCode, deviceId) {
        return this.request('vip/activate', 'POST', {
            activate_code: activateCode,
            device_id: deviceId
        }, true);
    },

    /**
     * 获取用户信息
     */
    getUserInfo: function() {
        return this.request('user/info', 'POST', {}, true);
    },

    // ==================== 题目模块 ====================

    /**
     * 获取章节列表
     */
    getChapters: function(subject) {
        return this.request('question/chapters?subject=' + subject, 'GET', {}, true);
    },

    /**
     * 获取题目列表
     */
    getQuestionList: function(params) {
        var query = [];
        for (var key in params) {
            if (params[key] !== null && params[key] !== undefined) {
                query.push(key + '=' + params[key]);
            }
        }
        return this.request('question/list?' + query.join('&'), 'GET', {}, true);
    },

    /**
     * 获取题目详情
     */
    getQuestionDetail: function(id) {
        return this.request('question/detail?id=' + id, 'GET', {}, true);
    },

    // ==================== 答题模块 ====================

    /**
     * 提交答题
     */
    submitAnswer: function(questionId, userAnswer, answerTime) {
        return this.request('answer/submit', 'POST', {
            question_id: questionId,
            user_answer: userAnswer,
            answer_time: answerTime
        }, false);
    },

    /**
     * 获取错题列表
     */
    getErrorList: function(page, pageSize) {
        return this.request('answer/error_list?page=' + (page || 1) + '&page_size=' + (pageSize || 20), 'GET', {}, true);
    },

    /**
     * 清空错题本
     */
    clearErrors: function() {
        return this.request('answer/error_clear', 'DELETE', {}, true);
    },

    // ==================== 收藏模块 ====================

    /**
     * 切换收藏状态
     */
    toggleCollection: function(questionId, status) {
        return this.request('collection/toggle', 'POST', {
            question_id: questionId,
            status: status
        }, false);
    },

    /**
     * 获取收藏列表
     */
    getCollectionList: function(page, pageSize) {
        return this.request('collection/list?page=' + (page || 1) + '&page_size=' + (pageSize || 20), 'GET', {}, true);
    },

    // ==================== 考试模块 ====================

    /**
     * 生成试卷
     */
    generateExam: function(subject, questionCount) {
        return this.request('exam/generate', 'POST', {
            subject: subject,
            question_count: questionCount || Config.EXAM.QUESTION_COUNT
        }, true);
    },

    /**
     * 提交试卷
     */
    submitExam: function(examId, answers, timeUsed) {
        return this.request('exam/submit', 'POST', {
            exam_id: examId,
            answers: answers,
            time_used: timeUsed
        }, true);
    },

    /**
     * 获取成绩记录
     */
    getExamRecords: function(page, pageSize) {
        return this.request('exam/record_list?page=' + (page || 1) + '&page_size=' + (pageSize || 20), 'GET', {}, true);
    },

    // ==================== 学员激活模块 ====================

    /**
     * 验证激活码
     */
    verifyCode: function(code) {
        return this.request('student/verify_code', 'POST', {
            activate_code: code
        }, true);
    },

    /**
     * 学员激活
     */
    activate: function(code, deviceId) {
        return this.request('student/activate', 'POST', {
            activate_code: code,
            device_id: deviceId
        }, true);
    },

    /**
     * 检查激活状态
     */
    checkActivation: function(deviceId) {
        return this.request('student/check?device_id=' + deviceId, 'GET', {}, true);
    }
};
