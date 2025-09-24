// Configuration untuk Agenda Management System

const CONFIG = {
    // API Configuration
    API: {
        BASE_URL: 'https://your-domain.com/api', // Ganti dengan URL backend Anda
        ENDPOINTS: {
            LOGIN: '/login',
            AGENDAS: '/agendas',
            AGENDAS_APPROVED: '/agendas/approved',
            AGENDAS_PENDING: '/agendas/pending',
            AGENDAS_USER: '/agendas/user',
            USERS: '/users',
            STATS: '/stats',
            ACTIVITIES: '/activities'
        }
    },
    
    // App Configuration
    APP: {
        NAME: 'Agenda Management System',
        VERSION: '1.0.0',
        SCHOOL_NAME: 'SMK Negeri 1 Kota Bekasi',
        DEFAULT_LANGUAGE: 'id',
        TIMEZONE: 'Asia/Jakarta'
    },
    
    // UI Configuration
    UI: {
        NOTIFICATION_DURATION: 3000,
        LOADING_MIN_TIME: 500,
        ANIMATION_DURATION: 300,
        ITEMS_PER_PAGE: 10,
        MAX_FILE_SIZE: 5 * 1024 * 1024, // 5MB
    },
    
    // User Roles
    ROLES: {
        ADMIN: 'admin',
        GURU: 'guru',
        SISWA: 'siswa'
    },
    
    // Agenda Status
    AGENDA_STATUS: {
        PENDING: 'pending',
        APPROVED: 'approved',
        REJECTED: 'rejected'
    },
    
    // Email Domains
    EMAIL_DOMAINS: {
        ADMIN: '@smkn1kotabekasi.admin.sch.id',
        GURU: '@smkn1kotabekasi.guru.sch.id',
        SISWA: '@smkn1kotabekasi.sch.id'
    },
    
    // Date/Time Configuration
    DATE_FORMAT: {
        DISPLAY: 'DD MMMM YYYY',
        INPUT: 'YYYY-MM-DD',
        TIME: 'HH:mm',
        FULL: 'dddd, DD MMMM YYYY HH:mm'
    },
    
    // Validation Rules
    VALIDATION: {
        EMAIL: {
            MIN_LENGTH: 5,
            MAX_LENGTH: 100,
            PATTERN: /^[^\s@]+@[^\s@]+\.[^\s@]+$/
        },
        PASSWORD: {
            MIN_LENGTH: 6,
            MAX_LENGTH: 50
        },
        AGENDA: {
            TITLE_MAX_LENGTH: 255,
            DESCRIPTION_MAX_LENGTH: 1000,
            LOCATION_MAX_LENGTH: 255
        }
    },
    
    // Demo Data (untuk fallback jika API tidak tersedia)
    DEMO: {
        ENABLED: true,
        USERS: [
            {
                id: 1,
                email: 'admin@smkn1kotabekasi.admin.sch.id',
                password: 'password',
                role: 'admin',
                nama: 'Administrator'
            },
            {
                id: 2,
                email: 'guru1@smkn1kotabekasi.guru.sch.id',
                password: 'password',
                role: 'guru',
                nama: 'Pak Budi Santoso'
            },
            {
                id: 3,
                email: 'siswa1@smkn1kotabekasi.sch.id',
                password: 'password',
                role: 'siswa',
                nama: 'Ahmad Rizki'
            }
        ],
        AGENDAS: [
            {
                id: 1,
                judul: "APEL PEMBUKAAN LOMBA",
                deskripsi: "Apel pembukaan acara perlombaan siswa dan guru yang akan dilaksanakan untuk membuka rangkaian kegiatan perlombaan tingkat sekolah.",
                tanggal: new Date().toISOString().split('T')[0],
                waktu: "07:00",
                tempat: "Lapangan Sekolah",
                status: "approved",
                created_by: 1
            },
            {
                id: 2,
                judul: "PERLOMBAAN",
                deskripsi: "Perlombaan pertama dimulai pada pukul 07.30 dan diharapkan selesai pada pukul 15.45. Berbagai kategori lomba akan dilaksanakan.",
                tanggal: new Date().toISOString().split('T')[0],
                waktu: "07:30",
                tempat: "Berbagai Lokasi Sekolah",
                status: "approved",
                created_by: 1
            },
            {
                id: 3,
                judul: "SEMINAR TOEC",
                deskripsi: "Seminar yang bertujuan untuk mensosialisasikan TOEC kepada seluruh siswa.",
                tanggal: "2025-09-20",
                waktu: "08:00",
                tempat: "Aula Sekolah",
                status: "approved",
                created_by: 2
            },
            {
                id: 4,
                judul: "KUNJUNGAN KE KUIL JEPANG",
                deskripsi: "Study Tour yang bertujuan agar para siswa/i dapat mempelajari budaya-budaya dan tradisi keagamaan.",
                tanggal: "2025-10-20",
                waktu: "09:00",
                tempat: "Kuil Sensoji, Tokyo, Jepang",
                status: "pending",
                created_by: 2
            }
        ]
    },
    
    // Error Messages
    MESSAGES: {
        ERROR: {
            NETWORK: 'Koneksi internet bermasalah. Silakan coba lagi.',
            LOGIN_FAILED: 'Email atau password salah.',
            VALIDATION_FAILED: 'Data yang dimasukkan tidak valid.',
            PERMISSION_DENIED: 'Anda tidak memiliki izin untuk melakukan aksi ini.',
            SERVER_ERROR: 'Terjadi kesalahan pada server. Silakan coba lagi.',
            DATA_NOT_FOUND: 'Data tidak ditemukan.',
            REQUIRED_FIELD: 'Field ini wajib diisi.',
            INVALID_EMAIL: 'Format email tidak valid.',
            INVALID_DATE: 'Format tanggal tidak valid.',
            FILE_TOO_LARGE: 'Ukuran file terlalu besar.'
        },
        SUCCESS: {
            LOGIN: 'Login berhasil!',
            LOGOUT: 'Logout berhasil!',
            AGENDA_CREATED: 'Agenda berhasil dibuat!',
            AGENDA_UPDATED: 'Agenda berhasil diperbarui!',
            AGENDA_DELETED: 'Agenda berhasil dihapus!',
            AGENDA_APPROVED: 'Agenda berhasil disetujui!',
            AGENDA_REJECTED: 'Agenda berhasil ditolak!',
            DATA_SAVED: 'Data berhasil disimpan!',
            DATA_LOADED: 'Data berhasil dimuat!'
        },
        INFO: {
            LOADING: 'Sedang memuat data...',
            SAVING: 'Sedang menyimpan data...',
            NO_DATA: 'Tidak ada data yang tersedia.',
            PENDING_APPROVAL: 'Agenda Anda sedang menunggu persetujuan admin.',
            GURU_SUBMIT_INFO: 'Agenda yang Anda ajukan akan masuk ke daftar pending dan baru dapat dilihat siswa setelah disetujui oleh admin.'
        }
    },
    
    // Theme Configuration
    THEME: {
        PRIMARY_COLOR: '#3b82f6',
        SECONDARY_COLOR: '#64748b',
        SUCCESS_COLOR: '#10b981',
        ERROR_COLOR: '#ef4444',
        WARNING_COLOR: '#f59e0b',
        INFO_COLOR: '#3b82f6'
    }
};

// Export configuration
if (typeof module !== 'undefined' && module.exports) {
    module.exports = CONFIG;
} else {
    window.CONFIG = CONFIG;
}

// Utility functions untuk konfigurasi
const ConfigUtils = {
    // Get API URL
    getApiUrl: (endpoint) => {
        return CONFIG.API.BASE_URL + endpoint;
    },
    
    // Check if demo mode
    isDemoMode: () => {
        return CONFIG.DEMO.ENABLED;
    },
    
    // Get role from email
    getRoleFromEmail: (email) => {
        if (email.includes(CONFIG.EMAIL_DOMAINS.ADMIN)) {
            return CONFIG.ROLES.ADMIN;
        } else if (email.includes(CONFIG.EMAIL_DOMAINS.GURU)) {
            return CONFIG.ROLES.GURU;
        } else if (email.includes(CONFIG.EMAIL_DOMAINS.SISWA)) {
            return CONFIG.ROLES.SISWA;
        }
        return null;
    },
    
    // Validate email domain
    isValidEmailDomain: (email) => {
        return Object.values(CONFIG.EMAIL_DOMAINS).some(domain => 
            email.toLowerCase().includes(domain.toLowerCase())
        );
    },
    
    // Format date
    formatDate: (date, format = CONFIG.DATE_FORMAT.DISPLAY) => {
        try {
            const d = new Date(date);
            return d.toLocaleDateString('id-ID', {
                weekday: format.includes('dddd') ? 'long' : undefined,
                year: 'numeric',
                month: 'long',
                day: '2-digit'
            });
        } catch (error) {
            return date;
        }
    },
    
    // Format time
    formatTime: (time) => {
        try {
            return time + ' WIB';
        } catch (error) {
            return time;
        }
    },
    
    // Validate form data
    validateField: (field, value, rules = {}) => {
        const errors = [];
        
        // Required validation
        if (rules.required && (!value || value.toString().trim() === '')) {
            errors.push(`${field} ${CONFIG.MESSAGES.ERROR.REQUIRED_FIELD}`);
        }
        
        // Length validation
        if (value && rules.maxLength && value.toString().length > rules.maxLength) {
            errors.push(`${field} maksimal ${rules.maxLength} karakter`);
        }
        
        if (value && rules.minLength && value.toString().length < rules.minLength) {
            errors.push(`${field} minimal ${rules.minLength} karakter`);
        }
        
        // Email validation
        if (value && rules.isEmail && !CONFIG.VALIDATION.EMAIL.PATTERN.test(value)) {
            errors.push(CONFIG.MESSAGES.ERROR.INVALID_EMAIL);
        }
        
        return errors;
    }
};

// Make ConfigUtils available globally
if (typeof window !== 'undefined') {
    window.ConfigUtils = ConfigUtils;
}