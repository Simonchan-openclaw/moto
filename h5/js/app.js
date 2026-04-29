/**
 * 摩托车笔试题库 H5 应用主逻辑
 */

var App = {
    // 当前用户信息
    user: null,
    token: null,

    // 考试相关
    exam: {
        id: null,
        subject: null,
        questions: [],
        answers: {},
        currentIndex: 0,
        startTime: null,
        timer: null
    },

    // 练习相关
    practice: {
        subject: null,
        chapterId: null,
        questions: [],
        currentIndex: 0,
        answerTime: 0,
        startTime: null,
        selectedAnswer: null,
        answerSubmitted: false // 是否已提交答案
    },

    /**
     * 初始化应用
     */
    init: function() {
        // 获取存储的用户信息
        this.token = localStorage.getItem('token');
        this.user = JSON.parse(localStorage.getItem('user') || 'null');

        // 初始化页面
        this.updateUserInfo();
        this.updateMenuVisibility();

        // 清理过期缓存
        this.cleanupCache();

        // 检测邀请码，有则跳转到注册页面
        var inviteCode = this.getInviteCodeFromUrl();
        if (inviteCode) {
            // 保存邀请码
            localStorage.setItem('invite_code', inviteCode);
            // 跳转到注册页面
            this.showPage('register');
        }
    },

    /**
     * 获取设备ID
     */
    getDeviceId: function() {
        var deviceId = localStorage.getItem('device_id');
        if (!deviceId) {
            deviceId = 'device_' + Date.now() + '_' + Math.random().toString(36).substr(2, 9);
            localStorage.setItem('device_id', deviceId);
        }
        return deviceId;
    },

    /**
     * 检查是否已激活
     */
    isActivated: function() {
        var cachedStatus = localStorage.getItem('activation_status');
        if (cachedStatus) {
            try {
                var status = JSON.parse(cachedStatus);
                if (status.activated && status.expire_at > Date.now()) {
                    return true; // 已激活且未过期
                }
            } catch (e) {}
        }
        return false;
    },

    /**
     * 从服务器检查VIP激活状态
     */
    checkActivationFromServer: function() {
        var deviceId = this.getDeviceId();
        var self = this;
        
        API.getVipStatus(deviceId).then(function(res) {
            if (res.code === 200 && res.data) {
                if (res.data.is_activated && res.data.expire_at) {
                    // 服务器返回已激活，更新本地状态
                    self.setActivation(res.data.expire_at);
                } else {
                    // 未激活，清除本地状态
                    localStorage.removeItem('activation_status');
                    self.updateUserInfo();
                    self.updateMenuVisibility();
                }
            }
        }).catch(function() {
            // 忽略错误，使用本地缓存
        });
    },

    /**
     * 设置激活状态
     */
    setActivation: function(expire_at) {
        var status = {
            activated: true,
            expire_at: new Date(expire_at).getTime()
        };
        localStorage.setItem('activation_status', JSON.stringify(status));
        this.updateUserInfo();
        this.updateMenuVisibility();
    },

    /**
     * 更新菜单显示状态
     */
    updateMenuVisibility: function() {
        var menuItems = document.querySelectorAll('.menu-item');
        var isLoggedIn = this.user !== null;
        var isAct = this.isActivated();

        if (!isLoggedIn) {
            // 未登录：所有菜单不可点击
            menuItems.forEach(function(item) {
                item.style.opacity = '0.5';
                item.style.pointerEvents = 'none';
            });
        } else if (!isAct) {
            // 登录未激活：所有菜单不可点击
            menuItems.forEach(function(item) {
                item.style.opacity = '0.5';
                item.style.pointerEvents = 'none';
            });
        } else {
            // 已登录已激活：菜单正常可用
            menuItems.forEach(function(item) {
                item.style.opacity = '1';
                item.style.pointerEvents = 'auto';
            });
        }
    },

    /**
     * 清理缓存
     */
    cleanupCache: function() {
        // 清理过期试卷缓存
        var examCache = localStorage.getItem('exam_cache');
        if (examCache) {
            var exam = JSON.parse(examCache);
            if (exam.expire_at < Date.now()) {
                localStorage.removeItem('exam_cache');
            }
        }
    },

    /**
     * 显示页面
     */
    showPage: function(pageId, params) {
        // 隐藏所有页面
        document.querySelectorAll('.page').forEach(function(page) {
            page.style.display = 'none';
        });

        // 显示目标页面
        var page = document.getElementById('page-' + pageId);
        if (page) {
            page.style.display = 'block';
        }

        // 加载页面数据
        this.loadPageData(pageId, params);
    },

    /**
     * 加载页面数据
     */
    loadPageData: function(pageId, params) {
        switch (pageId) {
            case 'chapters':
                this.loadChapters(params.subject);
                break;
            case 'error-list':
                this.loadErrorList();
                break;
            case 'collection-list':
                this.loadCollectionList();
                break;
            case 'exam-records':
                this.loadExamRecords();
                break;
            case 'exam-start':
                this.updateExamStartStatus();
                break;
            case 'login':
                this.showLoginPage();
                break;
            case 'register':
                this.showRegisterPage();
                break;
        }
    },

    /**
     * 显示登录页
     */
    showLoginPage: function() {
        var deviceCodeEl = document.getElementById('deviceCodeDisplay');
        if (deviceCodeEl) {
            deviceCodeEl.textContent = this.getDeviceId();
        }
    },

    /**
     * 显示注册页（扫码注册专用）
     */
    showRegisterPage: function() {
        var deviceCodeEl = document.getElementById('regDeviceCodeDisplay');
        if (deviceCodeEl) {
            deviceCodeEl.textContent = this.getDeviceId();
        }
        
        // 检查邀请码并显示教练信息
        var inviteCodeEl = document.getElementById('inviteCoachName');
        var inviteCoachGroup = document.getElementById('inviteCoachGroup');
        var inviteCode = localStorage.getItem('invite_code') || this.getInviteCodeFromUrl() || '';
        var countryCode = document.getElementById('regCountryCode') ? document.getElementById('regCountryCode').value : '86';
        var phoneInput = document.getElementById('regPhone');
        var phoneCodeSelect = document.getElementById('regCountryCode');
        
        if (inviteCode) {
            // 解析邀请码（新格式：C开头+教练ID，或旧格式：base64 JSON）
            var coachId = 0;
            if (inviteCode.startsWith('C')) {
                // 新格式：C10001 -> coachId = 10001
                coachId = parseInt(inviteCode.substring(1));
            } else {
                // 旧格式：base64 JSON
                try {
                    var decoded = JSON.parse(atob(inviteCode));
                    coachId = decoded.coach_id || 0;
                } catch (e) {
                    coachId = 0;
                }
            }
            
            if (coachId > 0 && inviteCoachGroup) {
                // 有邀请码，显示教练信息
                inviteCoachGroup.style.display = 'block';
                if (inviteCodeEl) {
                    inviteCodeEl.value = '加载中...';
                }
                
                // 调用API获取教练信息
                var self = this;
                API.getCoachInfo(coachId, inviteCode).then(function(res) {
                    if (res.code === 200 && res.data) {
                        if (inviteCodeEl) {
                            inviteCodeEl.value = res.data.real_name || '教练' + coachId;
                        }
                    } else {
                        if (inviteCodeEl) {
                            inviteCodeEl.value = '未知教练';
                        }
                    }
                }).catch(function() {
                    if (inviteCodeEl) {
                        inviteCodeEl.value = '加载失败';
                    }
                });
            } else {
                this.hideInviteInfo();
            }
        } else {
            this.hideInviteInfo();
        }
    },
    
    /**
     * 隐藏邀请信息（无邀请码时）
     */
    hideInviteInfo: function() {
        var inviteCoachGroup = document.getElementById('inviteCoachGroup');
        
        if (inviteCoachGroup) {
            inviteCoachGroup.style.display = 'none';
        }
    },

    /**
     * 执行注册（扫码注册）
     */
    doRegister: function() {
        var phone = document.getElementById('regPhone').value.trim();
        var name = document.getElementById('regName').value.trim();
        var password = document.getElementById('regPassword').value;
        var deviceId = this.getDeviceId();
        var inviteCode = localStorage.getItem('invite_code') || this.getInviteCodeFromUrl() || '';
        var countryCode = document.getElementById('regCountryCode') ? document.getElementById('regCountryCode').value : '86';

        if (!phone || !/^1[3-9]\d{9}$/.test(phone)) {
            this.showToast('请输入正确的手机号');
            return;
        }

        if (!name) {
            this.showToast('请输入姓名');
            return;
        }

        if (!password || password.length < 6) {
            this.showToast('请输入6位以上密码');
            return;
        }

        // 调用注册API
        var self = this;
        API.register(phone, name, password, deviceId, inviteCode, countryCode).then(function(res) {
            App.token = res.data.token;
            App.user = res.data.userInfo;

            localStorage.setItem('token', App.token);
            localStorage.setItem('user', JSON.stringify(App.user));

            // 如果有邀请码，清除URL中的参数
            if (inviteCode) {
                App.clearInviteCodeFromUrl();
                App.showToast('注册成功，已绑定教练');
            } else {
                App.showToast('注册成功');
            }
            App.updateUserInfo();
            App.updateMenuVisibility();
            App.showPage('home');
        }).catch(function(err) {
            App.showToast(err.message || '注册失败');
        });
    },

    /**
     * 更新考试开始页状态
     */
    updateExamStartStatus: function() {
        var statusEl = document.getElementById('examActivationStatus');
        if (!statusEl) return;

        var isAct = this.isActivated();
        if (!this.user) {
            statusEl.textContent = '📱 状态: 请先登录';
            statusEl.style.color = '#999';
        } else if (!isAct) {
            statusEl.textContent = '📱 状态: 请先联系教练激活';
            statusEl.style.color = '#ff4d4f';
        } else {
            statusEl.textContent = '📱 状态: 已激活';
            statusEl.style.color = '#52c41a';
        }
    },

    /**
     * 更新用户信息显示
     */
    updateUserInfo: function() {
        var userInfo = document.getElementById('userInfo');
        var nickname = userInfo.querySelector('.nickname');
        var status = userInfo.querySelector('.status');
        var btnLogout = document.getElementById('btnLogout');

        if (!this.user) {
            // 未登录
            nickname.textContent = '未登录';
            status.textContent = '请先登录';
            userInfo.onclick = function() {
                App.showPage('login');
            };
            userInfo.style.cursor = 'pointer';
            if (btnLogout) btnLogout.style.display = 'none';
        } else if (!this.isActivated()) {
            // 登录未激活
            nickname.textContent = this.user.nickname || '摩托学员';
            status.textContent = '请先联系教练激活';
            userInfo.onclick = function() {
                App.showToast('请联系教练获取激活码');
            };
            userInfo.style.cursor = 'pointer';
            if (btnLogout) btnLogout.style.display = 'block';
        } else {
            // 已登录已激活
            nickname.textContent = this.user.nickname || '摩托学员';
            status.textContent = '已登录';
            userInfo.onclick = null;
            userInfo.style.cursor = 'default';
            if (btnLogout) btnLogout.style.display = 'block';
        }
    },

    /**
     * 发送验证码
     */
    sendCode: function() {
        var phone = document.getElementById('loginPhone').value.trim();

        if (!phone || !/^1[3-9]\d{9}$/.test(phone)) {
            this.showToast('请输入正确的手机号');
            return;
        }

        var btn = document.querySelector('.btn-code');
        btn.disabled = true;
        var countdown = 60;
        btn.textContent = countdown + 's';

        var timer = setInterval(function() {
            countdown--;
            btn.textContent = countdown + 's';
            if (countdown <= 0) {
                clearInterval(timer);
                btn.disabled = false;
                btn.textContent = '获取验证码';
            }
        }, 1000);

        API.sendCode(phone).then(function(res) {
            App.showToast('验证码已发送');
            if (Config.DEBUG && res.data.code) {
                console.log('测试验证码:', res.data.code);
            }
        }).catch(function(err) {
            clearInterval(timer);
            btn.disabled = false;
            btn.textContent = '获取验证码';
            App.showToast(err.message || '发送失败');
        });
    },

    /**
     * 执行登录
     */
    doLogin: function() {
        var phone = document.getElementById('loginPhone').value.trim();
        var countryCode = document.getElementById('countryCode').value;
        var deviceId = this.getDeviceId();

        if (!phone || !/^1[3-9]\d{9}$/.test(phone)) {
            this.showToast('请输入正确的手机号');
            return;
        }

        // 使用设备码作为登录凭证
        var self = this;
        API.login(phone, deviceId, countryCode).then(function(res) {
            App.token = res.data.token;
            App.user = res.data.userInfo;

            localStorage.setItem('token', App.token);
            localStorage.setItem('user', JSON.stringify(App.user));
            
            // 更新VIP激活状态
            if (res.data.userInfo.vip_expire) {
                App.setActivation(res.data.userInfo.vip_expire);
            }

            App.updateUserInfo();
            App.updateMenuVisibility();
            App.showPage('home');
            App.showToast('登录成功');
            
            // 从服务器检查最新激活状态
            App.checkActivationFromServer();
        }).catch(function(err) {
            self.showToast(err.message || '登录失败');
        });
    },

    /**
     * 从URL获取邀请码
     */
    getInviteCodeFromUrl: function() {
        var params = new URLSearchParams(window.location.search);
        return params.get('invite_code') || '';
    },

    /**
     * 清除URL中的邀请码
     */
    clearInviteCodeFromUrl: function() {
        var url = new URL(window.location);
        url.searchParams.delete('invite_code');
        window.history.replaceState({}, '', url);
    },

    /**
     * 退出登录
     */
    logout: function() {
        this.token = null;
        this.user = null;
        localStorage.removeItem('token');
        localStorage.removeItem('user');
        this.updateUserInfo();
        this.updateMenuVisibility();
        this.showPage('home');
    },

    // ==================== 章节练习 ====================

    /**
     * 加载章节列表
     */
    loadChapters: function(subject) {
        var self = this;
        this.practice.subject = subject;
        document.getElementById('chapterTitle').textContent = subject === 1 ? '科目一 章节练习' : '科目四 章节练习';

        API.getChapters(subject).then(function(res) {
            var list = document.getElementById('chapterList');
            var html = '';

            res.data.forEach(function(chapter) {
                html += '<div class="chapter-item" onclick="App.startChapterPractice(' + chapter.chapter_id + ', \'' + chapter.chapter_name + '\')">' +
                    '<div class="chapter-icon">' + chapter.sort + '</div>' +
                    '<div class="chapter-info">' +
                    '<span class="chapter-name">' + chapter.chapter_name + '</span>' +
                    '</div></div>';
            });

            list.innerHTML = html || '<div class="empty-state"><div class="icon">📚</div><p>暂无章节数据</p></div>';
        }).catch(function(err) {
            document.getElementById('chapterList').innerHTML = '<div class="empty-state"><div class="icon">❌</div><p>加载失败</p></div>';
        });
    },

    /**
     * 开始章节练习
     */
    startChapterPractice: function(chapterId, chapterName) {
        this.practice.chapterId = chapterId;
        this.practice.currentIndex = 0;
        this.practice.questions = [];

        this.showPage('practice');

        // 加载章节题目
        var self = this;
        API.getQuestionList({
            chapter_id: chapterId,
            page: 1,
            page_size: 20
        }).then(function(res) {
            self.practice.questions = res.data.list;
            if (self.practice.questions.length > 0) {
                self.showPracticeQuestion();
            } else {
                document.querySelector('#page-practice .question-content').textContent = '该章节暂无题目';
            }
        }).catch(function(err) {
            self.showToast('加载失败');
        });
    },

    /**
     * 显示练习题目
     */
    showPracticeQuestion: function() {
        var question = this.practice.questions[this.practice.currentIndex];
        if (!question) return;

        var container = document.getElementById('questionContainer');
        var content = container.querySelector('.question-content');
        var options = container.querySelector('.options-list');
        var result = container.querySelector('.answer-result');
        var questionImage = document.getElementById('questionImage');

        content.textContent = question.content;
        container.querySelector('.chapter-name').textContent = question.chapter_name || '';
        
        // 显示题型
        var typeText = '';
        if (question.question_type == 1) typeText = '单选题';
        else if (question.question_type == 2) typeText = '多选题';
        else if (question.question_type == 3) typeText = '判断题';
        container.querySelector('.question-type').textContent = typeText;

        // 显示图片（如有）
        if (question.image) {
            questionImage.innerHTML = '<img src="' + question.image + '" alt="题目图片" style="max-width:100%;border-radius:8px;margin:10px 0;">';
            questionImage.style.display = 'block';
        } else {
            questionImage.innerHTML = '';
            questionImage.style.display = 'none';
        }

        // 生成选项
        var html = '';
        var self = this;
        question.options.forEach(function(opt) {
            html += '<div class="option-item" onclick="App.selectOption(\'' + opt.option_key + '\')">' +
                '<span class="option-key">' + opt.option_key + '</span>' +
                '<span class="option-text">' + opt.option_content + '</span></div>';
        });
        options.innerHTML = html;

        // 重置状态
        result.style.display = 'none';
        container.querySelectorAll('.option-item').forEach(function(item) {
            item.classList.remove('selected', 'correct', 'wrong');
        });

        // 下一题按钮文字
        var btnNext = document.getElementById('btnNext');
        btnNext.textContent = '下一题';
        this.practice.answerSubmitted = false; // 标记是否已提交

        // 开始计时
        this.practice.startTime = Date.now();
        this.practice.answerTime = 0;
        this.practice.selectedAnswer = null; // 清空已选答案
    },

    /**
     * 选择答案（仅标记，不提交）
     */
    selectOption: function(optionKey) {
        var question = this.practice.questions[this.practice.currentIndex];
        var container = document.getElementById('questionContainer');
        var options = container.querySelectorAll('.option-item');
        var btnNext = document.getElementById('btnNext');

        // 多选题：切换选择状态
        if (question.question_type == 2) {
            options.forEach(function(item) {
                if (item.querySelector('.option-key').textContent === optionKey) {
                    item.classList.toggle('selected');
                }
            });
            // 收集已选答案（多选题可能选多个）
            var selected = [];
            options.forEach(function(item) {
                if (item.classList.contains('selected')) {
                    selected.push(item.querySelector('.option-key').textContent);
                }
            });
            this.practice.selectedAnswer = selected.join(',');
            btnNext.textContent = '确认答案';
            return;
        }

        // 单选题/判断题：标记已选
        options.forEach(function(item) {
            item.classList.remove('selected');
            if (item.querySelector('.option-key').textContent === optionKey) {
                item.classList.add('selected');
            }
        });
        this.practice.selectedAnswer = optionKey;
    },

    /**
     * 显示答题结果
     */
    showAnswerResult: function(data, userAnswer) {
        var question = this.practice.questions[this.practice.currentIndex];
        var container = document.getElementById('questionContainer');
        var options = container.querySelectorAll('.option-item');
        var result = container.querySelector('.answer-result');
        var isCorrect = data.is_correct;

        // 显示结果
        options.forEach(function(item) {
            var key = item.querySelector('.option-key').textContent;
            if (key === data.correct_answer) {
                item.classList.add('correct');
            } else if (key === userAnswer && !isCorrect) {
                item.classList.add('wrong');
            }
        });

        result.style.display = 'block';
        result.querySelector('.result-icon').textContent = isCorrect ? '✅' : '❌';
        result.querySelector('.result-text').textContent = isCorrect ? '回答正确' : '回答错误';
        result.querySelector('.analysis').textContent = data.analysis || '暂无解析';
    },

    /**
     * 下一题
     */
    nextQuestion: function() {
        var question = this.practice.questions[this.practice.currentIndex];
        var container = document.getElementById('questionContainer');
        var btnNext = document.getElementById('btnNext');

        // 检查是否已选择答案
        if (!this.practice.selectedAnswer) {
            this.showToast('请选择答案');
            return;
        }

        // 多选题且未确认答案
        if (question.question_type == 2 && btnNext.textContent === '确认答案') {
            // 计算答题用时
            this.practice.answerTime = Math.round((Date.now() - this.practice.startTime) / 1000);

            // 禁用选项点击
            var options = container.querySelectorAll('.option-item');
            options.forEach(function(item) {
                item.onclick = null;
            });

            // 提交多选题答案
            var self = this;
            API.submitAnswer(question.id, this.practice.selectedAnswer, this.practice.answerTime).then(function(res) {
                self.showAnswerResult(res.data, self.practice.selectedAnswer);
                btnNext.textContent = '下一题';
            }).catch(function(err) {
                self.showToast('提交失败');
            });
            return;
        }

        // 检查是否已显示结果
        var result = container.querySelector('.answer-result');
        if (result.style.display === 'none') {
            // 首次点击：提交答案并显示结果
            this.practice.answerTime = Math.round((Date.now() - this.practice.startTime) / 1000);

            // 禁用选项点击
            var options = container.querySelectorAll('.option-item');
            options.forEach(function(item) {
                item.onclick = null;
            });

            var self = this;
            API.submitAnswer(question.id, this.practice.selectedAnswer, this.practice.answerTime).then(function(res) {
                self.showAnswerResult(res.data, self.practice.selectedAnswer);
            }).catch(function(err) {
                self.showToast('提交失败');
            });
            return;
        }

        // 已显示结果，进入下一题
        this.practice.currentIndex++;

        if (this.practice.currentIndex >= this.practice.questions.length) {
            this.showToast('练习完成');
            this.showPage('chapters', { subject: this.practice.subject });
        } else {
            this.showPracticeQuestion();
        }
    },

    /**
     * 切换收藏
     */
    toggleFavorite: function() {
        var question = this.practice.questions[this.practice.currentIndex];
        if (!question) return;

        var isCollected = question.is_collected;
        var newStatus = isCollected ? 0 : 1;

        API.toggleCollection(question.id, newStatus).then(function() {
            question.is_collected = !isCollected;
            App.showToast(newStatus ? '已收藏' : '已取消收藏');
        }).catch(function(err) {
            App.showToast('操作失败');
        });
    },

    // ==================== 模拟考试 ====================

    /**
     * 开始考试
     */
    startExam: function(subject) {
        var self = this;

        API.generateExam(subject).then(function(res) {
            self.exam.id = res.data.exam_id;
            self.exam.subject = subject;
            self.exam.questions = res.data.question_ids;
            self.exam.answers = {};
            self.exam.currentIndex = 0;
            self.exam.startTime = Date.now();

            // 缓存考试信息
            localStorage.setItem('exam_cache', JSON.stringify({
                id: self.exam.id,
                subject: subject,
                questions: res.data.question_ids,
                expire_at: Date.now() + 3600000
            }));

            self.showPage('exam');
            document.getElementById('totalQ').textContent = self.exam.questions.length;
            self.loadExamQuestion();
            self.startExamTimer();

        }).catch(function(err) {
            self.showToast(err.message || '生成试卷失败');
        });
    },

    /**
     * 加载考试题目
     */
    loadExamQuestion: function() {
        var self = this;
        var questionId = this.exam.questions[this.exam.currentIndex];

        document.getElementById('currentQ').textContent = (this.exam.currentIndex + 1);

        API.getQuestionDetail(questionId).then(function(res) {
            var question = res.data;
            var container = document.getElementById('examQuestionContainer');
            var examImage = document.getElementById('examQuestionImage');

            container.querySelector('.question-content').textContent = question.content;
            
            // 显示题型
            var typeText = '';
            if (question.question_type == 1) typeText = '单选题';
            else if (question.question_type == 2) typeText = '多选题';
            else if (question.question_type == 3) typeText = '判断题';
            container.querySelector('.question-type').textContent = typeText;

            // 显示图片（如有）
            if (question.image) {
                examImage.innerHTML = '<img src="' + question.image + '" alt="题目图片" style="max-width:100%;border-radius:8px;margin:10px 0;">';
                examImage.style.display = 'block';
            } else {
                examImage.innerHTML = '';
                examImage.style.display = 'none';
            }

            // 保存当前题目信息
            this.exam.currentQuestion = question;

            // 生成选项
            var html = '';
            question.options.forEach(function(opt) {
                var selected = self.exam.answers[questionId] && self.exam.answers[questionId].includes(opt.option_key) ? 'selected' : '';
                html += '<div class="option-item ' + selected + '" onclick="App.selectExamOption(\'' + opt.option_key + '\')">' +
                    '<span class="option-key">' + opt.option_key + '</span>' +
                    '<span class="option-text">' + opt.option_content + '</span></div>';
            });
            container.querySelector('.options-list').innerHTML = html;

            // 更新下一题按钮
            var btnNext = document.getElementById('examBtnNext');
            btnNext.textContent = '下一题';

        }).catch(function(err) {
            self.showToast('加载失败');
        });
    },

    /**
     * 选择考试答案
     */
    selectExamOption: function(optionKey) {
        var questionId = this.exam.questions[this.exam.currentIndex];
        var container = document.getElementById('examQuestionContainer');
        var options = container.querySelectorAll('.option-item');
        var btnNext = document.getElementById('examBtnNext');
        var questionType = this.exam.currentQuestion ? this.exam.currentQuestion.question_type : 1;

        // 多选题：切换选择状态
        if (questionType == 2) {
            options.forEach(function(item) {
                if (item.querySelector('.option-key').textContent === optionKey) {
                    item.classList.toggle('selected');
                }
            });
            // 收集已选答案
            var selected = [];
            options.forEach(function(item) {
                if (item.classList.contains('selected')) {
                    selected.push(item.querySelector('.option-key').textContent);
                }
            });
            this.exam.answers[questionId] = selected;
            btnNext.textContent = '确认并下一题';
            return;
        }

        // 单选题/判断题：直接选中并保存
        options.forEach(function(item) {
            item.classList.remove('selected');
            if (item.querySelector('.option-key').textContent === optionKey) {
                item.classList.add('selected');
            }
        });
        this.exam.answers[questionId] = optionKey;
    },

    /**
     * 上一题
     */
    prevExamQuestion: function() {
        if (this.exam.currentIndex > 0) {
            this.exam.currentIndex--;
            this.loadExamQuestion();
        } else {
            this.showToast('已是第一题');
        }
    },

    /**
     * 下一题
     */
    nextExamQuestion: function() {
        if (this.exam.currentIndex < this.exam.questions.length - 1) {
            this.exam.currentIndex++;
            this.loadExamQuestion();
        } else {
            this.showToast('已是最后一题');
        }
    },

    /**
     * 显示交卷确认
     */
    showSubmitConfirm: function() {
        var answered = Object.keys(this.exam.answers).length;
        var total = this.exam.questions.length;

        if (confirm('已回答 ' + answered + '/' + total + ' 题，确定要交卷吗？')) {
            this.submitExam();
        }
    },

    /**
     * 提交试卷
     */
    submitExam: function() {
        this.stopExamTimer();

        var timeUsed = Math.round((Date.now() - this.exam.startTime) / 1000);
        var self = this;

        API.submitExam(this.exam.id, this.exam.answers, timeUsed).then(function(res) {
            localStorage.removeItem('exam_cache');
            self.showExamResult(res.data);
            self.showPage('exam-result');
        }).catch(function(err) {
            self.showToast(err.message || '提交失败');
        });
    },

    /**
     * 显示考试成绩
     */
    showExamResult: function(data) {
        var container = document.getElementById('resultContainer');
        var isPass = data.score >= Config.EXAM.PASSING_SCORE;

        container.innerHTML = '<div class="result-container">' +
            '<div class="score-circle">' +
            '<span class="score">' + data.score + '</span>' +
            '<span class="total">分</span>' +
            '</div>' +
            '<div class="score-status ' + (isPass ? 'pass' : 'fail') + '">' + (isPass ? '🎉 恭喜及格！' : '😅 未及格，继续加油') + '</div>' +
            '<div class="score-detail">' +
            '<div class="item"><div class="num">' + data.correct_count + '</div><div class="label">正确</div></div>' +
            '<div class="item"><div class="num">' + (data.total_questions - data.correct_count) + '</div><div class="label">错误</div></div>' +
            '<div class="item"><div class="num">' + data.total_questions + '</div><div class="label">总题</div></div>' +
            '</div>' +
            '<button class="btn-primary" onclick="App.showPage(\'home\')">返回首页</button>' +
            '<button class="btn-secondary" onclick="App.startExam(' + self.exam.subject + ')">再次考试</button>' +
            '</div>';
    },

    /**
     * 开始考试计时器
     */
    startExamTimer: function() {
        var self = this;
        var timeLeft = Config.EXAM.TIME_LIMIT * 60; // 秒

        this.exam.timer = setInterval(function() {
            timeLeft--;

            if (timeLeft <= 0) {
                self.stopExamTimer();
                self.showToast('时间到，自动交卷');
                self.submitExam();
                return;
            }

            var minutes = Math.floor(timeLeft / 60);
            var seconds = timeLeft % 60;
            document.getElementById('examTimer').textContent =
                minutes + ':' + (seconds < 10 ? '0' : '') + seconds;

        }, 1000);
    },

    /**
     * 停止考试计时器
     */
    stopExamTimer: function() {
        if (this.exam.timer) {
            clearInterval(this.exam.timer);
            this.exam.timer = null;
        }
    },

    // ==================== 错题本 ====================

    /**
     * 加载错题列表
     */
    loadErrorList: function() {
        var self = this;

        API.getErrorList().then(function(res) {
            var container = document.getElementById('errorListContainer');

            if (res.data.total === 0) {
                container.innerHTML = '<div class="empty-state"><div class="icon">✨</div><p>暂无错题，太棒了！</p></div>';
                return;
            }

            var html = '';
            res.data.list.forEach(function(item) {
                html += '<div class="record-item">' +
                    '<div class="record-header">' +
                    '<span class="subject">第' + item.id + '题</span>' +
                    '<span class="date">错误' + item.error_count + '次</span>' +
                    '</div>' +
                    '<div class="record-detail">' +
                    '<span>科目' + item.subject + '</span>' +
                    '<span>' + item.chapter_name + '</span>' +
                    '</div></div>';
            });

            container.innerHTML = html;

        }).catch(function(err) {
            document.getElementById('errorListContainer').innerHTML = '<div class="empty-state"><div class="icon">❌</div><p>加载失败</p></div>';
        });
    },

    // ==================== 收藏列表 ====================

    /**
     * 加载收藏列表
     */
    loadCollectionList: function() {
        var self = this;

        API.getCollectionList().then(function(res) {
            var container = document.getElementById('collectionListContainer');

            if (res.data.total === 0) {
                container.innerHTML = '<div class="empty-state"><div class="icon">⭐</div><p>暂无收藏</p></div>';
                return;
            }

            var html = '';
            res.data.list.forEach(function(item) {
                html += '<div class="record-item">' +
                    '<div class="record-header">' +
                    '<span class="subject">' + item.content.substr(0, 20) + '...</span>' +
                    '</div>' +
                    '<div class="record-detail">' +
                    '<span>科目' + item.subject + '</span>' +
                    '<span>' + (item.question_type == 1 ? '选择题' : '判断题') + '</span>' +
                    '</div></div>';
            });

            container.innerHTML = html;

        }).catch(function(err) {
            document.getElementById('collectionListContainer').innerHTML = '<div class="empty-state"><div class="icon">❌</div><p>加载失败</p></div>';
        });
    },

    // ==================== 成绩记录 ====================

    /**
     * 加载成绩记录
     */
    loadExamRecords: function() {
        var self = this;

        API.getExamRecords().then(function(res) {
            var container = document.getElementById('examRecordsContainer');

            if (res.data.total === 0) {
                container.innerHTML = '<div class="empty-state"><div class="icon">📊</div><p>暂无考试记录</p></div>';
                return;
            }

            var html = '';
            res.data.list.forEach(function(item) {
                var isPass = item.score >= Config.EXAM.PASSING_SCORE;
                html += '<div class="record-item">' +
                    '<div class="record-header">' +
                    '<span class="subject">科目' + item.subject + '</span>' +
                    '<span class="date">' + item.submit_time + '</span>' +
                    '</div>' +
                    '<div class="record-detail">' +
                    '<span class="score">' + item.score + '分</span>' +
                    '<span>正确' + item.correct_count + '/' + item.total_questions + '</span>' +
                    '<span>' + (isPass ? '✅及格' : '❌不及格') + '</span>' +
                    '</div></div>';
            });

            container.innerHTML = html;

        }).catch(function(err) {
            document.getElementById('examRecordsContainer').innerHTML = '<div class="empty-state"><div class="icon">❌</div><p>加载失败</p></div>';
        });
    },

    // ==================== 工具方法 ====================

    /**
     * 显示加载中
     */
    showLoading: function() {
        document.getElementById('loading').style.display = 'flex';
    },

    /**
     * 隐藏加载中
     */
    hideLoading: function() {
        document.getElementById('loading').style.display = 'none';
    },

    /**
     * 显示提示
     */
    showToast: function(text) {
        var toast = document.getElementById('toast');
        var toastText = document.getElementById('toastText');
        toastText.textContent = text;
        toast.style.display = 'block';

        setTimeout(function() {
            toast.style.display = 'none';
        }, 2000);
    }
};

// ==================== 全局函数 ====================

/**
 * 全局登录函数 - 供HTML的onclick调用
 */
function doLogin() {
    App.doLogin();
}

/**
 * 全局显示页面函数
 */
function showPage(pageId, params) {
    App.showPage(pageId, params);
}

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    App.init();
});
