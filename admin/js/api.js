/**
 * 管理后台 API 请求
 */

var API = {
    request: function(url, method, data, showLoading) {
        return new Promise(function(resolve, reject) {
            if (showLoading !== false) {
                Admin.showLoading();
            }

            var xhr = new XMLHttpRequest();
            var apiUrl = Config.API_BASE + url;

            // POST/PUT 请求添加参数到 URL
            if ((method === 'POST' || method === 'PUT') && data && !url.includes('?')) {
                var params = [];
                for (var key in data) {
                    params.push(key + '=' + encodeURIComponent(data[key]));
                }
                if (params.length > 0) {
                    apiUrl += '?' + params.join('&');
                }
            }

            xhr.open(method, apiUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/json');
            // 从 localStorage 获取 token
            var token = localStorage.getItem('admin_token') || '';
            xhr.setRequestHeader('Authorization', 'Bearer ' + token);

            xhr.onload = function() {
                if (showLoading !== false) {
                    Admin.hideLoading();
                }

                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.code === 200) {
                        resolve(response);
                    } else {
                        reject(response);
                    }
                } else {
                    reject({ message: '请求失败' });
                }
            };

            xhr.onerror = function() {
                if (showLoading !== false) {
                    Admin.hideLoading();
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

    // ==================== 管理员 ====================

    login: function(username, password) {
        return this.request('admin/login', 'POST', { username: username, password: password }, true);
    },

    // ==================== 题目管理 ====================

    getQuestionList: function(params) {
        var query = [];
        for (var key in params) {
            if (params[key] !== null && params[key] !== undefined) {
                query.push(key + '=' + params[key]);
            }
        }
        return this.request('admin/question/list?' + query.join('&'), 'GET', {}, true);
    },

    // ==================== 章节管理 ====================

    getChapters: function(subject) {
        return this.request('question/chapters?subject=' + (subject || 1), 'GET', {}, true);
    },

    // ==================== 用户管理 ====================

    getUserList: function(page, pageSize) {
        return this.request('admin/user/list?page=' + (page || 1) + '&page_size=' + (pageSize || 20), 'GET', {}, true);
    },

    // ==================== 教练管理 ====================

    getCoachList: function(page, pageSize) {
        return this.request('admin/coach/list?page=' + (page || 1) + '&page_size=' + (pageSize || 20), 'GET', {}, true);
    },

    addCoach: function(phone, password, realName) {
        return this.request('admin/coach/add', 'POST', { phone: phone, password: password, real_name: realName }, true);
    },

    coachRecharge: function(coachId, amount) {
        return this.request('admin/coach/recharge', 'POST', { coach_id: coachId, amount: amount }, true);
    },

    getCoachBalance: function(coachId) {
        return this.request('admin/coach/balance?coach_id=' + coachId, 'GET', {}, true);
    },

    // ==================== 激活记录 ====================

    getActivationList: function(params) {
        var query = [];
        for (var key in params) {
            if (params[key] !== null && params[key] !== undefined) {
                query.push(key + '=' + params[key]);
            }
        }
        return this.request('admin/activation/list?' + query.join('&'), 'GET', {}, true);
    },

    getActivationStatistics: function() {
        return this.request('admin/activation/statistics', 'GET', {}, true);
    },

    // ==================== 统计数据 ====================

    getStatistics: function() {
        return this.request('admin/stat/summary', 'GET', {}, true);
    }
};
