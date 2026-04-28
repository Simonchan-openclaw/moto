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
        startTime: null
    },

    /**
     * 初始化应用
     */
    init: function() {
        // 获取存储的用户信息
        this.token = localStorage.getItem('token');
        this.user = JSON.parse(localStorage.getItem('user') || 'null');

        // 检查激活状态
        this.checkActivation();

        // 初始化页面
        this.updateUserInfo();

        // 清理过期缓存
        this.cleanupCache();
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
     * 检查激活状态
     */
    checkActivation: function() {
        if (!Config.ACTIVATION.REQUIRED) {
            return;
        }

        var deviceId = this.getDeviceId();
        var cachedStatus = localStorage.getItem('activation_status');

        if (cachedStatus) {
            var status = JSON.parse(cachedStatus);
            if (status.activated && status.expire_at > Date.now()) {
                return; // 已激活且未过期
            }
        }

        // 显示激活弹窗
        this.showActivationModal();
    },

    /**
     * 显示激活弹窗
     */
    showActivationModal: function() {
        var html = '<div class="activation-modal" id="activationModal">' +
            '<div class="activation-content">' +
            '<h3>🔒 请先激活</h3>' +
            '<p style="text-align:center;color:#666;margin-bottom:15px;">请输入教练提供的激活码</p>' +
            '<div class="form-group">' +
            '<input type="text" id="activationCode" placeholder="请输入激活码" maxlength="32">' +
            '</div>' +
            '<button class="btn-primary" onclick="App.doActivation()">激活</button>' +
            '<p style="text-align:center;color:#999;font-size:12px;margin-top:10px;">激活后即可使用全部功能</p>' +
            '</div></div>';

        document.body.insertAdjacentHTML('beforeend', html);
    },

    /**
     * 执行激活
     */
    doActivation: function() {
        var code = document.getElementById('activationCode').value.trim();
        if (!code) {
            this.showToast('请输入激活码');
            return;
        }

        var deviceId = this.getDeviceId();

        API.activate(code, deviceId).then(function(res) {
            // 保存激活状态
            var status = {
                activated: true,
                expire_at: new Date(res.data.expire_at).getTime()
            };
            localStorage.setItem('activation_status', JSON.stringify(status));

            // 关闭弹窗
            document.getElementById('activationModal').remove();
            App.showToast('激活成功！');
        }).catch(function(err) {
            App.showToast(err.message || '激活失败');
        });
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
        }
    },

    /**
     * 更新用户信息显示
     */
    updateUserInfo: function() {
        var userInfo = document.getElementById('userInfo');
        var nickname = userInfo.querySelector('.nickname');
        var status = userInfo.querySelector('.status');

        if (this.user) {
            nickname.textContent = this.user.nickname || '摩托学员';
            status.textContent = '已登录';
            userInfo.onclick = null;
        } else {
            nickname.textContent = '未登录';
            status.textContent = '点击登录';
            userInfo.onclick = function() {
                App.showPage('login');
            };
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
        var code = document.getElementById('loginCode').value.trim();

        if (!phone || !/^1[3-9]\d{9}$/.test(phone)) {
            this.showToast('请输入正确的手机号');
            return;
        }

        if (!code) {
            this.showToast('请输入验证码');
            return;
        }

        API.login(phone, code).then(function(res) {
            App.token = res.data.token;
            App.user = res.data.userInfo;

            localStorage.setItem('token', App.token);
            localStorage.setItem('user', JSON.stringify(App.user));

            App.updateUserInfo();
            App.showPage('home');
            App.showToast('登录成功');
        }).catch(function(err) {
            App.showToast(err.message || '登录失败');
        });
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

        content.textContent = question.content;
        container.querySelector('.chapter-name').textContent = question.chapter_name || '';
        container.querySelector('.question-type').textContent = question.question_type == 1 ? '选择题' : '判断题';

        // 生成选项
        var html = '';
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

        // 开始计时
        this.practice.startTime = Date.now();
        this.practice.answerTime = 0;
    },

    /**
     * 选择答案
     */
    selectOption: function(optionKey) {
        var question = this.practice.questions[this.practice.currentIndex];
        var container = document.getElementById('questionContainer');
        var options = container.querySelectorAll('.option-item');
        var result = container.querySelector('.answer-result');

        // 计算答题用时
        this.practice.answerTime = Math.round((Date.now() - this.practice.startTime) / 1000);

        // 禁用选项点击
        options.forEach(function(item) {
            item.onclick = null;
        });

        // 显示选择
        options.forEach(function(item) {
            if (item.querySelector('.option-key').textContent === optionKey) {
                item.classList.add('selected');
            }
        });

        // 提交答案
        var self = this;
        API.submitAnswer(question.id, optionKey, this.practice.answerTime).then(function(res) {
            var isCorrect = res.data.is_correct;

            // 显示结果
            options.forEach(function(item) {
                var key = item.querySelector('.option-key').textContent;
                if (key === res.data.correct_answer) {
                    item.classList.add('correct');
                } else if (key === optionKey && !isCorrect) {
                    item.classList.add('wrong');
                }
            });

            result.style.display = 'block';
            result.querySelector('.result-icon').textContent = isCorrect ? '✅' : '❌';
            result.querySelector('.result-text').textContent = isCorrect ? '回答正确' : '回答错误';
            result.querySelector('.analysis').textContent = res.data.analysis || '暂无解析';

        }).catch(function(err) {
            self.showToast('提交失败');
        });
    },

    /**
     * 下一题
     */
    nextQuestion: function() {
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

            container.querySelector('.question-content').textContent = question.content;
            container.querySelector('.question-type').textContent = question.question_type == 1 ? '选择题' : '判断题';

            // 生成选项
            var html = '';
            question.options.forEach(function(opt) {
                var selected = self.exam.answers[questionId] === opt.option_key ? 'selected' : '';
                html += '<div class="option-item ' + selected + '" onclick="App.selectExamOption(\'' + opt.option_key + '\')">' +
                    '<span class="option-key">' + opt.option_key + '</span>' +
                    '<span class="option-text">' + opt.option_content + '</span></div>';
            });
            container.querySelector('.options-list').innerHTML = html;

        }).catch(function(err) {
            self.showToast('加载失败');
        });
    },

    /**
     * 选择考试答案
     */
    selectExamOption: function(optionKey) {
        var questionId = this.exam.questions[this.exam.currentIndex];
        this.exam.answers[questionId] = optionKey;

        var container = document.getElementById('examQuestionContainer');
        container.querySelectorAll('.option-item').forEach(function(item) {
            var key = item.querySelector('.option-key').textContent;
            item.classList.toggle('selected', key === optionKey);
        });
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

// 页面加载完成后初始化
document.addEventListener('DOMContentLoaded', function() {
    App.init();
});
