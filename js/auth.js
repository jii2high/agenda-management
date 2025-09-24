// Authentication Service untuk Agenda Management System

class AuthService {
    constructor() {
        this.currentUser = null;
        this.isAuthenticated = false;
        this.listeners = [];
        
        // Load user dari localStorage saat inisialisasi
        this.loadUserFromStorage();
    }

    // Login user
    async login(email, password) {
        try {
            // Validate input
            const validation = this.validateLoginInput(email, password);
            if (!validation.isValid) {
                throw new Error(validation.message);
            }

            // Call API
            const response = await apiService.login(email, password);
            
            if (response.success && response.user) {
                this.setCurrentUser(response.user);
                this.saveUserToStorage(response.user);
                this.notifyListeners('login', response.user);
                
                return {
                    success: true,
                    user: response.user,
                    message: CONFIG.MESSAGES.SUCCESS.LOGIN
                };
            } else {
                throw new Error(response.message || CONFIG.MESSAGES.ERROR.LOGIN_FAILED);
            }
        } catch (error) {
            console.error('Login error:', error);
            return {
                success: false,
                message: error.message || CONFIG.MESSAGES.ERROR.LOGIN_FAILED
            };
        }
    }

    // Logout user
    logout() {
        this.setCurrentUser(null);
        this.removeUserFromStorage();
        this.notifyListeners('logout', null);
        
        // Clear cache
        dataManager.clearCache();
        
        return {
            success: true,
            message: CONFIG.MESSAGES.SUCCESS.LOGOUT
        };
    }

    // Validate login input
    validateLoginInput(email, password) {
        const errors = [];

        // Email validation
        const emailErrors = ConfigUtils.validateField('Email', email, {
            required: true,
            isEmail: true
        });
        errors.push(...emailErrors);

        // Password validation
        const passwordErrors = ConfigUtils.validateField('Password', password, {
            required: true,
            minLength: CONFIG.VALIDATION.PASSWORD.MIN_LENGTH
        });
        errors.push(...passwordErrors);

        // Domain validation
        if (email && !ConfigUtils.isValidEmailDomain(email)) {
            errors.push('Domain email tidak valid. Gunakan email sekolah yang benar.');
        }

        return {
            isValid: errors.length === 0,
            errors: errors,
            message: errors.length > 0 ? errors[0] : null
        };
    }

    // Set current user
    setCurrentUser(user) {
        this.currentUser = user;
        this.isAuthenticated = user !== null;
    }

    // Get current user
    getCurrentUser() {
        return this.currentUser;
    }

    // Check if user is authenticated
    isUserAuthenticated() {
        return this.isAuthenticated;
    }

    // Get user role
    getUserRole() {
        return this.currentUser ? this.currentUser.role : null;
    }

    // Get user ID
    getUserId() {
        return this.currentUser ? this.currentUser.id : null;
    }

    // Get user name
    getUserName() {
        return this.currentUser ? this.currentUser.nama : '';
    }

    // Check if user has role
    hasRole(role) {
        return this.getUserRole() === role;
    }

    // Check if user is admin
    isAdmin() {
        return this.hasRole(CONFIG.ROLES.ADMIN);
    }

    // Check if user is guru
    isGuru() {
        return this.hasRole(CONFIG.ROLES.GURU);
    }

    // Check if user is siswa
    isSiswa() {
        return this.hasRole(CONFIG.ROLES.SISWA);
    }

    // Check permission for action
    hasPermission(action) {
        const role = this.getUserRole();
        
        switch (action) {
            case 'create_agenda':
                return role === CONFIG.ROLES.ADMIN || role === CONFIG.ROLES.GURU;
            
            case 'edit_agenda':
                return role === CONFIG.ROLES.ADMIN || role === CONFIG.ROLES.GURU;
            
            case 'delete_agenda':
                return role === CONFIG.ROLES.ADMIN;
            
            case 'approve_agenda':
            case 'reject_agenda':
                return role === CONFIG.ROLES.ADMIN;
            
            case 'view_pending':
                return role === CONFIG.ROLES.ADMIN;
            
            case 'view_all_agendas':
                return role === CONFIG.ROLES.ADMIN;
            
            case 'create_user':
                return role === CONFIG.ROLES.ADMIN;
            
            default:
                return false;
        }
    }

    // Save user to localStorage
    saveUserToStorage(user) {
        try {
            localStorage.setItem('agenda_user', JSON.stringify(user));
            localStorage.setItem('agenda_login_time', Date.now().toString());
        } catch (error) {
            console.error('Error saving user to storage:', error);
        }
    }

    // Load user from localStorage
    loadUserFromStorage() {
        try {
            const userStr = localStorage.getItem('agenda_user');
            const loginTime = localStorage.getItem('agenda_login_time');
            
            if (userStr && loginTime) {
                const user = JSON.parse(userStr);
                const loginTimestamp = parseInt(loginTime);
                
                // Check if login is still valid (24 hours)
                const maxAge = 24 * 60 * 60 * 1000; // 24 hours
                if (Date.now() - loginTimestamp < maxAge) {
                    this.setCurrentUser(user);
                    return user;
                } else {
                    // Login expired
                    this.removeUserFromStorage();
                }
            }
        } catch (error) {
            console.error('Error loading user from storage:', error);
            this.removeUserFromStorage();
        }
        return null;
    }

    // Remove user from localStorage
    removeUserFromStorage() {
        try {
            localStorage.removeItem('agenda_user');
            localStorage.removeItem('agenda_login_time');
        } catch (error) {
            console.error('Error removing user from storage:', error);
        }
    }

    // Subscribe to auth changes
    onAuthChange(callback) {
        this.listeners.push(callback);
        
        // Return unsubscribe function
        return () => {
            const index = this.listeners.indexOf(callback);
            if (index > -1) {
                this.listeners.splice(index, 1);
            }
        };
    }

    // Notify listeners of auth changes
    notifyListeners(event, user) {
        this.listeners.forEach(callback => {
            try {
                callback(event, user);
            } catch (error) {
                console.error('Error in auth listener:', error);
            }
        });
    }

    // Get user initials for avatar
    getUserInitials() {
        const name = this.getUserName();
        if (!name) return 'U';
        
        const parts = name.split(' ');
        if (parts.length === 1) {
            return parts[0].charAt(0).toUpperCase();
        }
        
        return (parts[0].charAt(0) + parts[parts.length - 1].charAt(0)).toUpperCase();
    }

    // Get user display info
    getUserDisplayInfo() {
        const user = this.getCurrentUser();
        if (!user) return null;

        return {
            name: user.nama,
            email: user.email,
            role: user.role,
            roleDisplay: this.getRoleDisplay(user.role),
            initials: this.getUserInitials(),
            id: user.id
        };
    }

    // Get role display name
    getRoleDisplay(role) {
        switch (role) {
            case CONFIG.ROLES.ADMIN:
                return 'Administrator';
            case CONFIG.ROLES.GURU:
                return 'Guru';
            case CONFIG.ROLES.SISWA:
                return 'Siswa';
            default:
                return role;
        }
    }

    // Session management
    extendSession() {
        if (this.isAuthenticated) {
            localStorage.setItem('agenda_login_time', Date.now().toString());
        }
    }

    getSessionTimeRemaining() {
        const loginTime = localStorage.getItem('agenda_login_time');
        if (!loginTime) return 0;

        const loginTimestamp = parseInt(loginTime);
        const maxAge = 24 * 60 * 60 * 1000; // 24 hours
        const elapsed = Date.now() - loginTimestamp;
        
        return Math.max(0, maxAge - elapsed);
    }

    isSessionExpired() {
        return this.getSessionTimeRemaining() === 0;
    }

    // Auto logout when session expires
    startSessionMonitor() {
        setInterval(() => {
            if (this.isAuthenticated && this.isSessionExpired()) {
                console.log('Session expired, auto logout');
                this.logout();
                // Redirect ke login atau show notification
                if (typeof app !== 'undefined') {
                    app.showNotification('Sesi telah berakhir, silakan login kembali.', 'info');
                    app.showLogin();
                }
            }
        }, 60000); // Check every minute
    }
}

// Password strength checker
class PasswordChecker {
    static checkStrength(password) {
        let score = 0;
        let feedback = [];

        if (password.length >= 8) score += 1;
        else feedback.push('Minimal 8 karakter');

        if (/[a-z]/.test(password)) score += 1;
        else feedback.push('Harus ada huruf kecil');

        if (/[A-Z]/.test(password)) score += 1;
        else feedback.push('Harus ada huruf besar');

        if (/[0-9]/.test(password)) score += 1;
        else feedback.push('Harus ada angka');

        if (/[^a-zA-Z0-9]/.test(password)) score += 1;
        else feedback.push('Harus ada karakter khusus');

        let strength = 'Sangat Lemah';
        if (score >= 4) strength = 'Kuat';
        else if (score >= 3) strength = 'Sedang';
        else if (score >= 2) strength = 'Lemah';

        return {
            score: score,
            strength: strength,
            feedback: feedback,
            isValid: score >= 3
        };
    }
}

// Initialize auth service
const authService = new AuthService();

// Start session monitoring
authService.startSessionMonitor();

// Export untuk penggunaan global
if (typeof window !== 'undefined') {
    window.AuthService = AuthService;
    window.authService = authService;
    window.PasswordChecker = PasswordChecker;
}