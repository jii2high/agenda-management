// API Handler untuk Agenda Management System

class ApiService {
    constructor() {
        this.baseURL = CONFIG.API.BASE_URL;
        this.headers = {
            'Content-Type': 'application/json',
        };
    }

    // Generic API call method
    async apiCall(endpoint, options = {}) {
        const url = this.baseURL + endpoint;
        const config = {
            headers: this.headers,
            ...options
        };

        try {
            console.log(`API Call: ${options.method || 'GET'} ${url}`);
            
            const response = await fetch(url, config);
            
            if (!response.ok) {
                throw new Error(`HTTP ${response.status}: ${response.statusText}`);
            }

            const data = await response.json();
            console.log('API Response:', data);
            
            return data;
        } catch (error) {
            console.error('API Error:', error);
            
            // Fallback ke demo data jika API tidak tersedia
            if (CONFIG.DEMO.ENABLED) {
                console.log('Falling back to demo data...');
                return this.handleFallback(endpoint, options);
            }
            
            throw error;
        }
    }

    // Handle fallback untuk demo mode
    handleFallback(endpoint, options) {
        const method = options.method || 'GET';
        
        // Login endpoint
        if (endpoint === CONFIG.API.ENDPOINTS.LOGIN && method === 'POST') {
            const body = JSON.parse(options.body);
            const user = CONFIG.DEMO.USERS.find(u => 
                u.email === body.email && u.password === body.password
            );
            
            if (user) {
                return {
                    success: true,
                    user: { ...user, password: undefined }
                };
            } else {
                return {
                    success: false,
                    message: 'Email atau password salah'
                };
            }
        }
        
        // Agendas endpoints
        if (endpoint.startsWith('/agendas')) {
            return this.handleAgendasFallback(endpoint, method);
        }
        
        // Default fallback
        return {
            success: true,
            data: [],
            message: 'Demo mode: Data tidak tersedia'
        };
    }

    // Handle agendas fallback
    handleAgendasFallback(endpoint, method) {
        const currentUser = AuthService.getCurrentUser();
        let agendas = [...CONFIG.DEMO.AGENDAS];

        // Filter berdasarkan endpoint dan role
        if (endpoint === CONFIG.API.ENDPOINTS.AGENDAS_APPROVED) {
            agendas = agendas.filter(a => a.status === 'approved');
        } else if (endpoint === CONFIG.API.ENDPOINTS.AGENDAS_PENDING) {
            agendas = agendas.filter(a => a.status === 'pending');
        } else if (endpoint.startsWith(CONFIG.API.ENDPOINTS.AGENDAS_USER)) {
            const userId = parseInt(endpoint.split('/').pop());
            agendas = agendas.filter(a => 
                a.status === 'approved' || a.created_by === userId
            );
        } else if (currentUser) {
            // Filter berdasarkan role user
            if (currentUser.role === 'siswa') {
                agendas = agendas.filter(a => a.status === 'approved');
            } else if (currentUser.role === 'guru') {
                agendas = agendas.filter(a => 
                    a.status === 'approved' || a.created_by === currentUser.id
                );
            }
            // Admin bisa lihat semua
        }

        return {
            success: true,
            data: agendas
        };
    }

    // Authentication
    async login(email, password) {
        return this.apiCall(CONFIG.API.ENDPOINTS.LOGIN, {
            method: 'POST',
            body: JSON.stringify({ email, password })
        });
    }

    // Agendas
    async getAgendas() {
        return this.apiCall(CONFIG.API.ENDPOINTS.AGENDAS);
    }

    async getApprovedAgendas() {
        return this.apiCall(CONFIG.API.ENDPOINTS.AGENDAS_APPROVED);
    }

    async getPendingAgendas() {
        return this.apiCall(CONFIG.API.ENDPOINTS.AGENDAS_PENDING);
    }

    async getUserAgendas(userId) {
        return this.apiCall(`${CONFIG.API.ENDPOINTS.AGENDAS_USER}/${userId}`);
    }

    async createAgenda(agendaData) {
        return this.apiCall(CONFIG.API.ENDPOINTS.AGENDAS, {
            method: 'POST',
            body: JSON.stringify(agendaData)
        });
    }

    async updateAgenda(agendaId, agendaData) {
        return this.apiCall(`${CONFIG.API.ENDPOINTS.AGENDAS}/${agendaId}`, {
            method: 'PUT',
            body: JSON.stringify(agendaData)
        });
    }

    async approveAgenda(agendaId, approvedBy) {
        return this.apiCall(`${CONFIG.API.ENDPOINTS.AGENDAS}/${agendaId}/approve`, {
            method: 'PUT',
            body: JSON.stringify({ approved_by: approvedBy })
        });
    }

    async rejectAgenda(agendaId, approvedBy) {
        return this.apiCall(`${CONFIG.API.ENDPOINTS.AGENDAS}/${agendaId}/reject`, {
            method: 'PUT',
            body: JSON.stringify({ approved_by: approvedBy })
        });
    }

    async deleteAgenda(agendaId) {
        return this.apiCall(`${CONFIG.API.ENDPOINTS.AGENDAS}/${agendaId}`, {
            method: 'DELETE'
        });
    }

    // Users
    async getUsers() {
        return this.apiCall(CONFIG.API.ENDPOINTS.USERS);
    }

    async createUser(userData) {
        return this.apiCall(CONFIG.API.ENDPOINTS.USERS, {
            method: 'POST',
            body: JSON.stringify(userData)
        });
    }

    // Statistics
    async getStats() {
        return this.apiCall(CONFIG.API.ENDPOINTS.STATS);
    }

    // Activities
    async getActivities(limit = 50) {
        return this.apiCall(`${CONFIG.API.ENDPOINTS.ACTIVITIES}?limit=${limit}`);
    }
}

// Data Manager untuk caching dan state management
class DataManager {
    constructor() {
        this.cache = new Map();
        this.listeners = new Map();
    }

    // Set data dengan caching
    setData(key, data, expiry = 300000) { // 5 minutes default
        this.cache.set(key, {
            data,
            timestamp: Date.now(),
            expiry
        });
        
        // Notify listeners
        if (this.listeners.has(key)) {
            this.listeners.get(key).forEach(callback => callback(data));
        }
    }

    // Get data dari cache atau fetch baru
    getData(key) {
        const cached = this.cache.get(key);
        if (cached && (Date.now() - cached.timestamp) < cached.expiry) {
            return cached.data;
        }
        return null;
    }

    // Subscribe ke perubahan data
    subscribe(key, callback) {
        if (!this.listeners.has(key)) {
            this.listeners.set(key, []);
        }
        this.listeners.get(key).push(callback);

        // Return unsubscribe function
        return () => {
            const callbacks = this.listeners.get(key);
            if (callbacks) {
                const index = callbacks.indexOf(callback);
                if (index > -1) {
                    callbacks.splice(index, 1);
                }
            }
        };
    }

    // Clear cache
    clearCache(key = null) {
        if (key) {
            this.cache.delete(key);
        } else {
            this.cache.clear();
        }
    }

    // Invalidate expired cache entries
    cleanup() {
        const now = Date.now();
        for (const [key, value] of this.cache.entries()) {
            if (now - value.timestamp >= value.expiry) {
                this.cache.delete(key);
            }
        }
    }
}

// Network status handler
class NetworkHandler {
    constructor() {
        this.isOnline = navigator.onLine;
        this.listeners = [];
        
        window.addEventListener('online', () => {
            this.isOnline = true;
            this.notifyListeners(true);
        });
        
        window.addEventListener('offline', () => {
            this.isOnline = false;
            this.notifyListeners(false);
        });
    }

    onStatusChange(callback) {
        this.listeners.push(callback);
        return () => {
            const index = this.listeners.indexOf(callback);
            if (index > -1) {
                this.listeners.splice(index, 1);
            }
        };
    }

    notifyListeners(status) {
        this.listeners.forEach(callback => callback(status));
    }

    getStatus() {
        return this.isOnline;
    }
}

// Request queue untuk offline support
class RequestQueue {
    constructor() {
        this.queue = JSON.parse(localStorage.getItem('apiQueue') || '[]');
        this.processing = false;
    }

    // Add request to queue
    enqueue(request) {
        this.queue.push({
            ...request,
            timestamp: Date.now(),
            id: this.generateId()
        });
        this.saveQueue();
    }

    // Process queue when online
    async processQueue() {
        if (this.processing || this.queue.length === 0) return;
        
        this.processing = true;
        const apiService = new ApiService();

        while (this.queue.length > 0) {
            const request = this.queue.shift();
            try {
                await apiService.apiCall(request.endpoint, request.options);
                console.log('Queued request processed:', request.id);
            } catch (error) {
                console.error('Failed to process queued request:', error);
                // Re-add to queue if failed
                this.queue.unshift(request);
                break;
            }
        }

        this.processing = false;
        this.saveQueue();
    }

    saveQueue() {
        localStorage.setItem('apiQueue', JSON.stringify(this.queue));
    }

    clearQueue() {
        this.queue = [];
        this.saveQueue();
    }

    generateId() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2);
    }

    getQueueLength() {
        return this.queue.length;
    }
}

// Initialize global instances
const apiService = new ApiService();
const dataManager = new DataManager();
const networkHandler = new NetworkHandler();
const requestQueue = new RequestQueue();

// Auto-process queue when online
networkHandler.onStatusChange(async (isOnline) => {
    if (isOnline && requestQueue.getQueueLength() > 0) {
        console.log('Network restored, processing queued requests...');
        await requestQueue.processQueue();
    }
});

// Cleanup cache every 5 minutes
setInterval(() => {
    dataManager.cleanup();
}, 300000);

// Export untuk penggunaan global
if (typeof window !== 'undefined') {
    window.ApiService = ApiService;
    window.apiService = apiService;
    window.dataManager = dataManager;
    window.networkHandler = networkHandler;
    window.requestQueue = requestQueue;
}