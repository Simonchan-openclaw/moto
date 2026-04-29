/**
 * 教练端 API 请求
 */

var CoachAPI = {
    request: function(url, method, data, showLoading) {
        return new Promise(function(resolve, reject) {
            if (showLoading !== false) {
                CoachApp.showLoading();
            }

            var xhr = new XMLHttpRequest();
            var apiUrl = Config.API_BASE + url;

            xhr.open(method, apiUrl, true);
            xhr.setRequestHeader('Content-Type', 'application/json');

            var token = localStorage.getItem('coach_token');
            if (token) {
                xhr.setRequestHeader('Authorization', 'Bearer ' + token);
            }

            xhr.onload = function() {
                if (showLoading !== false) {
                    CoachApp.hideLoading();
                }

                if (xhr.status === 200) {
                    var response = JSON.parse(xhr.responseText);
                    if (response.code === 200) {
                        resolve(response);
                    } else {
                        if (response.code === 401) {
                            CoachApp.logout();
                            CoachApp.showToast('请先登录');
                            reject(response);  // 添加这行
                        } else {
                            reject(response);
                        }
                    }
                } else {
                    reject({ message: '网络请求失败', status: xhr.status });
                }
            };

            xhr.onerror = function() {
                if (showLoading !== false) {
                    CoachApp.hideLoading();
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

    // 教练登录
    login: function(phone, password) {
        return this.request('coach/login', 'POST', { phone: phone, password: password }, true);
    },

    // 获取余额
    getBalance: function() {
        return this.request('coach/balance', 'GET', {}, true);
    },

    // 充值
    recharge: function(amount, payMethod) {
        return this.request('coach/recharge', 'POST', { amount: amount, pay_method: payMethod }, true);
    },

    // 充值记录
    getRechargeList: function(page, pageSize) {
        return this.request('coach/recharge_list?page=' + (page || 1) + '&page_size=' + (pageSize || 20), 'GET', {}, true);
    },

    // 激活学员
    activate: function(studentPhone) {
        return this.request('coach/activate', 'POST', { student_phone: studentPhone }, true);
    },

    // 激活记录
    getActivationList: function(page, pageSize, status) {
        var url = 'coach/activation_list?page=' + (page || 1) + '&page_size=' + (pageSize || 20);
        if (status !== null && status !== undefined) {
            url += '&status=' + status;
        }
        return this.request(url, 'GET', {}, true);
    },

    // 退款
    refund: function(activationId) {
        return this.request('coach/refund', 'POST', { activation_id: activationId }, true);
    },

    // 获取教练信息（包含邀请码）
    getInfo: function() {
        return this.request('coach/info', 'GET', {}, true);
    },

    // 获取邀请学员列表
    getInviteList: function(page, pageSize) {
        return this.request('coach/invite_list?page=' + (page || 1) + '&page_size=' + (pageSize || 20), 'GET', {}, true);
    }
};
