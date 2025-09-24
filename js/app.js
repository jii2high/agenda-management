// Main Application untuk Agenda Management System

class AgendaApp {
    constructor() {
        this.currentView = 'login';
        this.agendas = [];
        this.pendingAgendas = [];
        this.formData = {
            judul: '',
            tanggal: '',
            waktu: '',
            tempat: '',
            deskripsi: ''
        };
        this.editMode = false;
        this.editAgendaId = null;
        this.selectedAgenda = null;

        this.initializeApp();
    }

    async initializeApp() {
        try {
            loadingManager.show('Initializing application...');
            
            // Check if user is already logged in
            if (authService.isUserAuthenticated()) {
                this.currentView = 'dashboard';
                await this.loadUserData();
            }
            
            this.render();
            this.setupEventListeners();
            
        } catch (error) {
            console.error('App initialization error:', error);
            notificationManager.show('Error initializing application', 'error');
        } finally {
            loadingManager.hide();
            this.showApp();
        }
    }

    showApp() {
        document.getElementById('loading').classList.add('hidden');
        document.getElementById('app').classList.remove('hidden');
        document.getElementById('app').classList.add('fade-in');
    }

    async loadUserData() {
        const user = authService.getCurrentUser();
        if (!user) return;

        try {
            // Load agendas based on user role
            if (user.role === 'admin') {
                await this.loadAllAgendas();
                await this.loadPendingAgendas();
            } else if (user.role === 'guru') {
                await this.loadUserAgendas(user.id);
            } else {
                await this.loadApprovedAgendas();
            }
        } catch (error) {
            console.error('Error loading user data:', error);
            notificationManager.show('Error loading data', 'error');
        }
    }

    async loadAllAgendas() {
        try {
            const response = await apiService.getAgendas();
            if (response.success) {
                this.agendas = response.data || [];
                dataManager.setData('agendas', this.agendas);
            }
        } catch (error) {
            console.error('Error loading agendas:', error);
            // Use cached data if available
            this.agendas = dataManager.getData('agendas') || [];
        }
    }

    async loadApprovedAgendas() {
        try {
            const response = await apiService.getApprovedAgendas();
            if (response.success) {
                this.agendas = response.data || [];
                dataManager.setData('approved-agendas', this.agendas);
            }
        } catch (error) {
            console.error('Error loading approved agendas:', error);
            this.agendas = dataManager.getData('approved-agendas') || [];
        }
    }

    async loadUserAgendas(userId) {
        try {
            const response = await apiService.getUserAgendas(userId);
            if (response.success) {
                this.agendas = response.data || [];
                dataManager.setData(`user-agendas-${userId}`, this.agendas);
            }
        } catch (error) {
            console.error('Error loading user agendas:', error);
            this.agendas = dataManager.getData(`user-agendas-${userId}`) || [];
        }
    }

    async loadPendingAgendas() {
        try {
            const response = await apiService.getPendingAgendas();
            if (response.success) {
                this.pendingAgendas = response.data || [];
                dataManager.setData('pending-agendas', this.pendingAgendas);
            }
        } catch (error) {
            console.error('Error loading pending agendas:', error);
            this.pendingAgendas = dataManager.getData('pending-agendas') || [];
        }
    }

    setupEventListeners() {
        // Auth state changes
        authService.onAuthChange((event, user) => {
            if (event === 'login') {
                this.currentView = 'dashboard';
                this.loadUserData().then(() => this.render());
            } else if (event === 'logout') {
                this.currentView = 'login';
                this.agendas = [];
                this.pendingAgendas = [];
                this.render();
            }
        });

        // Network status changes
        networkHandler.onStatusChange((isOnline) => {
            if (isOnline) {
                notificationManager.show('Koneksi internet tersambung kembali', 'success');
                // Reload data if needed
                if (authService.isUserAuthenticated()) {
                    this.loadUserData();
                }
            } else {
                notificationManager.show('Koneksi internet terputus', 'warning');
            }
        });

        // Keyboard shortcuts
        document.addEventListener('keydown', (e) => {
            if (authService.isUserAuthenticated() && e.ctrlKey) {
                switch (e.key) {
                    case 'n':
                        e.preventDefault();
                        if (authService.hasPermission('create_agenda')) {
                            this.showForm();
                        }
                        break;
                    case 'h':
                        e.preventDefault();
                        this.showDashboard();
                        break;
                }
            }
        });
    }

    render() {
        this.renderHeader();
        this.renderMainContent();
    }

    renderHeader() {
        const headerActions = document.getElementById('header-actions');
        const user = authService.getCurrentUser();

        if (this.currentView === 'login') {
            headerActions.innerHTML = `
                <div class="w-10 h-10 rounded bg-gray-600 flex items-center justify-center">
                    ${IconHelper.getIcon('user', 'w-6 h-6 text-white')}
                </div>
            `;
            return;
        }

        let buttons = '';

        // Home button
        buttons += `
            <button onclick="app.showDashboard()" 
                    class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors flex items-center gap-2">
                ${IconHelper.getIcon('home', 'w-4 h-4')}
                Home
            </button>
        `;

        // Create/Submit button
        if (authService.hasPermission('create_agenda')) {
            const buttonText = authService.isGuru() ? 'Ajukan' : 'Tambah';
            buttons += `
                <button onclick="app.showForm()" 
                        class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors flex items-center gap-2">
                    ${IconHelper.getIcon('plus', 'w-4 h-4')}
                    ${buttonText}
                </button>
            `;
        }

        // Pending button (admin only)
        if (authService.isAdmin()) {
            const pendingCount = this.pendingAgendas.length;
            buttons += `
                <button onclick="app.showPending()" 
                        class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors relative">
                    Pending
                    ${pendingCount > 0 ? `
                        <span class="absolute -top-1 -right-1 bg-red-500 text-white text-xs rounded-full w-5 h-5 flex items-center justify-center">
                            ${pendingCount}
                        </span>
                    ` : ''}
                </button>
            `;
        }

        // Logout button
        buttons += `
            <button onclick="app.logout()" 
                    class="px-4 py-2 bg-gray-700 hover:bg-gray-600 rounded-lg transition-colors flex items-center gap-2">
                ${IconHelper.getIcon('logout', 'w-4 h-4')}
                Logout
            </button>
        `;

        // User avatar
        const userInfo = authService.getUserDisplayInfo();
        const avatar = `
            <div class="relative group">
                <div class="w-10 h-10 rounded bg-gray-600 flex items-center justify-center cursor-pointer">
                    <span class="text-white font-semibold text-sm">${userInfo ? userInfo.initials : 'U'}</span>
                </div>
                <div class="absolute right-0 top-12 bg-slate-800 border border-slate-600 rounded-lg p-4 min-w-48 opacity-0 invisible group-hover:opacity-100 group-hover:visible transition-all duration-200 z-10">
                    <div class="text-white font-semibold">${userInfo ? userInfo.name : ''}</div>
                    <div class="text-gray-400 text-sm">${userInfo ? userInfo.email : ''}</div>
                    <div class="text-blue-400 text-sm mt-1">${userInfo ? userInfo.roleDisplay : ''}</div>
                </div>
            </div>
        `;

        headerActions.innerHTML = buttons + avatar;
    }

    renderMainContent() {
        const mainContent = document.getElementById('main-content');

        switch (this.currentView) {
            case 'login':
                mainContent.innerHTML = this.renderLoginPage();
                break;
            case 'dashboard':
                mainContent.innerHTML = this.renderDashboardPage();
                break;
            case 'form':
                mainContent.innerHTML = this.renderFormPage();
                break;
            case 'pending':
                mainContent.innerHTML = this.renderPendingPage();
                break;
            default:
                mainContent.innerHTML = '<div class="text-center text-white p-8">Page not found</div>';
        }

        // Add event listeners after rendering
        this.attachEventListeners();
    }

    renderLoginPage() {
        return `
            <div class="flex-1 flex items-center justify-center p-6">
                <div class="glass rounded-2xl p-8 w-full max-w-md shadow-2xl">
                    <h2 class="text-3xl font-bold text-white text-center mb-8">Login</h2>
                    
                    <form id="login-form" class="space-y-6">
                        <div>
                            <label class="form-label">Email</label>
                            <input
                                type="email"
                                name="email"
                                placeholder="example@smkn1kotabekasi.sch.id"
                                class="form-input focus-ring"
                                required
                            />
                        </div>
                        
                        <div>
                            <label class="form-label">Password</label>
                            <input
                                type="password"
                                name="password"
                                placeholder="••••••"
                                class="form-input focus-ring"
                                required
                            />
                        </div>
                        
                        <button
                            type="submit"
                            class="w-full btn-primary focus-ring"
                        >
                            <span class="login-text">Login</span>
                            <span class="login-spinner hidden">
                                ${IconHelper.getIcon('spinner', 'w-5 h-5 mr-2')}
                                Logging in...
                            </span>
                        </button>
                    </form>
                    
                    <div class="mt-6 text-sm text-gray-400">
                        <p class="mb-2 font-semibold">Demo accounts:</p>
                        <div class="space-y-1 text-xs">
                            <p>• <strong>Admin:</strong> admin@smkn1kotabekasi.admin.sch.id</p>
                            <p>• <strong>Guru:</strong> guru1@smkn1kotabekasi.guru.sch.id</p>
                            <p>• <strong>Siswa:</strong> siswa1@smkn1kotabekasi.sch.id</p>
                            <p class="mt-2 text-yellow-400">Password: <strong>password</strong> (untuk semua)</p>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    renderDashboardPage() {
        const user = authService.getCurrentUser();
        const todayAgendas = this.agendas.filter(agenda => DateTimeHelper.isToday(agenda.tanggal));
        const upcomingAgendas = this.agendas.filter(agenda => !DateTimeHelper.isToday(agenda.tanggal));

        return `
            <div class="flex-1 p-6">
                <div class="container-custom">
                    <!-- Welcome Section -->
                    <div class="text-center mb-8">
                        <h1 class="text-4xl font-bold text-gray-300 mb-2">WELCOME</h1>
                        <h2 class="text-4xl font-bold text-white mb-4">
                            <span class="text-gradient">${user ? user.nama.toUpperCase() : ''}</span>
                        </h2>
                        <h3 class="text-3xl font-bold text-white mb-4">
                            AGENDA <span class="text-blue-400">MANAGEMENT</span> SYSTEM
                        </h3>
                        
                        <!-- Stats -->
                        <div class="flex justify-center gap-4 text-sm text-gray-400 mb-4 mobile-stack">
                            <span class="glass px-3 py-1 rounded-full">
                                Role: ${authService.getUserDisplayInfo()?.roleDisplay || ''}
                            </span>
                            <span class="glass px-3 py-1 rounded-full">
                                Total Agenda: ${this.agendas.length}
                            </span>
                            ${user?.role === 'admin' && this.pendingAgendas.length > 0 ? `
                                <span class="bg-yellow-600/20 text-yellow-400 px-3 py-1 rounded-full">
                                    Pending: ${this.pendingAgendas.length}
                                </span>
                            ` : ''}
                        </div>
                        
                        <p class="text-gray-400 max-w-3xl mx-auto">
                            Platform agenda sekolah yang memudahkan guru dan admin mengelola kegiatan,
                            sementara siswa dapat melihat dan mengikuti agenda dengan cepat dan praktis.
                        </p>
                    </div>

                    ${this.renderAgendasSection(todayAgendas, upcomingAgendas)}
                </div>
            </div>
        `;
    }

    renderAgendasSection(todayAgendas, upcomingAgendas) {
        if (this.agendas.length === 0) {
            return `
                <div class="text-center py-12">
                    <div class="text-gray-400 mb-6">
                        ${IconHelper.getIcon('calendar', 'w-16 h-16 mx-auto mb-4 opacity-50')}
                        <p class="text-lg">Belum ada agenda yang tersedia</p>
                    </div>
                    ${authService.hasPermission('create_agenda') ? `
                        <button onclick="app.showForm()" class="btn-primary">
                            ${IconHelper.getIcon('plus', 'w-5 h-5 mr-2')}
                            Buat Agenda Pertama
                        </button>
                    ` : ''}
                </div>
            `;
        }

        let html = '';

        // Today's Agendas
        if (todayAgendas.length > 0) {
            html += `
                <div class="mb-8">
                    <h3 class="text-2xl font-bold text-white mb-6 flex items-center gap-2">
                        ${IconHelper.getIcon('calendar', 'w-6 h-6')}
                        Agenda Hari Ini
                    </h3>
                    <div class="grid md:grid-cols-2 gap-6">
                        ${todayAgendas.map(agenda => this.renderAgendaCard(agenda, true)).join('')}
                    </div>
                </div>
            `;
        }

        // Upcoming Agendas
        if (upcomingAgendas.length > 0) {
            html += `
                <div>
                    <h3 class="text-2xl font-bold text-white mb-6 flex items-center gap-2">
                        ${IconHelper.getIcon('clock', 'w-6 h-6')}
                        ${todayAgendas.length > 0 ? 'Agenda Mendatang' : 'Semua Agenda'}
                    </h3>
                    <div class="flex gap-6 overflow-x-auto pb-4 custom-scroll">
                        ${upcomingAgendas.map(agenda => this.renderAgendaCard(agenda, false)).join('')}
                    </div>
                </div>
            `;
        }

        return html;
    }

    renderAgendaCard(agenda, isToday = false) {
        const statusBadge = this.getStatusBadge(agenda.status);
        const isEditable = authService.hasPermission('edit_agenda') && 
                          (authService.isAdmin() || agenda.created_by === authService.getUserId());

        return `
            <div class="card hover-lift cursor-pointer ${isToday ? 'w-full' : 'min-w-80 flex-shrink-0'}" 
                 onclick="app.showAgendaDetail(${agenda.id})">
                <div class="p-6">
                    <div class="flex justify-between items-start mb-3">
                        <h4 class="text-xl font-bold text-white pr-2">${agenda.judul}</h4>
                        <div class="flex flex-col gap-1 flex-shrink-0">
                            ${isToday ? `
                                <span class="badge badge-today text-center">
                                    ${agenda.waktu}
                                </span>
                            ` : `
                                <span class="badge text-center">
                                    ${DateTimeHelper.formatDate(agenda.tanggal, { day: '2-digit', month: 'short' })}
                                </span>
                            `}
                            ${statusBadge}
                        </div>
                    </div>
                    
                    <p class="text-gray-300 text-sm mb-3 line-clamp-2">${agenda.deskripsi || 'Tidak ada deskripsi'}</p>
                    
                    <div class="space-y-1 text-sm">
                        <div class="flex items-center gap-2 text-gray-400">
                            ${IconHelper.getIcon('clock', 'w-4 h-4')}
                            <span>${DateTimeHelper.formatTime(agenda.waktu)}</span>
                        </div>
                        <div class="flex items-center gap-2 text-gray-400">
                            ${IconHelper.getIcon('location', 'w-4 h-4')}
                            <span class="truncate">${agenda.tempat}</span>
                        </div>
                    </div>
                </div>
            </div>
        `;
    }

    renderFormPage() {
        const user = authService.getCurrentUser();
        const title = this.editMode ? 'EDIT AGENDA' : 
                     (user?.role === 'guru' ? 'AJUKAN AGENDA' : 'TAMBAH AGENDA');

        return `
            <div class="flex-1 flex items-center justify-center p-6">
                <div class="glass rounded-2xl p-8 w-full max-w-2xl shadow-2xl">
                    <h2 class="text-2xl font-bold text-white mb-8">${title}</h2>
                    
                    ${user?.role === 'guru' && !this.editMode ? `
                        <div class="bg-yellow-900/50 border border-yellow-600 rounded-lg p-4 mb-6">
                            <p class="text-yellow-200 text-sm">
                                ${IconHelper.getIcon('alertCircle', 'w-4 h-4 inline mr-2')}
                                ${CONFIG.MESSAGES.INFO.GURU_SUBMIT_INFO || 'Agenda akan menunggu persetujuan admin'}
                            </p>
                        </div>
                    ` : ''}
                    
                    <form id="agenda-form" class="space-y-6">
                        <div>
                            <label class="form-label">Judul *</label>
                            <input
                                type="text"
                                name="judul"
                                value="${this.formData.judul}"
                                class="form-input focus-ring"
                                placeholder="Masukkan judul agenda"
                                required
                            />
                        </div>
                        
                        <div class="grid md:grid-cols-2 gap-6">
                            <div>
                                <label class="form-label">Tanggal *</label>
                                <input
                                    type="date"
                                    name="tanggal"
                                    value="${this.formData.tanggal}"
                                    class="form-input focus-ring"
                                    required
                                />
                            </div>
                            
                            <div>
                                <label class="form-label">Waktu *</label>
                                <input
                                    type="time"
                                    name="waktu"
                                    value="${this.formData.waktu}"
                                    class="form-input focus-ring"
                                    required
                                />
                            </div>
                        </div>
                        
                        <div>
                            <label class="form-label">Tempat *</label>
                            <input
                                type="text"
                                name="tempat"
                                value="${this.formData.tempat}"
                                class="form-input focus-ring"
                                placeholder="Masukkan lokasi agenda"
                                required
                            />
                        </div>
                        
                        <div>
                            <label class="form-label">Deskripsi</label>
                            <textarea
                                name="deskripsi"
                                rows="4"
                                class="form-input focus-ring resize-none"
                                placeholder="Masukkan deskripsi agenda (opsional)"
                            >${this.formData.deskripsi}</textarea>
                        </div>
                        
                        <div class="flex gap-4 justify-end mobile-stack">
                            <button type="button" onclick="app.showDashboard()" class="btn-secondary mobile-full">
                                Batal
                            </button>
                            <button type="submit" class="btn-primary mobile-full">
                                <span class="submit-text">
                                    ${this.editMode ? 'Update' : (user?.role === 'guru' ? 'Ajukan' : 'Tambah')}
                                </span>
                                <span class="submit-spinner hidden">
                                    ${IconHelper.getIcon('spinner', 'w-5 h-5 mr-2')}
                                    Menyimpan...
                                </span>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        `;
    }

    renderPendingPage() {
        return `
            <div class="flex-1 p-6">
                <div class="glass rounded-2xl p-8 max-w-6xl mx-auto shadow-2xl">
                    <h2 class="text-2xl font-bold text-white mb-8">
                        AGENDA PENDING
                        ${this.pendingAgendas.length > 0 ? `
                            <span class="ml-2 text-lg text-gray-400">(${this.pendingAgendas.length})</span>
                        ` : ''}
                    </h2>
                    
                    ${this.pendingAgendas.length === 0 ? `
                        <div class="text-center py-8 text-gray-400">
                            ${IconHelper.getIcon('calendar', 'w-16 h-16 mx-auto mb-4 opacity-50')}
                            <p>Tidak ada agenda yang perlu disetujui</p>
                        </div>
                    ` : `
                        <div class="space-y-4 max-h-96 overflow-y-auto custom-scroll">
                            ${this.pendingAgendas.map(agenda => this.renderPendingAgendaCard(agenda)).join('')}
                        </div>
                    `}
                </div>
            </div>
        `;
    }

    renderPendingAgendaCard(agenda) {
        return `
            <div class="glass rounded-xl p-6 hover-lift">
                <div class="flex gap-4">
                    <div class="flex-1">
                        <div class="flex justify-between items-start mb-3">
                            <h3 class="text-xl font-bold text-white">${agenda.judul}</h3>
                            <span class="badge badge-pending">Pending</span>
                        </div>
                        
                        <p class="text-gray-300 text-sm mb-3">${agenda.deskripsi || 'Tidak ada deskripsi'}</p>
                        
                        <div class="grid md:grid-cols-3 gap-4 text-sm">
                            <div class="flex items-center gap-2 text-gray-300">
                                ${IconHelper.getIcon('calendar', 'w-4 h-4')}
                                <span>${DateTimeHelper.formatDate(agenda.tanggal)}</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-300">
                                ${IconHelper.getIcon('clock', 'w-4 h-4')}
                                <span>${DateTimeHelper.formatTime(agenda.waktu)}</span>
                            </div>
                            <div class="flex items-center gap-2 text-gray-300">
                                ${IconHelper.getIcon('location', 'w-4 h-4')}
                                <span class="truncate">${agenda.tempat}</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="flex flex-col gap-2 mobile-stack">
                        <button 
                            onclick="app.approveAgenda(${agenda.id})"
                            class="btn-success flex items-center gap-2"
                        >
                            ${IconHelper.getIcon('checkCircle', 'w-4 h-4')}
                            Approve
                        </button>
                        <button 
                            onclick="app.rejectAgenda(${agenda.id})"
                            class="btn-danger flex items-center gap-2"
                        >
                            ${IconHelper.getIcon('xCircle', 'w-4 h-4')}
                            Reject
                        </button>
                        <button 
                            onclick="app.showAgendaDetail(${agenda.id})"
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded transition-colors text-sm"
                        >
                            Detail
                        </button>
                    </div>
                </div>
            </div>
        `;
    }

    attachEventListeners() {
        // Login form
        const loginForm = document.getElementById('login-form');
        if (loginForm) {
            loginForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleLogin(e);
            });
        }

        // Agenda form
        const agendaForm = document.getElementById('agenda-form');
        if (agendaForm) {
            agendaForm.addEventListener('submit', (e) => {
                e.preventDefault();
                this.handleAgendaSubmit(e);
            });
        }
    }

    async handleLogin(e) {
        const form = e.target;
        const formData = new FormData(form);
        const email = formData.get('email');
        const password = formData.get('password');

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const loginText = submitBtn.querySelector('.login-text');
        const loginSpinner = submitBtn.querySelector('.login-spinner');
        
        loginText.classList.add('hidden');
        loginSpinner.classList.remove('hidden');
        submitBtn.disabled = true;

        try {
            const result = await authService.login(email, password);
            
            if (result.success) {
                this.showNotification(result.message, 'success');
                // AuthService will handle the view change
            } else {
                this.showNotification(result.message, 'error');
            }
        } catch (error) {
            this.showNotification('Login gagal. Silakan coba lagi.', 'error');
        } finally {
            // Reset loading state
            loginText.classList.remove('hidden');
            loginSpinner.classList.add('hidden');
            submitBtn.disabled = false;
        }
    }

    async handleAgendaSubmit(e) {
        const form = e.target;
        const validator = new FormValidator(form);
        
        // Add validation rules
        validator
            .addRule('judul', { required: true, maxLength: CONFIG?.VALIDATION?.AGENDA?.TITLE_MAX_LENGTH || 100 })
            .addRule('tanggal', { required: true })
            .addRule('waktu', { required: true })
            .addRule('tempat', { required: true, maxLength: CONFIG?.VALIDATION?.AGENDA?.LOCATION_MAX_LENGTH || 100 })
            .addRule('deskripsi', { maxLength: CONFIG?.VALIDATION?.AGENDA?.DESCRIPTION_MAX_LENGTH || 500 });

        const validation = validator.validate();
        
        if (!validation.isValid) {
            this.showNotification('Silakan perbaiki kesalahan pada form', 'error');
            return;
        }

        // Show loading state
        const submitBtn = form.querySelector('button[type="submit"]');
        const submitText = submitBtn.querySelector('.submit-text');
        const submitSpinner = submitBtn.querySelector('.submit-spinner');
        
        submitText.classList.add('hidden');
        submitSpinner.classList.remove('hidden');
        submitBtn.disabled = true;

        try {
            const agendaData = {
                ...validation.data,
                created_by: authService.getUserId(),
                status: authService.isAdmin() ? 'approved' : 'pending'
            };

            let response;
            if (this.editMode) {
                response = await apiService.updateAgenda(this.editAgendaId, agendaData);
            } else {
                response = await apiService.createAgenda(agendaData);
            }

            if (response.success) {
                const message = authService.isGuru() ? 
                    (this.editMode ? 'Agenda berhasil diperbarui dan menunggu approval!' : 'Agenda berhasil diajukan!') : 
                    (this.editMode ? 'Agenda berhasil diperbarui!' : 'Agenda berhasil ditambahkan!');
                
                this.showNotification(message, 'success');
                this.resetForm();
                this.showDashboard();
                await this.loadUserData();
            } else {
                this.showNotification('Gagal menyimpan agenda. Silakan coba lagi.', 'error');
            }
        } catch (error) {
            console.error('Error submitting agenda:', error);
            this.showNotification('Terjadi kesalahan. Silakan coba lagi.', 'error');
        } finally {
            // Reset loading state
            submitText.classList.remove('hidden');
            submitSpinner.classList.add('hidden');
            submitBtn.disabled = false;
        }
    }

    async approveAgenda(agendaId) {
        modalManager.confirm(
            'Setujui Agenda',
            'Apakah Anda yakin ingin menyetujui agenda ini?',
            async () => {
                try {
                    loadingManager.show('Menyetujui agenda...');
                    const response = await apiService.approveAgenda(agendaId, authService.getUserId());
                    
                    if (response.success) {
                        this.showNotification('Agenda berhasil disetujui!', 'success');
                        await this.loadUserData();
                        this.render();
                    } else {
                        this.showNotification('Gagal menyetujui agenda', 'error');
                    }
                } catch (error) {
                    console.error('Error approving agenda:', error);
                    this.showNotification('Terjadi kesalahan saat menyetujui agenda', 'error');
                } finally {
                    loadingManager.hide();
                }
            }
        );
    }

    async rejectAgenda(agendaId) {
        modalManager.confirm(
            'Tolak Agenda',
            'Apakah Anda yakin ingin menolak agenda ini?',
            async () => {
                try {
                    loadingManager.show('Menolak agenda...');
                    const response = await apiService.rejectAgenda(agendaId, authService.getUserId());
                    
                    if (response.success) {
                        this.showNotification('Agenda berhasil ditolak!', 'success');
                        await this.loadUserData();
                        this.render();
                    } else {
                        this.showNotification('Gagal menolak agenda', 'error');
                    }
                } catch (error) {
                    console.error('Error rejecting agenda:', error);
                    this.showNotification('Terjadi kesalahan saat menolak agenda', 'error');
                } finally {
                    loadingManager.hide();
                }
            }
        );
    }

    showAgendaDetail(agendaId) {
        const agenda = [...this.agendas, ...this.pendingAgendas].find(a => a.id === agendaId);
        if (!agenda) return;

        const isEditable = authService.hasPermission('edit_agenda') && 
                          (authService.isAdmin() || agenda.created_by === authService.getUserId()) &&
                          (agenda.status !== 'approved' || authService.isAdmin());

        const content = `
            <div>
                <div class="flex justify-between items-center mb-6">
                    <h3 class="text-2xl font-bold text-white">${agenda.judul}</h3>
                    <button onclick="modalManager.hide()" class="text-gray-400 hover:text-white text-2xl">
                        ${IconHelper.getIcon('x', 'w-6 h-6')}
                    </button>
                </div>
                
                <div class="space-y-4">
                    <p class="text-gray-300 text-sm leading-relaxed">
                        ${agenda.deskripsi || 'Tidak ada deskripsi'}
                    </p>
                    
                    <div class="grid gap-3">
                        <div class="flex items-center gap-2 text-white">
                            ${IconHelper.getIcon('calendar', 'w-4 h-4')}
                            <span class="font-semibold">Tanggal:</span>
                            <span>${DateTimeHelper.formatDate(agenda.tanggal, { 
                                weekday: 'long',
                                year: 'numeric',
                                month: 'long',
                                day: 'numeric'
                            })}</span>
                        </div>
                        <div class="flex items-center gap-2 text-white">
                            ${IconHelper.getIcon('clock', 'w-4 h-4')}
                            <span class="font-semibold">Waktu:</span>
                            <span>${DateTimeHelper.formatTime(agenda.waktu)}</span>
                        </div>
                        <div class="flex items-center gap-2 text-white">
                            ${IconHelper.getIcon('location', 'w-4 h-4')}
                            <span class="font-semibold">Tempat:</span>
                            <span>${agenda.tempat}</span>
                        </div>
                        ${agenda.status ? `
                            <div class="flex items-center gap-2 text-white">
                                <span class="font-semibold">Status:</span>
                                ${this.getStatusBadge(agenda.status)}
                            </div>
                        ` : ''}
                    </div>
                </div>
                
                ${isEditable ? `
                    <div class="flex justify-end mt-6 gap-2">
                        <button 
                            onclick="app.editAgenda(${agenda.id}); modalManager.hide();" 
                            class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg transition-colors flex items-center gap-2"
                        >
                            ${IconHelper.getIcon('edit', 'w-4 h-4')}
                            Edit
                        </button>
                    </div>
                ` : ''}
            </div>
        `;

        modalManager.show(content);
    }

    editAgenda(agendaId) {
        const agenda = [...this.agendas, ...this.pendingAgendas].find(a => a.id === agendaId);
        if (!agenda) return;

        this.formData = {
            judul: agenda.judul,
            tanggal: agenda.tanggal,
            waktu: agenda.waktu,
            tempat: agenda.tempat,
            deskripsi: agenda.deskripsi || ''
        };
        
        this.editMode = true;
        this.editAgendaId = agenda.id;
        this.showForm();
    }

    resetForm() {
        this.formData = {
            judul: '',
            tanggal: '',
            waktu: '',
            tempat: '',
            deskripsi: ''
        };
        this.editMode = false;
        this.editAgendaId = null;
    }

    getStatusBadge(status) {
        const badges = {
            'approved': '<span class="badge badge-approved">Disetujui</span>',
            'pending': '<span class="badge badge-pending">Menunggu Persetujuan</span>',
            'rejected': '<span class="badge badge-rejected">Ditolak</span>'
        };
        return badges[status] || '';
    }

    // Navigation methods
    showDashboard() {
        this.currentView = 'dashboard';
        this.resetForm();
        this.render();
    }

    showForm() {
        this.currentView = 'form';
        this.render();
    }

    showPending() {
        if (authService.isAdmin()) {
            this.currentView = 'pending';
            this.render();
        }
    }

    showLogin() {
        this.currentView = 'login';
        this.render();
    }

    logout() {
        modalManager.confirm(
            'Logout',
            'Apakah Anda yakin ingin logout?',
            () => {
                const result = authService.logout();
                this.showNotification(result.message, 'success');
            }
        );
    }

    showNotification(message, type = 'success') {
        notificationManager.show(message, type);
    }
}

// Error handling
window.addEventListener('error', (event) => {
    console.error('Global error:', event.error);
    if (typeof app !== 'undefined') {
        app.showNotification('Terjadi kesalahan pada aplikasi', 'error');
    }
});

window.addEventListener('unhandledrejection', (event) => {
    console.error('Unhandled promise rejection:', event.reason);
    if (typeof app !== 'undefined') {
        app.showNotification('Terjadi kesalahan pada aplikasi', 'error');
    }
});

// Initialize app when DOM is loaded
document.addEventListener('DOMContentLoaded', () => {
    window.app = new AgendaApp();
});

// Export untuk penggunaan global
if (typeof window !== 'undefined') {
    window.AgendaApp = AgendaApp;
}