/**
 * 管理后台应用主逻辑
 */

var Admin = {
    currentPage: 'dashboard',
    pageParams: {
        page: 1,
        pageSize: 20
    },

    init: function() {
        // 检查登录状态
        var token = localStorage.getItem('admin_token');
        if (!token) {
            this.showLogin();
            return;
        }

        // 绑定导航点击
        this.bindNav();

        // 加载默认页面
        this.loadPage('dashboard');
    },

    showLogin: function() {
        var html = '<div class="login-container">' +
            '<div class="login-box">' +
            '<h1>🏍️ 题库管理后台</h1>' +
            '<div class="form-group">' +
            '<label class="form-label">用户名</label>' +
            '<input type="text" class="form-input" id="loginUsername" placeholder="请输入用户名">' +
            '</div>' +
            '<div class="form-group">' +
            '<label class="form-label">密码</label>' +
            '<input type="password" class="form-input" id="loginPassword" placeholder="请输入密码">' +
            '</div>' +
            '<button class="btn btn-primary" style="width:100%;padding:12px;" onclick="Admin.doLogin()">登录</button>' +
            '<p style="text-align:center;margin-top:15px;color:#999;font-size:12px;">测试账号: admin / admin123</p>' +
            '</div></div>';

        document.body.innerHTML = html;
    },

    doLogin: function() {
        var username = document.getElementById('loginUsername').value;
        var password = document.getElementById('loginPassword').value;

        if (!username || !password) {
            this.showToast('请输入用户名和密码');
            return;
        }

        // 模拟登录（测试用）
        if (username === 'admin' && password === 'admin123') {
            localStorage.setItem('admin_token', Config.ADMIN_TOKEN);
            location.reload();
            return;
        }

        var self = this;
        API.login(username, password).then(function() {
            localStorage.setItem('admin_token', Config.ADMIN_TOKEN);
            location.reload();
        }).catch(function(err) {
            self.showToast(err.message || '登录失败');
        });
    },

    logout: function() {
        localStorage.removeItem('admin_token');
        location.reload();
    },

    bindNav: function() {
        var self = this;
        document.querySelectorAll('.nav-item').forEach(function(item) {
            item.addEventListener('click', function(e) {
                e.preventDefault();
                var page = this.dataset.page;
                self.loadPage(page);
            });
        });
    },

    loadPage: function(page) {
        this.currentPage = page;

        // 更新导航状态
        document.querySelectorAll('.nav-item').forEach(function(item) {
            item.classList.remove('active');
            if (item.dataset.page === page) {
                item.classList.add('active');
            }
        });

        // 更新标题
        var titles = {
            dashboard: '控制台',
            questions: '题库管理',
            chapters: '章节管理',
            users: '用户管理',
            coaches: '教练管理',
            activations: '激活记录',
            statistics: '数据统计',
            settings: '系统设置'
        };
        document.getElementById('pageTitle').textContent = titles[page] || '控制台';

        // 加载页面内容
        var content = document.getElementById('contentArea');
        switch (page) {
            case 'dashboard':
                this.renderDashboard(content);
                break;
            case 'questions':
                this.renderQuestions(content);
                break;
            case 'chapters':
                this.renderChapters(content);
                break;
            case 'users':
                this.renderUsers(content);
                break;
            case 'coaches':
                this.renderCoaches(content);
                break;
            case 'activations':
                this.renderActivations(content);
                break;
            case 'statistics':
                this.renderStatistics(content);
                break;
            case 'settings':
                this.renderSettings(content);
                break;
        }
    },

    // ==================== 控制台 ====================

    renderDashboard: function(container) {
        var self = this;
        container.innerHTML = '<div class="stats-grid">' +
            '<div class="stat-card">' +
            '<div class="stat-icon blue">📝</div>' +
            '<div class="stat-info">' +
            '<div class="stat-label">题目总数</div>' +
            '<div class="stat-value" id="statQuestions">-</div>' +
            '</div></div>' +
            '<div class="stat-card">' +
            '<div class="stat-icon green">👥</div>' +
            '<div class="stat-info">' +
            '<div class="stat-label">注册用户</div>' +
            '<div class="stat-value" id="statUsers">-</div>' +
            '</div></div>' +
            '<div class="stat-card">' +
            '<div class="stat-icon orange">👨‍🏫</div>' +
            '<div class="stat-info">' +
            '<div class="stat-label">注册教练</div>' +
            '<div class="stat-value" id="statCoaches">-</div>' +
            '</div></div>' +
            '<div class="stat-card">' +
            '<div class="stat-icon red">🎫</div>' +
            '<div class="stat-info">' +
            '<div class="stat-label">激活次数</div>' +
            '<div class="stat-value" id="statActivations">-</div>' +
            '</div></div></div>' +

            '<div style="display:grid;grid-template-columns:2fr 1fr;gap:20px;margin-top:20px;">' +
            '<div class="card">' +
            '<div class="card-header">' +
            '<h3 class="card-title">📈 今日激活趋势</h3>' +
            '</div>' +
            '<div id="dashboardActivationsChart" style="width:100%;height:260px;"></div></div>' +

            '<div class="card">' +
            '<div class="card-header">' +
            '<h3 class="card-title">📊 激活状态分布</h3>' +
            '</div>' +
            '<div id="dashboardStatusChart" style="width:100%;height:260px;"></div></div></div>' +

            '<div class="card" style="margin-top:20px;">' +
            '<div class="card-header">' +
            '<h3 class="card-title">⚡ 快捷操作</h3>' +
            '</div>' +
            '<div style="display:flex;gap:12px;flex-wrap:wrap;">' +
            '<button class="btn btn-primary" onclick="Admin.loadPage(\'questions\');">📝 添加题目</button>' +
            '<button class="btn btn-success" onclick="Admin.loadPage(\'chapters\');">📚 管理章节</button>' +
            '<button class="btn btn-warning" onclick="Admin.loadPage(\'activations\');">🎫 查看激活</button>' +
            '<button class="btn btn-primary" onclick="Admin.showImportModal();">📤 批量导入</button>' +
            '<button class="btn btn-success" onclick="Admin.loadPage(\'statistics\');">📈 数据统计</button>' +
            '</div></div>' +

            '<div class="card" style="margin-top:20px;">' +
            '<div class="card-header">' +
            '<h3 class="card-title">📋 最新激活记录</h3>' +
            '</div>' +
            '<div class="table-container" id="recentActivations"></div></div>';

        // 加载统计数据（模拟）
        document.getElementById('statQuestions').textContent = '523';
        document.getElementById('statUsers').textContent = '1,234';
        document.getElementById('statCoaches').textContent = '56';
        document.getElementById('statActivations').textContent = '2,890';

        // 加载最新激活记录
        this.loadRecentActivations();

        // 渲染控制台图表
        setTimeout(function() {
            self.renderDashboardCharts();
        }, 100);
    },

    renderDashboardCharts: function() {
        // 激活趋势图
        var actChart = echarts.init(document.getElementById('dashboardActivationsChart'));
        actChart.setOption({
            tooltip: { trigger: 'axis' },
            grid: { left: '3%', right: '4%', bottom: '10%', top: '10%', containLabel: true },
            xAxis: {
                type: 'category',
                data: ['06:00', '08:00', '10:00', '12:00', '14:00', '16:00', '18:00', '20:00', '22:00']
            },
            yAxis: { type: 'value' },
            series: [{
                name: '激活次数',
                type: 'line',
                smooth: true,
                areaStyle: { color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                    { offset: 0, color: 'rgba(102, 126, 234, 0.4)' },
                    { offset: 1, color: 'rgba(102, 126, 234, 0.05)' }
                ]) },
                lineStyle: { color: '#667eea', width: 3 },
                itemStyle: { color: '#667eea' },
                data: [5, 12, 18, 25, 32, 28, 22, 15, 8]
            }]
        });

        // 激活状态分布饼图
        var statusChart = echarts.init(document.getElementById('dashboardStatusChart'));
        statusChart.setOption({
            tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
            legend: { orient: 'vertical', right: '5%', top: 'center', textStyle: { fontSize: 12 } },
            color: ['#52c41a', '#667eea', '#ff4d4f', '#faad14'],
            series: [{
                type: 'pie',
                radius: ['45%', '70%'],
                center: ['35%', '50%'],
                avoidLabelOverlap: false,
                itemStyle: { borderRadius: 6, borderColor: '#fff', borderWidth: 2 },
                label: { show: false },
                emphasis: { label: { show: true, fontSize: 13, fontWeight: 'bold' } },
                data: [
                    { value: 2150, name: '已激活' },
                    { value: 420, name: '待激活' },
                    { value: 280, name: '已失效' },
                    { value: 40, name: '已退款' }
                ]
            }]
        });

        window.addEventListener('resize', function() {
            actChart.resize();
            statusChart.resize();
        });
    },

    loadRecentActivations: function() {
        var html = '<table>' +
            '<thead><tr><th>学员手机</th><th>教练</th><th>金额</th><th>状态</th><th>时间</th></tr></thead>' +
            '<tbody>' +
            '<tr><td>139****9000</td><td>张教练</td><td>¥18</td><td><span class="tag tag-success">已激活</span></td><td>2026-04-27 10:30</td></tr>' +
            '<tr><td>138****8000</td><td>李教练</td><td>¥18</td><td><span class="tag tag-primary">待激活</span></td><td>2026-04-27 09:15</td></tr>' +
            '<tr><td>137****7000</td><td>王教练</td><td>¥18</td><td><span class="tag tag-success">已激活</span></td><td>2026-04-26 18:45</td></tr>' +
            '</tbody></table>';

        document.getElementById('recentActivations').innerHTML = html;
    },

    // ==================== 题库管理 ====================

    renderQuestions: function(container) {
        var self = this;
        container.innerHTML = '<div class="card">' +
            '<div class="card-header">' +
            '<h3 class="card-title">题目列表</h3>' +
            '<button class="btn btn-primary" onclick="Admin.showQuestionModal()">添加题目</button>' +
            '</div>' +
            '<div class="search-bar">' +
            '<input type="text" class="search-input" id="searchKeyword" placeholder="搜索题目内容...">' +
            '<select class="form-input filter-select" id="filterSubject">' +
            '<option value="">全部科目</option>' +
            '<option value="1">科目一</option>' +
            '<option value="4">科目四</option>' +
            '</select>' +
            '<select class="form-input filter-select" id="filterType">' +
            '<option value="">题型</option>' +
            '<option value="1">选择题</option>' +
            '<option value="2">判断题</option>' +
            '</select>' +
            '<button class="btn btn-primary" onclick="self.loadQuestions()">搜索</button>' +
            '</div>' +
            '<div class="table-container" id="questionsTable"></div>' +
            '<div class="pagination">' +
            '<span class="pagination-info" id="questionsInfo">共 0 条</span>' +
            '<div class="pagination-buttons" id="questionsPagination"></div>' +
            '</div></div>';

        this.loadQuestions();
    },

    loadQuestions: function() {
        // 模拟数据
        var html = '<table>' +
            '<thead><tr><th>ID</th><th>科目</th><th>题型</th><th>章节</th><th>题目内容</th><th>答案</th><th>状态</th><th>操作</th></tr></thead>' +
            '<tbody>';

        for (var i = 1; i <= 10; i++) {
            var subject = i % 2 === 0 ? '科目一' : '科目四';
            var type = i % 3 === 0 ? '判断题' : '选择题';
            var status = i % 4 === 0 ? '<span class="tag tag-danger">禁用</span>' : '<span class="tag tag-success">启用</span>';

            html += '<tr>' +
                '<td>' + (1000 + i) + '</td>' +
                '<td>' + subject + '</td>' +
                '<td>' + type + '</td>' +
                '<td>第' + (i % 5 + 1) + '章</td>' +
                '<td style="max-width:200px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;">机动车驾驶人应当依法遵守道路交通安全法律、法规的规定...</td>' +
                '<td>B</td>' +
                '<td>' + status + '</td>' +
                '<td>' +
                '<button class="btn btn-sm btn-primary" onclick="Admin.showQuestionModal(' + (1000 + i) + ')">编辑</button>' +
                '<button class="btn btn-sm btn-danger" onclick="Admin.deleteQuestion(' + (1000 + i) + ')">删除</button>' +
                '</td></tr>';
        }

        html += '</tbody></table>';
        document.getElementById('questionsTable').innerHTML = html;
        document.getElementById('questionsInfo').textContent = '共 500+ 条';
    },

    showQuestionModal: function(id) {
        var title = id ? '编辑题目' : '添加题目';
        var isEdit = !!id;

        var body = '<form id="questionForm">' +
            '<div class="form-group">' +
            '<label class="form-label">科目</label>' +
            '<select class="form-input" id="qSubject" required>' +
            '<option value="1">科目一</option>' +
            '<option value="4">科目四</option>' +
            '</select></div>' +
            '<div class="form-group">' +
            '<label class="form-label">题型</label>' +
            '<select class="form-input" id="qType" required>' +
            '<option value="1">选择题</option>' +
            '<option value="2">判断题</option>' +
            '</select></div>' +
            '<div class="form-group">' +
            '<label class="form-label">所属章节</label>' +
            '<select class="form-input" id="qChapter" required>' +
            '<option value="1">道路交通安全法律</option>' +
            '<option value="2">交通信号</option>' +
            '</select></div>' +
            '<div class="form-group">' +
            '<label class="form-label">题目内容</label>' +
            '<textarea class="form-input" id="qContent" rows="3" required placeholder="请输入题目内容"></textarea></div>' +
            '<div class="form-group">' +
            '<label class="form-label">选项A</label>' +
            '<input type="text" class="form-input" id="qOptionA" placeholder="请输入选项A"></div>' +
            '<div class="form-group">' +
            '<label class="form-label">选项B</label>' +
            '<input type="text" class="form-input" id="qOptionB" placeholder="请输入选项B"></div>' +
            '<div class="form-group">' +
            '<label class="form-label">选项C</label>' +
            '<input type="text" class="form-input" id="qOptionC" placeholder="请输入选项C"></div>' +
            '<div class="form-group">' +
            '<label class="form-label">选项D</label>' +
            '<input type="text" class="form-input" id="qOptionD" placeholder="请输入选项D"></div>' +
            '<div class="form-group">' +
            '<label class="form-label">正确答案</label>' +
            '<select class="form-input" id="qAnswer" required>' +
            '<option value="A">A</option>' +
            '<option value="B">B</option>' +
            '<option value="C">C</option>' +
            '<option value="D">D</option>' +
            '<option value="true">正确</option>' +
            '<option value="false">错误</option>' +
            '</select></div>' +
            '<div class="form-group">' +
            '<label class="form-label">题目解析</label>' +
            '<textarea class="form-input" id="qAnalysis" rows="2" placeholder="请输入题目解析"></textarea></div>' +
            '</form>';

        var footer = '<button class="btn btn-primary" onclick="Admin.saveQuestion()">保存</button>' +
            '<button class="btn" onclick="Admin.closeModal()">取消</button>';

        this.showModal(title, body, footer);
    },

    saveQuestion: function() {
        var form = document.getElementById('questionForm');
        if (!form.checkValidity()) {
            form.reportValidity();
            return;
        }

        // 获取表单数据
        var data = {
            subject: document.getElementById('qSubject').value,
            question_type: document.getElementById('qType').value,
            chapter_id: document.getElementById('qChapter').value,
            title: document.getElementById('qContent').value,
            option_a: document.getElementById('qOptionA').value,
            option_b: document.getElementById('qOptionB').value,
            option_c: document.getElementById('qOptionC').value,
            option_d: document.getElementById('qOptionD').value,
            answer: document.getElementById('qAnswer').value,
            analysis: document.getElementById('qAnalysis').value
        };

        console.log('保存题目:', data);
        this.closeModal();
        this.showToast('保存成功');
        this.loadQuestions();
    },

    deleteQuestion: function(id) {
        if (!confirm('确定要删除这道题目吗？')) return;
        this.showToast('删除成功');
        this.loadQuestions();
    },

    // ==================== 章节管理 ====================

    renderChapters: function(container) {
        container.innerHTML = '<div class="card">' +
            '<div class="card-header">' +
            '<h3 class="card-title">章节管理</h3>' +
            '<button class="btn btn-primary" onclick="Admin.showChapterModal()">添加章节</button>' +
            '</div>' +
            '<div class="table-container">' +
            '<table>' +
            '<thead><tr><th>ID</th><th>科目</th><th>章节名称</th><th>排序</th><th>题目数</th><th>状态</th><th>操作</th></tr></thead>' +
            '<tbody id="chaptersBody"></tbody>' +
            '</table></div></div>';

        // 加载章节数据
        var html = '';
        var subjects = ['科目一', '科目四'];
        var chapters = ['道路交通安全法律、法规和规章', '道路交通信号及其含义', '安全行车、文明驾驶知识', '机动车构造与维护常识', '安全驾驶行为与应急处置'];

        for (var i = 1; i <= 10; i++) {
            var subjectIdx = Math.floor((i - 1) / 5);
            var chapterIdx = (i - 1) % 5;
            html += '<tr>' +
                '<td>' + i + '</td>' +
                '<td>' + subjects[subjectIdx] + '</td>' +
                '<td>' + chapters[chapterIdx] + '</td>' +
                '<td>' + (chapterIdx + 1) + '</td>' +
                '<td>' + (Math.floor(Math.random() * 50) + 10) + '</td>' +
                '<td><span class="tag tag-success">启用</span></td>' +
                '<td>' +
                '<button class="btn btn-sm btn-primary" onclick="Admin.showChapterModal(' + i + ')">编辑</button>' +
                '<button class="btn btn-sm btn-danger" onclick="Admin.deleteChapter(' + i + ')">删除</button>' +
                '</td></tr>';
        }

        document.getElementById('chaptersBody').innerHTML = html;
    },

    showChapterModal: function(id) {
        var title = id ? '编辑章节' : '添加章节';
        var body = '<form id="chapterForm">' +
            '<div class="form-group">' +
            '<label class="form-label">科目</label>' +
            '<select class="form-input" id="cSubject" required>' +
            '<option value="1">科目一</option>' +
            '<option value="4">科目四</option>' +
            '</select></div>' +
            '<div class="form-group">' +
            '<label class="form-label">章节名称</label>' +
            '<input type="text" class="form-input" id="cName" required placeholder="请输入章节名称"></div>' +
            '<div class="form-group">' +
            '<label class="form-label">排序（数字越小越靠前）</label>' +
            '<input type="number" class="form-input" id="cSort" value="1" min="1"></div>' +
            '</form>';

        var footer = '<button class="btn btn-primary" onclick="Admin.saveChapter()">保存</button>' +
            '<button class="btn" onclick="Admin.closeModal()">取消</button>';

        this.showModal(title, body, footer);
    },

    saveChapter: function() {
        this.closeModal();
        this.showToast('保存成功');
    },

    deleteChapter: function(id) {
        if (!confirm('确定要删除这个章节吗？')) return;
        this.showToast('删除成功');
    },

    // ==================== 用户管理 ====================

    renderUsers: function(container) {
        container.innerHTML = '<div class="card">' +
            '<div class="card-header">' +
            '<h3 class="card-title">用户列表</h3>' +
            '</div>' +
            '<div class="search-bar">' +
            '<input type="text" class="search-input" id="searchUser" placeholder="搜索手机号...">' +
            '<button class="btn btn-primary" onclick="Admin.loadUsers()">搜索</button>' +
            '</div>' +
            '<div class="table-container">' +
            '<table>' +
            '<thead><tr><th>ID</th><th>手机号</th><th>昵称</th><th>激活状态</th><th>注册时间</th><th>最后登录</th></tr></thead>' +
            '<tbody id="usersBody"></tbody>' +
            '</table></div></div>';

        var html = '';
        for (var i = 1; i <= 15; i++) {
            var phone = '138' + String(Math.floor(Math.random() * 100000000)).padStart(8, '0');
            var activated = Math.random() > 0.3;
            html += '<tr>' +
                '<td>' + (1000 + i) + '</td>' +
                '<td>' + phone.substr(0, 3) + '****' + phone.substr(-4) + '</td>' +
                '<td>学员' + i + '</td>' +
                '<td>' + (activated ? '<span class="tag tag-success">已激活</span>' : '<span class="tag tag-warning">未激活</span>') + '</td>' +
                '<td>2026-04-' + (10 + i % 18) + '</td>' +
                '<td>2026-04-27</td></tr>';
        }

        document.getElementById('usersBody').innerHTML = html;
    },

    // ==================== 教练管理 ====================

    renderCoaches: function(container) {
        container.innerHTML = '<div class="card">' +
            '<div class="card-header">' +
            '<h3 class="card-title">教练列表</h3>' +
            '</div>' +
            '<div class="table-container">' +
            '<table>' +
            '<thead><tr><th>ID</th><th>手机号</th><th>姓名</th><th>余额</th><th>累计充值</th><th>激活次数</th><th>注册时间</th></tr></thead>' +
            '<tbody id="coachesBody"></tbody>' +
            '</table></div></div>';

        var html = '';
        for (var i = 1; i <= 10; i++) {
            var phone = '139' + String(Math.floor(Math.random() * 100000000)).padStart(8, '0');
            var balance = (Math.random() * 500 + 50).toFixed(2);
            var totalRecharged = (Math.random() * 2000 + 100).toFixed(2);
            html += '<tr>' +
                '<td>' + i + '</td>' +
                '<td>' + phone.substr(0, 3) + '****' + phone.substr(-4) + '</td>' +
                '<td>教练' + i + '</td>' +
                '<td style="color:#52c41a;font-weight:bold;">¥' + balance + '</td>' +
                '<td>¥' + totalRecharged + '</td>' +
                '<td>' + Math.floor(Math.random() * 50) + '</td>' +
                '<td>2026-04-' + (5 + i % 22) + '</td></tr>';
        }

        document.getElementById('coachesBody').innerHTML = html;
    },

    // ==================== 激活记录 ====================

    renderActivations: function(container) {
        container.innerHTML = '<div class="card">' +
            '<div class="card-header">' +
            '<h3 class="card-title">激活记录</h3>' +
            '</div>' +
            '<div class="search-bar">' +
            '<select class="form-input filter-select" id="filterStatus">' +
            '<option value="">全部状态</option>' +
            '<option value="0">待激活</option>' +
            '<option value="1">已激活</option>' +
            '<option value="2">已失效</option>' +
            '<option value="3">已退款</option>' +
            '</select>' +
            '<button class="btn btn-primary" onclick="Admin.loadActivations()">筛选</button>' +
            '</div>' +
            '<div class="table-container">' +
            '<table>' +
            '<thead><tr><th>ID</th><th>教练</th><th>学员手机</th><th>激活码</th><th>设备ID</th><th>金额</th><th>状态</th><th>激活时间</th><th>到期时间</th></tr></thead>' +
            '<tbody id="activationsBody"></tbody>' +
            '</table></div></div>';

        var html = '';
        var statuses = [
            { text: '已激活', class: 'success' },
            { text: '待激活', class: 'primary' },
            { text: '已失效', class: 'danger' }
        ];

        for (var i = 1; i <= 15; i++) {
            var phone = '137' + String(Math.floor(Math.random() * 100000000)).padStart(8, '0');
            var status = statuses[i % 3];
            html += '<tr>' +
                '<td>' + i + '</td>' +
                '<td>教练' + (i % 5 + 1) + '</td>' +
                '<td>' + phone.substr(0, 3) + '****' + phone.substr(-4) + '</td>' +
                '<td><code>' + Math.random().toString(36).substr(2, 10).toUpperCase() + '</code></td>' +
                '<td style="font-size:11px;color:#999;">' + Math.random().toString(36).substr(2, 8) + '...</td>' +
                '<td>¥18.00</td>' +
                '<td><span class="tag tag-' + status.class + '">' + status.text + '</span></td>' +
                '<td>' + (status.text === '已激活' ? '2026-04-27 10:30' : '-') + '</td>' +
                '<td>2026-05-27</td></tr>';
        }

        document.getElementById('activationsBody').innerHTML = html;
    },

    // ==================== 数据统计 ====================

    renderStatistics: function(container) {
        var self = this;
        container.innerHTML = '<div class="stats-grid">' +
            '<div class="stat-card">' +
            '<div class="stat-icon blue">📝</div>' +
            '<div class="stat-info">' +
            '<div class="stat-label">总题目数</div>' +
            '<div class="stat-value" id="statQuestions">-</div>' +
            '</div></div>' +
            '<div class="stat-card">' +
            '<div class="stat-icon green">👥</div>' +
            '<div class="stat-info">' +
            '<div class="stat-label">注册用户</div>' +
            '<div class="stat-value" id="statUsers">-</div>' +
            '</div></div>' +
            '<div class="stat-card">' +
            '<div class="stat-icon orange">📊</div>' +
            '<div class="stat-info">' +
            '<div class="stat-label">考试次数</div>' +
            '<div class="stat-value" id="statExams">-</div>' +
            '</div></div>' +
            '<div class="stat-card">' +
            '<div class="stat-icon red">💰</div>' +
            '<div class="stat-info">' +
            '<div class="stat-label">总收入</div>' +
            '<div class="stat-value" id="statRevenue">-</div>' +
            '</div></div></div>' +

            '<div class="card">' +
            '<div class="card-header">' +
            '<h3 class="card-title">📈 激活趋势（近7天）</h3>' +
            '</div>' +
            '<div id="chartActivations" style="width:100%;height:320px;"></div></div>' +

            '<div style="display:grid;grid-template-columns:repeat(2,1fr);gap:20px;margin-top:20px;">' +
            '<div class="card">' +
            '<div class="card-header">' +
            '<h3 class="card-title">📊 题目类型分布</h3>' +
            '</div>' +
            '<div id="chartQuestionType" style="width:100%;height:280px;"></div></div>' +

            '<div class="card">' +
            '<div class="card-header">' +
            '<h3 class="card-title">💰 收入统计（近7天）</h3>' +
            '</div>' +
            '<div id="chartRevenue" style="width:100%;height:280px;"></div></div>' +

            '<div class="card">' +
            '<div class="card-header">' +
            '<h3 class="card-title">👥 用户增长趋势</h3>' +
            '</div>' +
            '<div id="chartUsers" style="width:100%;height:280px;"></div></div>' +

            '<div class="card">' +
            '<div class="card-header">' +
            '<h3 class="card-title">📋 考试通过率</h3>' +
            '</div>' +
            '<div id="chartPassRate" style="width:100%;height:280px;"></div></div>' +
            '</div>';

        // 模拟数据
        document.getElementById('statQuestions').textContent = '523';
        document.getElementById('statUsers').textContent = '1,234';
        document.getElementById('statExams').textContent = '5,678';
        document.getElementById('statRevenue').textContent = '¥52,014';

        // 渲染图表
        setTimeout(function() {
            self.renderActivationChart();
            self.renderQuestionTypeChart();
            self.renderRevenueChart();
            self.renderUserChart();
            self.renderPassRateChart();
        }, 100);
    },

    // 激活趋势图表
    renderActivationChart: function() {
        var chart = echarts.init(document.getElementById('chartActivations'));
        var option = {
            tooltip: { trigger: 'axis' },
            legend: { data: ['激活次数', '新增用户'], bottom: 0 },
            grid: { left: '3%', right: '4%', bottom: '15%', top: '10%', containLabel: true },
            xAxis: {
                type: 'category',
                boundaryGap: false,
                data: ['04-21', '04-22', '04-23', '04-24', '04-25', '04-26', '04-27']
            },
            yAxis: { type: 'value' },
            series: [
                {
                    name: '激活次数',
                    type: 'line',
                    smooth: true,
                    areaStyle: { color: 'rgba(102, 126, 234, 0.2)' },
                    lineStyle: { color: '#667eea', width: 3 },
                    itemStyle: { color: '#667eea' },
                    data: [45, 52, 38, 61, 55, 72, 89]
                },
                {
                    name: '新增用户',
                    type: 'line',
                    smooth: true,
                    areaStyle: { color: 'rgba(82, 196, 26, 0.2)' },
                    lineStyle: { color: '#52c41a', width: 3 },
                    itemStyle: { color: '#52c41a' },
                    data: [28, 34, 25, 42, 38, 48, 56]
                }
            ]
        };
        chart.setOption(option);
        window.addEventListener('resize', function() { chart.resize(); });
    },

    // 题目类型分布
    renderQuestionTypeChart: function() {
        var chart = echarts.init(document.getElementById('chartQuestionType'));
        var option = {
            tooltip: { trigger: 'item', formatter: '{b}: {c} ({d}%)' },
            legend: { orient: 'vertical', right: '5%', top: 'center' },
            color: ['#667eea', '#52c41a', '#faad14'],
            series: [{
                type: 'pie',
                radius: ['45%', '70%'],
                center: ['35%', '50%'],
                avoidLabelOverlap: false,
                itemStyle: { borderRadius: 8, borderColor: '#fff', borderWidth: 2 },
                label: { show: false },
                emphasis: { label: { show: true, fontSize: 14, fontWeight: 'bold' } },
                data: [
                    { value: 350, name: '选择题' },
                    { value: 150, name: '判断题' },
                    { value: 23, name: '多选题' }
                ]
            }]
        };
        chart.setOption(option);
        window.addEventListener('resize', function() { chart.resize(); });
    },

    // 收入统计图表
    renderRevenueChart: function() {
        var chart = echarts.init(document.getElementById('chartRevenue'));
        var option = {
            tooltip: { trigger: 'axis', formatter: '{b}<br/>收入: ¥{c}' },
            grid: { left: '3%', right: '4%', bottom: '10%', top: '10%', containLabel: true },
            xAxis: {
                type: 'category',
                data: ['04-21', '04-22', '04-23', '04-24', '04-25', '04-26', '04-27']
            },
            yAxis: { type: 'value', axisLabel: { formatter: '¥{c}' } },
            series: [{
                type: 'bar',
                barWidth: '50%',
                itemStyle: {
                    color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                        { offset: 0, color: '#52c41a' },
                        { offset: 1, color: '#389e0d' }
                    ]),
                    borderRadius: [8, 8, 0, 0]
                },
                data: [360, 450, 288, 540, 468, 648, 792]
            }]
        };
        chart.setOption(option);
        window.addEventListener('resize', function() { chart.resize(); });
    },

    // 用户增长图表
    renderUserChart: function() {
        var chart = echarts.init(document.getElementById('chartUsers'));
        var option = {
            tooltip: { trigger: 'axis' },
            grid: { left: '3%', right: '4%', bottom: '10%', top: '10%', containLabel: true },
            xAxis: {
                type: 'category',
                data: ['04-21', '04-22', '04-23', '04-24', '04-25', '04-26', '04-27']
            },
            yAxis: { type: 'value' },
            series: [{
                type: 'line',
                smooth: true,
                lineStyle: { color: '#faad14', width: 3 },
                areaStyle: { color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                    { offset: 0, color: 'rgba(250, 173, 20, 0.4)' },
                    { offset: 1, color: 'rgba(250, 173, 20, 0.05)' }
                ]) },
                itemStyle: { color: '#faad14' },
                data: [980, 1020, 1050, 1090, 1125, 1168, 1234]
            }]
        };
        chart.setOption(option);
        window.addEventListener('resize', function() { chart.resize(); });
    },

    // 考试通过率图表
    renderPassRateChart: function() {
        var chart = echarts.init(document.getElementById('chartPassRate'));
        var option = {
            tooltip: { trigger: 'axis', axisPointer: { type: 'shadow' } },
            grid: { left: '3%', right: '4%', bottom: '10%', top: '10%', containLabel: true },
            xAxis: {
                type: 'category',
                data: ['04-21', '04-22', '04-23', '04-24', '04-25', '04-26', '04-27']
            },
            yAxis: { type: 'value', axisLabel: { formatter: '{value}%' }, max: 100 },
            series: [{
                type: 'line',
                smooth: true,
                lineStyle: { color: '#667eea', width: 3 },
                areaStyle: { color: new echarts.graphic.LinearGradient(0, 0, 0, 1, [
                    { offset: 0, color: 'rgba(102, 126, 234, 0.4)' },
                    { offset: 1, color: 'rgba(102, 126, 234, 0.05)' }
                ]) },
                itemStyle: { color: '#667eea' },
                markLine: {
                    silent: true,
                    lineStyle: { color: '#ff4d4f', type: 'dashed' },
                    data: [{ yAxis: 90, name: '及格线' }]
                },
                data: [85, 88, 82, 90, 87, 92, 89]
            }]
        };
        chart.setOption(option);
        window.addEventListener('resize', function() { chart.resize(); });
    },

    // ==================== 系统设置 ====================

    renderSettings: function(container) {
        container.innerHTML = '<div class="card">' +
            '<div class="card-header">' +
            '<h3 class="card-title">系统配置</h3>' +
            '</div>' +
            '<form>' +
            '<div class="form-group">' +
            '<label class="form-label">系统名称</label>' +
            '<input type="text" class="form-input" value="摩托车笔试题库系统"></div>' +
            '<div class="form-group">' +
            '<label class="form-label">激活价格（元）</label>' +
            '<input type="number" class="form-input" value="18"></div>' +
            '<div class="form-group">' +
            '<label class="form-label">激活有效期（天）</label>' +
            '<input type="number" class="form-input" value="30"></div>' +
            '<div class="form-group">' +
            '<label class="form-label">单次最低充值（元）</label>' +
            '<input type="number" class="form-input" value="18"></div>' +
            '<div class="form-group">' +
            '<label class="form-label">考试时长（分钟）</label>' +
            '<input type="number" class="form-input" value="45"></div>' +
            '<div class="form-group">' +
            '<label class="form-label">及格分数</label>' +
            '<input type="number" class="form-input" value="90"></div>' +
            '<button type="button" class="btn btn-primary" onclick="Admin.saveSettings()">保存设置</button>' +
            '</form></div>';
    },

    saveSettings: function() {
        this.showToast('保存成功');
    },

    // ==================== 批量导入 ====================

    showImportModal: function() {
        var body = '<div class="upload-area" onclick="document.getElementById(\'importFile\').click()">' +
            '<div class="icon">📁</div>' +
            '<p>点击选择 Excel 文件</p>' +
            '<p style="color:#999;font-size:12px;margin-top:10px;">支持 .xlsx, .xls 格式</p>' +
            '<input type="file" id="importFile" accept=".xlsx,.xls" style="display:none;" onchange="Admin.handleFileSelect(this)">' +
            '</div>' +
            '<div style="margin-top:20px;">' +
            '<h4 style="margin-bottom:10px;">📋 Excel 模板格式</h4>' +
            '<table style="width:100%;border-collapse:collapse;font-size:12px;">' +
            '<tr style="background:#fafafa;"><th style="padding:8px;border:1px solid #ddd;">字段</th><th style="padding:8px;border:1px solid #ddd;">说明</th><th style="padding:8px;border:1px solid #ddd;">示例</th></tr>' +
            '<tr><td style="padding:8px;border:1px solid #ddd;">subject</td><td style="padding:8px;border:1px solid #ddd;">科目</td><td style="padding:8px;border:1px solid #ddd;">1</td></tr>' +
            '<tr><td style="padding:8px;border:1px solid #ddd;">question_type</td><td style="padding:8px;border:1px solid #ddd;">题型</td><td style="padding:8px;border:1px solid #ddd;">1</td></tr>' +
            '<tr><td style="padding:8px;border:1px solid #ddd;">chapter_id</td><td style="padding:8px;border:1px solid #ddd;">章节ID</td><td style="padding:8px;border:1px solid #ddd;">1</td></tr>' +
            '<tr><td style="padding:8px;border:1px solid #ddd;">title</td><td style="padding:8px;border:1px solid #ddd;">题目内容</td><td style="padding:8px;border:1px solid #ddd;">...</td></tr>' +
            '<tr><td style="padding:8px;border:1px solid #ddd;">option_a</td><td style="padding:8px;border:1px solid #ddd;">选项A</td><td style="padding:8px;border:1px solid #ddd;">...</td></tr>' +
            '<tr><td style="padding:8px;border:1px solid #ddd;">answer</td><td style="padding:8px;border:1px solid #ddd;">答案</td><td style="padding:8px;border:1px solid #ddd;">B</td></tr>' +
            '</table></div>';

        var footer = '<button class="btn btn-primary" onclick="Admin.doImport()">开始导入</button>' +
            '<button class="btn" onclick="Admin.closeModal()">取消</button>';

        this.showModal('批量导入题目', body, footer);
    },

    handleFileSelect: function(input) {
        if (input.files.length > 0) {
            var fileName = input.files[0].name;
            var label = input.parentElement.querySelector('p');
            label.textContent = '已选择: ' + fileName;
        }
    },

    doImport: function() {
        var fileInput = document.getElementById('importFile');
        if (!fileInput.files.length) {
            this.showToast('请先选择文件');
            return;
        }

        this.showToast('导入中...');
        setTimeout(function() {
            Admin.closeModal();
            Admin.showToast('导入成功：98 条');
        }, 1500);
    },

    // ==================== 工具方法 ====================

    showModal: function(title, body, footer) {
        document.getElementById('modalTitle').textContent = title;
        document.getElementById('modalBody').innerHTML = body;

        var footerEl = document.createElement('div');
        footerEl.className = 'modal-footer';
        footerEl.innerHTML = footer;
        document.getElementById('modalBody').appendChild(footerEl);

        document.getElementById('modal').classList.add('show');
    },

    closeModal: function() {
        document.getElementById('modal').classList.remove('show');
    },

    showToast: function(text) {
        var toast = document.getElementById('toast');
        document.getElementById('toastText').textContent = text;
        toast.style.display = 'block';

        setTimeout(function() {
            toast.style.display = 'none';
        }, 2000);
    },

    showLoading: function() {
        // 简化处理
    },

    hideLoading: function() {
        // 简化处理
    }
};

// 初始化
document.addEventListener('DOMContentLoaded', function() {
    Admin.init();
});
