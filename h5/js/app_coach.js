/**
 * 教练端应用主逻辑
 */

var CoachApp = {
    coach: null,
    token: null,
    balance: 0,
    selectedAmount: 50,
    selectedPayMethod: 1,

    init: function() {
        this.token = localStorage.getItem('coach_token');
        this.coach = JSON.parse(localStorage.getItem('coach') || 'null');
        this.updateUserInfo();

        if (this.token) {
            this.loadBalance();
        }
    },

    checkLogin: function(pageId) {
        if (!this.token) {
            this.showToast('请先登录');
            setTimeout(function() {
                CoachApp.showPage('login');
            }, 500);
            return;
        }
        this.showPage(pageId);
    },

    updateUserInfo: function() {
        var userInfo = document.getElementById('userInfo');
        var nickname = userInfo.querySelector('.nickname');
        var status = userInfo.querySelector('.status');
        var menuItems = document.querySelectorAll('.menu-grid .menu-item');
        var btnLogout = document.getElementById('btnLogout');

        if (this.coach) {
            nickname.textContent = this.coach.real_name || '教练';
            status.textContent = '余额 ¥' + (parseFloat(this.coach.balance) || 0).toFixed(2) + '元';
            userInfo.onclick = null;
            userInfo.classList.remove('clickable');
            
            // 显示登出按钮
            if (btnLogout) btnLogout.style.display = 'block';
            
            // 启用菜单按钮
            menuItems.forEach(function(item) {
                item.classList.remove('disabled');
                item.style.pointerEvents = 'auto';
                item.style.opacity = '1';
            });
        } else {
            nickname.textContent = '未登录';
            status.textContent = '点击登录';
            userInfo.onclick = function() {
                CoachApp.showPage('login');
            };
            userInfo.classList.add('clickable');
            
            // 隐藏登出按钮
            if (btnLogout) btnLogout.style.display = 'none';
            
            // 禁用菜单按钮
            menuItems.forEach(function(item) {
                item.classList.add('disabled');
                item.style.pointerEvents = 'none';
                item.style.opacity = '0.5';
            });
        }
    },

    loadBalance: function() {
        CoachAPI.getBalance().then(function(res) {
            CoachApp.balance = parseFloat(res.data.balance);
            document.getElementById('balanceAmount').textContent = '¥' + CoachApp.balance.toFixed(2);
            document.getElementById('currentBalance').textContent = '¥' + CoachApp.balance.toFixed(2);
        }).catch(function() {});
    },

    showPage: function(pageId) {
        document.querySelectorAll('.page').forEach(function(page) {
            page.style.display = 'none';
        });

        var page = document.getElementById('page-' + pageId);
        if (page) {
            page.style.display = 'block';
        }

        this.loadPageData(pageId);
    },

    loadPageData: function(pageId) {
        switch (pageId) {
            case 'activation-list':
                this.loadActivationList();
                break;
            case 'recharge-list':
                this.loadRechargeList();
                break;
            case 'activate':
                var balanceEl = document.getElementById('currentBalance');
                if (balanceEl) balanceEl.textContent = '¥' + this.balance.toFixed(2);
                break;
            case 'invite':
                this.loadInvitePage();
                break;
            case 'invite-list':
                this.loadInviteList();
                break;
        }
    },

    doLogin: function() {
        var phone = document.getElementById('loginPhone').value.trim();
        var password = document.getElementById('loginPassword').value;

        if (!phone || !/^1[3-9]\d{9}$/.test(phone)) {
            this.showToast('请输入正确的手机号');
            return;
        }

        if (!password) {
            this.showToast('请输入密码');
            return;
        }

        var self = this;

        // 模拟登录（测试用）
        if (phone === '13800138000' && password === '123456') {
            this.token = 'test_token_123456';
            this.coach = { id: 1, phone: phone, real_name: '测试教练' };
            localStorage.setItem('coach_token', this.token);
            localStorage.setItem('coach', JSON.stringify(this.coach));

            this.updateUserInfo();
            this.loadBalance();
            this.showPage('home');
            this.showToast('登录成功');
            return;
        }

        CoachAPI.login(phone, password).then(function(res) {
            self.token = res.data.token;
            self.coach = res.data;

            localStorage.setItem('coach_token', self.token);
            localStorage.setItem('coach', JSON.stringify(self.coach));

            self.updateUserInfo();
            self.loadBalance();
            self.showPage('home');
            self.showToast('登录成功');
        }).catch(function(err) {
            self.showToast(err.message || '登录失败');
        });
    },

    logout: function() {
        this.token = null;
        this.coach = null;
        localStorage.removeItem('coach_token');
        localStorage.removeItem('coach');
        this.updateUserInfo();
    },

    selectAmount: function(amount) {
        this.selectedAmount = amount;
        document.getElementById('customAmount').value = amount;

        document.querySelectorAll('.amount-item').forEach(function(item) {
            item.classList.remove('selected');
        });
        event.target.classList.add('selected');
    },

    selectPayMethod: function(method) {
        this.selectedPayMethod = method;

        document.querySelectorAll('.pay-item').forEach(function(item) {
            item.classList.remove('selected');
        });
        event.currentTarget.classList.add('selected');
    },

    showRecharge: function() {
        document.getElementById('customAmount').value = '50';
        this.selectedAmount = 50;
        this.showPage('recharge');
    },

    doRecharge: function() {
        var amount = parseInt(document.getElementById('customAmount').value) || 0;

        if (amount < 18) {
            this.showToast('最低充值金额为18元');
            return;
        }

        var self = this;

        CoachAPI.recharge(amount, this.selectedPayMethod).then(function(res) {
            self.balance = parseFloat(res.data.balance);
            document.getElementById('balanceAmount').textContent = '¥' + self.balance.toFixed(2);
            document.getElementById('currentBalance').textContent = '¥' + self.balance.toFixed(2);
            self.showToast('充值成功');
            self.showPage('home');
        }).catch(function(err) {
            self.showToast(err.message || '充值失败');
        });
    },

    doActivate: function() {
        var studentPhone = document.getElementById('studentPhone').value.trim();

        if (!studentPhone || !/^1[3-9]\d{9}$/.test(studentPhone)) {
            this.showToast('请输入正确的学员手机号');
            return;
        }

        if (this.balance < 18) {
            this.showToast('余额不足，请先充值');
            return;
        }

        var self = this;

        CoachAPI.activate(studentPhone).then(function(res) {
            self.balance = parseFloat(res.data.balance);
            document.getElementById('balanceAmount').textContent = '¥' + self.balance.toFixed(2);

            // 显示激活结果
            var result = document.getElementById('activateResult');
            result.innerHTML = '<div class="result-container">' +
                '<div style="font-size:60px;margin-bottom:15px;">🎉</div>' +
                '<h3 style="margin-bottom:15px;">激活码生成成功</h3>' +
                '<div class="code-box">' +
                '<div class="code-label">请将此激活码发送给学员</div>' +
                '<div class="code-value">' + res.data.activate_code + '</div>' +
                '</div>' +
                '<div class="activation-detail">' +
                '<div class="detail-row"><span>学员手机</span><span>' + studentPhone + '</span></div>' +
                '<div class="detail-row"><span>扣款金额</span><span>¥' + res.data.amount + '</span></div>' +
                '<div class="detail-row"><span>剩余余额</span><span>¥' + res.data.balance + '</span></div>' +
                '<div class="detail-row"><span>有效期至</span><span>' + res.data.expire_at + '</span></div>' +
                '</div>' +
                '<button class="btn-primary" style="background:linear-gradient(135deg,#52c41a 0%,#389e0d 100%);margin-top:20px;" onclick="CoachApp.showPage(\'home\')">完成</button>' +
                '</div>';

            self.showPage('activate-result');
        }).catch(function(err) {
            self.showToast(err.message || '激活失败');
        });
    },

    loadActivationList: function() {
        var self = this;

        CoachAPI.getActivationList().then(function(res) {
            var container = document.getElementById('activationListContainer');

            if (res.data.total === 0) {
                container.innerHTML = '<div class="empty-state"><div class="icon">📋</div><p>暂无激活记录</p></div>';
                return;
            }

            var html = '';
            res.data.list.forEach(function(item) {
                var statusClass = ['', 'status-active', 'status-expired', 'status-refund'][item.activate_status];
                var statusText = ['待激活', '已激活', '已失效', '已退款'][item.activate_status];

                html += '<div class="record-item">' +
                    '<div class="record-header">' +
                    '<span class="subject">' + item.student_phone_mask + '</span>' +
                    '<span class="status-badge ' + statusClass + '">' + statusText + '</span>' +
                    '</div>' +
                    '<div class="record-code">激活码: ' + item.activate_code + '</div>' +
                    '<div class="record-detail">' +
                    '<span>扣款: ¥' + item.amount_deducted + '</span>' +
                    '<span>' + item.create_time + '</span>' +
                    '</div>' +
                    (item.activate_status === 0 ? '<button class="btn-refund" onclick="CoachApp.refund(' + item.id + ')">退款</button>' : '') +
                    '</div>';
            });

            container.innerHTML = html;

        }).catch(function() {
            document.getElementById('activationListContainer').innerHTML = '<div class="empty-state"><div class="icon">❌</div><p>加载失败</p></div>';
        });
    },

    filterActivation: function(status) {
        document.querySelectorAll('.tab-item').forEach(function(item) {
            item.classList.remove('active');
        });
        event.target.classList.add('active');

        var self = this;
        CoachAPI.getActivationList(1, 20, status).then(function(res) {
            var container = document.getElementById('activationListContainer');

            if (res.data.total === 0) {
                container.innerHTML = '<div class="empty-state"><div class="icon">📋</div><p>暂无记录</p></div>';
                return;
            }

            var html = '';
            res.data.list.forEach(function(item) {
                var statusClass = ['', 'status-active', 'status-expired', 'status-refund'][item.activate_status];
                var statusText = ['待激活', '已激活', '已失效', '已退款'][item.activate_status];

                html += '<div class="record-item">' +
                    '<div class="record-header">' +
                    '<span class="subject">' + item.student_phone_mask + '</span>' +
                    '<span class="status-badge ' + statusClass + '">' + statusText + '</span>' +
                    '</div>' +
                    '<div class="record-code">激活码: ' + item.activate_code + '</div>' +
                    '<div class="record-detail">' +
                    '<span>扣款: ¥' + item.amount_deducted + '</span>' +
                    '<span>' + item.create_time + '</span>' +
                    '</div>' +
                    (item.activate_status === 0 ? '<button class="btn-refund" onclick="CoachApp.refund(' + item.id + ')">退款</button>' : '') +
                    '</div>';
            });

            container.innerHTML = html;
        }).catch(function() {});
    },

    refund: function(activationId) {
        if (!confirm('确定要退款吗？')) return;

        var self = this;

        CoachAPI.refund(activationId).then(function(res) {
            self.balance = parseFloat(res.data.balance);
            document.getElementById('balanceAmount').textContent = '¥' + self.balance.toFixed(2);
            self.showToast('退款成功');
            self.loadActivationList();
        }).catch(function(err) {
            self.showToast(err.message || '退款失败');
        });
    },

    loadRechargeList: function() {
        var self = this;

        CoachAPI.getRechargeList().then(function(res) {
            var container = document.getElementById('rechargeListContainer');

            if (res.data.total === 0) {
                container.innerHTML = '<div class="empty-state"><div class="icon">💳</div><p>暂无充值记录</p></div>';
                return;
            }

            var html = '';
            res.data.list.forEach(function(item) {
                var payText = item.pay_method === 1 ? '微信支付' : '支付宝';
                var statusText = ['', '成功', '退款'][item.status];

                html += '<div class="record-item">' +
                    '<div class="record-header">' +
                    '<span class="subject">充值 ¥' + item.amount + '</span>' +
                    '<span class="status-badge ' + (item.status === 1 ? 'status-active' : 'status-refund') + '">' + statusText + '</span>' +
                    '</div>' +
                    '<div class="record-detail">' +
                    '<span>' + payText + '</span>' +
                    '<span>流水号: ' + item.trade_no + '</span>' +
                    '<span>' + item.create_time + '</span>' +
                    '</div></div>';
            });

            container.innerHTML = html;

        }).catch(function() {
            document.getElementById('rechargeListContainer').innerHTML = '<div class="empty-state"><div class="icon">❌</div><p>加载失败</p></div>';
        });
    },

    /**
     * 加载邀请页面
     */
    loadInvitePage: function() {
        var self = this;
        var qrcodeImg = document.getElementById('qrcodeImg');
        var qrcodeLoading = document.getElementById('qrcodeLoading');
        
        // 显示加载中
        qrcodeLoading.style.display = 'block';
        if (qrcodeImg) qrcodeImg.style.display = 'none';

        CoachAPI.getInfo().then(function(res) {
            var inviteCode = res.data.invite_code;
            var qrcodeUrl = res.data.qrcode_url;

            // 保存邀请码
            localStorage.setItem('invite_code', inviteCode);

            // 使用后端返回的二维码URL（使用QR Server API）
            if (qrcodeUrl) {
                if (qrcodeImg) {
                    qrcodeImg.src = qrcodeUrl;
                    qrcodeImg.onload = function() {
                        qrcodeLoading.style.display = 'none';
                        qrcodeImg.style.display = 'block';
                    };
                    qrcodeImg.onerror = function() {
                        qrcodeLoading.innerHTML = '加载失败，请刷新重试';
                    };
                } else {
                    // 如果没有img标签，创建并显示
                    var qrcodeBox = document.getElementById('qrcodeBox');
                    if (qrcodeBox) {
                        qrcodeBox.innerHTML = '<img id="qrcodeImg" src="' + qrcodeUrl + '" style="max-width:200px;border-radius:8px;" />';
                        qrcodeLoading.style.display = 'none';
                    }
                }
            } else {
                qrcodeLoading.innerHTML = '获取失败，请刷新重试';
            }
        }).catch(function(err) {
            qrcodeLoading.innerHTML = '获取信息失败，请刷新重试';
        });
    },

    /**
     * 生成二维码（已废弃，使用后端API）
     */
    generateQRCode: function(container, text) {
        // 已改用后端API生成，此方法保留但不再使用
    },

    /**
     * 加载邀请学员列表
     */
    loadInviteList: function() {
        var self = this;
        var container = document.getElementById('inviteListContainer');
        container.innerHTML = '<div class="invite-empty">加载中...</div>';

        CoachAPI.getInviteList().then(function(res) {
            var list = res.data.list || [];

            if (list.length === 0) {
                container.innerHTML = '<div class="invite-empty">暂无邀请记录<br><small>分享二维码给学员注册即可获得奖励</small></div>';
                return;
            }

            var html = '';
            list.forEach(function(item) {
                html += '<div class="invite-item">' +
                    '<div><div class="phone">' + (item.phone_mask || item.phone) + '</div>' +
                    '<div class="time">' + item.create_time + '</div></div>' +
                    '</div>';
            });

            container.innerHTML = html;
        }).catch(function() {
            container.innerHTML = '<div class="invite-empty">加载失败</div>';
        });
    },

    showLoading: function() {
        document.getElementById('loading').style.display = 'flex';
    },

    hideLoading: function() {
        document.getElementById('loading').style.display = 'none';
    },

    showToast: function(text) {
        var toast = document.getElementById('toast');
        document.getElementById('toastText').textContent = text;
        toast.style.display = 'block';
        setTimeout(function() {
            toast.style.display = 'none';
        }, 2000);
    }
};

document.addEventListener('DOMContentLoaded', function() {
    CoachApp.init();
});

// 添加教练端特有样式
var style = document.createElement('style');
style.textContent = `
    .balance-card {
        background: linear-gradient(135deg, #52c41a 0%, #389e0d 100%);
        color: white;
        margin: -10px 15px 15px;
        padding: 20px;
        border-radius: 10px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 4px 15px rgba(82, 196, 26, 0.3);
    }
    .balance-main .label { display: block; font-size: 12px; opacity: 0.9; }
    .balance-main .amount { display: block; font-size: 28px; font-weight: bold; }
    .btn-recharge {
        padding: 10px 20px;
        background: white;
        color: #52c41a;
        border: none;
        border-radius: 20px;
        font-weight: bold;
        cursor: pointer;
    }
    .amount-options { display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-top: 10px; }
    .amount-item {
        padding: 15px;
        background: #f5f5f5;
        border-radius: 8px;
        text-align: center;
        cursor: pointer;
        border: 2px solid transparent;
    }
    .amount-item.selected { border-color: #52c41a; background: rgba(82, 196, 26, 0.1); }
    .pay-methods { display: flex; gap: 10px; margin-top: 10px; }
    .pay-item {
        flex: 1;
        padding: 15px;
        background: #f5f5f5;
        border-radius: 8px;
        text-align: center;
        cursor: pointer;
        border: 2px solid transparent;
    }
    .pay-item.selected { border-color: #52c41a; background: rgba(82, 196, 26, 0.1); }
    .activation-info {
        background: #f9f9f9;
        border-radius: 10px;
        padding: 15px;
        margin-bottom: 20px;
    }
    .info-row {
        display: flex;
        justify-content: space-between;
        padding: 8px 0;
        border-bottom: 1px solid #eee;
    }
    .info-row:last-child { border-bottom: none; }
    .code-box {
        background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
        color: white;
        padding: 25px;
        border-radius: 10px;
        text-align: center;
        margin: 20px 0;
    }
    .code-label { font-size: 14px; opacity: 0.9; margin-bottom: 10px; }
    .code-value {
        font-size: 28px;
        font-weight: bold;
        letter-spacing: 3px;
    }
    .activation-detail { text-align: left; }
    .detail-row {
        display: flex;
        justify-content: space-between;
        padding: 10px 0;
        border-bottom: 1px solid #eee;
    }
    .record-code {
        font-size: 12px;
        color: #999;
        margin: 8px 0;
    }
    .status-badge {
        padding: 3px 10px;
        border-radius: 10px;
        font-size: 12px;
    }
    .status-active { background: #d9f7be; color: #52c41a; }
    .status-expired { background: #fff1f0; color: #ff4d4f; }
    .status-refund { background: #f0f5ff; color: #1890ff; }
    .btn-refund {
        width: 100%;
        padding: 10px;
        background: white;
        border: 1px solid #ff4d4f;
        color: #ff4d4f;
        border-radius: 5px;
        margin-top: 10px;
        cursor: pointer;
    }
    .menu-item.disabled,
    .btn-recharge.disabled {
        opacity: 0.5;
        pointer-events: none;
    }
    .user-info.clickable {
        cursor: pointer;
    }
    .filter-tabs {
        display: flex;
        background: white;
        padding: 10px 15px;
        gap: 10px;
    }
    .tab-item {
        flex: 1;
        padding: 10px;
        text-align: center;
        border-radius: 20px;
        font-size: 14px;
        cursor: pointer;
    }
    .tab-item.active { background: #52c41a; color: white; }
`;
document.head.appendChild(style);
