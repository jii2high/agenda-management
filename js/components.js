// UI Components untuk Agenda Management System

// Notification Manager
class NotificationManager {
    constructor() {
        this.container = document.getElementById('notifications');
        this.notifications = [];
    }

    show(message, type = 'success', duration = CONFIG.UI.NOTIFICATION_DURATION) {
        const notification = this.createNotification(message, type, duration);
        this.notifications.push(notification);
        this.container.appendChild(notification.element);

        // Auto remove
        setTimeout(() => {
            this.remove(notification.id);
        }, duration);

        return notification.id;
    }

    createNotification(message, type, duration) {
        const id = Date.now().toString();
        const element = document.createElement('div');
        element.className = `notification notification-${type}`;
        element.innerHTML = `
            <div class="flex items-center gap-2">
                <div class="flex-shrink-0">
                    ${this.getIcon(type)}
                </div>
                <span class="text-sm font-medium flex-1">${message}</span>
                <button class="text-white/70 hover:text-white ml-2" onclick="notificationManager.remove('${id}')">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <line x1="18" y1="6" x2="6" y2="18"></line>
                        <line x1="6" y1="6" x2="18" y2="18"></line>
                    </svg>
                </button>
            </div>
        `;

        return { id, element, type, message };
    }

    getIcon(type) {
        const icons = {
            success: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22,4 12,14.01 9,11.01"></polyline></svg>',
            error: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="15" y1="9" x2="9" y2="15"></line><line x1="9" y1="9" x2="15" y2="15"></line></svg>',
            warning: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"></path><line x1="12" y1="9" x2="12" y2="13"></line><line x1="12" y1="17" x2="12.01" y2="17"></line></svg>',
            info: '<svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>'
        };
        return icons[type] || icons.info;
    }

    remove(id) {
        const notification = this.notifications.find(n => n.id === id);
        if (notification) {
            notification.element.classList.add('fade-out');
            setTimeout(() => {
                if (notification.element.parentNode) {
                    notification.element.parentNode.removeChild(notification.element);
                }
                this.notifications = this.notifications.filter(n => n.id !== id);
            }, CONFIG.UI.ANIMATION_DURATION);
        }
    }

    clear() {
        this.notifications.forEach(n => {
            if (n.element.parentNode) {
                n.element.parentNode.removeChild(n.element);
            }
        });
        this.notifications = [];
    }
}

// Modal Manager
class ModalManager {
    constructor() {
        this.modal = document.getElementById('modal');
        this.content = document.getElementById('modal-content');
        this.currentModal = null;
    }

    show(content, options = {}) {
        this.content.innerHTML = content;
        this.modal.classList.remove('hidden');
        this.modal.classList.add('modal-enter');
        
        this.currentModal = {
            content,
            options
        };

        // Close on backdrop click
        if (!options.persistent) {
            this.modal.addEventListener('click', this.handleBackdropClick.bind(this));
        }

        // Focus management
        this.trapFocus();

        return this;
    }

    hide() {
        if (this.currentModal) {
            this.modal.classList.add('modal-leave');
            setTimeout(() => {
                this.modal.classList.remove('modal-enter', 'modal-leave');
                this.modal.classList.add('hidden');
                this.content.innerHTML = '';
                this.currentModal = null;
            }, CONFIG.UI.ANIMATION_DURATION);
        }
    }

    handleBackdropClick(e) {
        if (e.target === this.modal) {
            this.hide();
        }
    }

    trapFocus() {
        const focusableElements = this.content.querySelectorAll(
            'button, [href], input, select, textarea, [tabindex]:not([tabindex="-1"])'
        );
        
        if (focusableElements.length > 0) {
            focusableElements[0].focus();
        }
    }

    confirm(title, message, onConfirm, onCancel) {
        const content = `
            <div>
                <h3 class="text-xl font-bold text-white mb-4">${title}</h3>
                <p class="text-gray-300 mb-6">${message}</p>
                <div class="flex gap-4 justify-end">
                    <button id="modal-cancel" class="btn-secondary">Batal</button>
                    <button id="modal-confirm" class="btn-primary">Konfirmasi</button>
                </div>
            </div>
        `;

        this.show(content, { persistent: true });

        document.getElementById('modal-cancel').addEventListener('click', () => {
            this.hide();
            if (onCancel) onCancel();
        });

        document.getElementById('modal-confirm').addEventListener('click', () => {
            this.hide();
            if (onConfirm) onConfirm();
        });
    }
}

// Loading Manager
class LoadingManager {
    constructor() {
        this.loading = document.getElementById('loading');
        this.isLoading = false;
        this.loadingCount = 0;
    }

    show(message = 'Loading...') {
        this.loadingCount++;
        if (!this.isLoading) {
            this.isLoading = true;
            this.loading.classList.remove('hidden');
            this.loading.querySelector('p').textContent = message;
        }
    }

    hide() {
        this.loadingCount = Math.max(0, this.loadingCount - 1);
        if (this.loadingCount === 0 && this.isLoading) {
            this.isLoading = false;
            this.loading.classList.add('hidden');
        }
    }

    forceHide() {
        this.loadingCount = 0;
        this.isLoading = false;
        this.loading.classList.add('hidden');
    }
}

// Form Validator
class FormValidator {
    constructor(form) {
        this.form = form;
        this.rules = {};
        this.errors = {};
    }

    addRule(fieldName, rules) {
        this.rules[fieldName] = rules;
        return this;
    }

    validate() {
        this.clearErrors();
        const formData = new FormData(this.form);
        let isValid = true;

        for (const [fieldName, rules] of Object.entries(this.rules)) {
            const value = formData.get(fieldName) || '';
            const fieldErrors = ConfigUtils.validateField(fieldName, value, rules);
            
            if (fieldErrors.length > 0) {
                this.errors[fieldName] = fieldErrors;
                this.showFieldError(fieldName, fieldErrors[0]);
                isValid = false;
            }
        }

        return {
            isValid,
            errors: this.errors,
            data: Object.fromEntries(formData)
        };
    }

    showFieldError(fieldName, message) {
        const field = this.form.querySelector(`[name="${fieldName}"]`);
        if (field) {
            field.classList.add('border-red-500');
            
            // Remove existing error message
            const existingError = field.parentNode.querySelector('.field-error');
            if (existingError) {
                existingError.remove();
            }

            // Add error message
            const errorDiv = document.createElement('div');
            errorDiv.className = 'field-error text-red-400 text-sm mt-1';
            errorDiv.textContent = message;
            field.parentNode.appendChild(errorDiv);
        }
    }

    clearErrors() {
        this.errors = {};
        
        // Remove error styles and messages
        this.form.querySelectorAll('.border-red-500').forEach(field => {
            field.classList.remove('border-red-500');
        });
        
        this.form.querySelectorAll('.field-error').forEach(error => {
            error.remove();
        });
    }
}

// Data Table Component
class DataTable {
    constructor(container, options = {}) {
        this.container = container;
        this.options = {
            searchable: true,
            sortable: true,
            pagination: true,
            itemsPerPage: CONFIG.UI.ITEMS_PER_PAGE,
            ...options
        };
        this.data = [];
        this.filteredData = [];
        this.currentPage = 1;
        this.sortColumn = null;
        this.sortDirection = 'asc';
    }

    setData(data) {
        this.data = data;
        this.filteredData = [...data];
        this.currentPage = 1;
        this.render();
    }

    render() {
        const totalPages = Math.ceil(this.filteredData.length / this.options.itemsPerPage);
        const startIndex = (this.currentPage - 1) * this.options.itemsPerPage;
        const endIndex = startIndex + this.options.itemsPerPage;
        const currentData = this.filteredData.slice(startIndex, endIndex);

        let html = '';

        // Search
        if (this.options.searchable) {
            html += `
                <div class="mb-4">
                    <input type="text" 
                           placeholder="Cari..." 
                           class="form-input max-w-md"
                           onkeyup="this.table.search(this.value)">
                </div>
            `;
        }

        // Table
        html += '<div class="overflow-x-auto"><table class="w-full">';
        
        // Header
        if (this.options.columns) {
            html += '<thead><tr class="border-b border-slate-600">';
            this.options.columns.forEach(col => {
                const sortIcon = this.getSortIcon(col.key);
                html += `
                    <th class="text-left py-3 px-4 text-gray-300 font-semibold ${col.sortable !== false ? 'cursor-pointer hover:text-white' : ''}"
                        ${col.sortable !== false ? `onclick="this.table.sort('${col.key}')"` : ''}>
                        ${col.label} ${sortIcon}
                    </th>
                `;
            });
            html += '</tr></thead>';
        }

        // Body
        html += '<tbody>';
        currentData.forEach(item => {
            html += '<tr class="border-b border-slate-700 hover:bg-slate-700/30">';
            this.options.columns.forEach(col => {
                const value = col.render ? col.render(item) : item[col.key];
                html += `<td class="py-3 px-4 text-gray-300">${value}</td>`;
            });
            html += '</tr>';
        });
        html += '</tbody></table></div>';

        // Pagination
        if (this.options.pagination && totalPages > 1) {
            html += this.renderPagination(totalPages);
        }

        this.container.innerHTML = html;
        
        // Set table reference for event handlers
        this.container.querySelectorAll('[onclick*="this.table"]').forEach(el => {
            el.table = this;
        });
    }

    renderPagination(totalPages) {
        let html = '<div class="flex justify-between items-center mt-4">';
        
        // Info
        const start = (this.currentPage - 1) * this.options.itemsPerPage + 1;
        const end = Math.min(this.currentPage * this.options.itemsPerPage, this.filteredData.length);
        html += `<div class="text-sm text-gray-400">
                    Menampilkan ${start}-${end} dari ${this.filteredData.length} data
                 </div>`;

        // Buttons
        html += '<div class="flex gap-2">';
        
        // Previous
        html += `<button class="px-3 py-1 rounded ${this.currentPage === 1 ? 'btn-disabled' : 'bg-gray-600 hover:bg-gray-500 text-white'}"
                         ${this.currentPage === 1 ? 'disabled' : `onclick="this.table.goToPage(${this.currentPage - 1})"`}>
                    Previous
                 </button>`;

        // Page numbers
        for (let i = 1; i <= totalPages; i++) {
            if (i === this.currentPage) {
                html += `<button class="px-3 py-1 rounded bg-blue-600 text-white">${i}</button>`;
            } else {
                html += `<button class="px-3 py-1 rounded bg-gray-600 hover:bg-gray-500 text-white"
                                 onclick="this.table.goToPage(${i})">${i}</button>`;
            }
        }

        // Next
        html += `<button class="px-3 py-1 rounded ${this.currentPage === totalPages ? 'btn-disabled' : 'bg-gray-600 hover:bg-gray-500 text-white'}"
                         ${this.currentPage === totalPages ? 'disabled' : `onclick="this.table.goToPage(${this.currentPage + 1})"`}>
                    Next
                 </button>`;

        html += '</div></div>';
        return html;
    }

    search(query) {
        if (!query.trim()) {
            this.filteredData = [...this.data];
        } else {
            this.filteredData = this.data.filter(item => {
                return this.options.columns.some(col => {
                    const value = item[col.key];
                    return value && value.toString().toLowerCase().includes(query.toLowerCase());
                });
            });
        }
        this.currentPage = 1;
        this.render();
    }

    sort(column) {
        if (this.sortColumn === column) {
            this.sortDirection = this.sortDirection === 'asc' ? 'desc' : 'asc';
        } else {
            this.sortColumn = column;
            this.sortDirection = 'asc';
        }

        this.filteredData.sort((a, b) => {
            let aVal = a[column];
            let bVal = b[column];

            if (typeof aVal === 'string') {
                aVal = aVal.toLowerCase();
                bVal = bVal.toLowerCase();
            }

            if (this.sortDirection === 'asc') {
                return aVal > bVal ? 1 : -1;
            } else {
                return aVal < bVal ? 1 : -1;
            }
        });

        this.render();
    }

    getSortIcon(column) {
        if (this.sortColumn !== column) {
            return '<svg class="inline w-4 h-4 ml-1 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16V4m0 0L3 8m4-4l4 4m6 0v12m0 0l4-4m-4 4l-4-4"></path></svg>';
        }

        if (this.sortDirection === 'asc') {
            return '<svg class="inline w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h6m4 0l4-4m0 0l4 4m-4-4v12"></path></svg>';
        } else {
            return '<svg class="inline w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4h13M3 8h9m-9 4h9m5-4v12m0 0l-4-4m4 4l4-4"></path></svg>';
        }
    }

    goToPage(page) {
        this.currentPage = page;
        this.render();
    }
}

// Card Component Builder
class CardBuilder {
    constructor() {
        this.cardElement = null;
    }

    create(options = {}) {
        const card = document.createElement('div');
        card.className = `card p-6 ${options.hover ? 'card-hover' : ''} ${options.className || ''}`;
        
        if (options.onClick) {
            card.addEventListener('click', options.onClick);
            card.classList.add('cursor-pointer');
        }

        this.cardElement = card;
        return this;
    }

    addHeader(title, subtitle = '', actions = '') {
        const header = document.createElement('div');
        header.className = 'flex justify-between items-start mb-4';
        header.innerHTML = `
            <div>
                <h3 class="text-xl font-bold text-white">${title}</h3>
                ${subtitle ? `<p class="text-gray-400 text-sm">${subtitle}</p>` : ''}
            </div>
            ${actions ? `<div class="flex gap-2">${actions}</div>` : ''}
        `;
        this.cardElement.appendChild(header);
        return this;
    }

    addContent(content) {
        const contentDiv = document.createElement('div');
        contentDiv.className = 'text-gray-300 mb-4';
        
        if (typeof content === 'string') {
            contentDiv.innerHTML = content;
        } else {
            contentDiv.appendChild(content);
        }
        
        this.cardElement.appendChild(contentDiv);
        return this;
    }

    addFooter(content) {
        const footer = document.createElement('div');
        footer.className = 'flex justify-between items-center pt-4 border-t border-slate-600';
        
        if (typeof content === 'string') {
            footer.innerHTML = content;
        } else {
            footer.appendChild(content);
        }
        
        this.cardElement.appendChild(footer);
        return this;
    }

    addBadge(text, type = 'default') {
        const badge = document.createElement('span');
        badge.className = `badge badge-${type}`;
        badge.textContent = text;
        return badge;
    }

    build() {
        return this.cardElement;
    }
}

// Icon Helper
class IconHelper {
    static getIcon(name, className = 'w-4 h-4') {
        const icons = {
            // Navigation
            home: `<svg class="${className}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"></path></svg>`,
            plus: `<svg class="${className}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"></path></svg>`,
            user: `<svg class="${className}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"></path></svg>`,
            logout: `<svg class="${className}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"></path></svg>`,
            
            // Content
            calendar: `<svg class="${className}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg>`,
            clock: `<svg class="${className}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`,
            location: `<svg class="${className}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"></path><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"></path></svg>`,
            edit: `<svg class="${className}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"></path></svg>`,
            
            // Actions
            check: `<svg class="${className}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>`,
            x: `<svg class="${className}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>`,
            trash: `<svg class="${className}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path></svg>`,
            
            // Status
            checkCircle: `<svg class="${className}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`,
            xCircle: `<svg class="${className}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 14l2-2m0 0l2-2m-2 2l-2-2m2 2l2 2m7-2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`,
            alertCircle: `<svg class="${className}" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>`,
            
            // Loading
            spinner: `<svg class="${className} animate-spin" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path></svg>`,
        };

        return icons[name] || '';
    }

    static createIcon(name, className = 'w-4 h-4') {
        const div = document.createElement('div');
        div.innerHTML = this.getIcon(name, className);
        return div.firstChild;
    }
}

// Date Time Helper
class DateTimeHelper {
    static formatDate(date, options = {}) {
        const d = new Date(date);
        const defaultOptions = {
            year: 'numeric',
            month: 'long',
            day: 'numeric',
            timeZone: CONFIG.APP.TIMEZONE
        };

        return d.toLocaleDateString('id-ID', { ...defaultOptions, ...options });
    }

    static formatTime(time) {
        if (!time) return '';
        return time + ' WIB';
    }

    static formatDateTime(date, time) {
        const formattedDate = this.formatDate(date, { 
            weekday: 'long',
            year: 'numeric',
            month: 'long',
            day: 'numeric'
        });
        const formattedTime = this.formatTime(time);
        return `${formattedDate}, ${formattedTime}`;
    }

    static isToday(date) {
        const today = new Date().toISOString().split('T')[0];
        return date === today;
    }

    static isPast(date) {
        const today = new Date().toISOString().split('T')[0];
        return date < today;
    }

    static isFuture(date) {
        const today = new Date().toISOString().split('T')[0];
        return date > today;
    }

    static getRelativeTime(date) {
        const now = new Date();
        const targetDate = new Date(date);
        const diffTime = targetDate - now;
        const diffDays = Math.ceil(diffTime / (1000 * 60 * 60 * 24));

        if (diffDays === 0) return 'Hari ini';
        if (diffDays === 1) return 'Besok';
        if (diffDays === -1) return 'Kemarin';
        if (diffDays > 0) return `${diffDays} hari lagi`;
        return `${Math.abs(diffDays)} hari yang lalu`;
    }
}

// Utility Functions
class Utils {
    static debounce(func, wait) {
        let timeout;
        return function executedFunction(...args) {
            const later = () => {
                clearTimeout(timeout);
                func(...args);
            };
            clearTimeout(timeout);
            timeout = setTimeout(later, wait);
        };
    }

    static throttle(func, limit) {
        let inThrottle;
        return function() {
            const args = arguments;
            const context = this;
            if (!inThrottle) {
                func.apply(context, args);
                inThrottle = true;
                setTimeout(() => inThrottle = false, limit);
            }
        };
    }

    static sanitizeHtml(str) {
        const temp = document.createElement('div');
        temp.textContent = str;
        return temp.innerHTML;
    }

    static generateId() {
        return Date.now().toString(36) + Math.random().toString(36).substr(2);
    }

    static copyToClipboard(text) {
        return navigator.clipboard.writeText(text).then(() => {
            return true;
        }).catch(() => {
            // Fallback for older browsers
            const textArea = document.createElement('textarea');
            textArea.value = text;
            document.body.appendChild(textArea);
            textArea.focus();
            textArea.select();
            try {
                const successful = document.execCommand('copy');
                document.body.removeChild(textArea);
                return successful;
            } catch (err) {
                document.body.removeChild(textArea);
                return false;
            }
        });
    }

    static downloadFile(data, filename, type = 'application/json') {
        const blob = new Blob([data], { type });
        const url = window.URL.createObjectURL(blob);
        const a = document.createElement('a');
        a.href = url;
        a.download = filename;
        document.body.appendChild(a);
        a.click();
        window.URL.revokeObjectURL(url);
        document.body.removeChild(a);
    }
}

// Initialize global components
const notificationManager = new NotificationManager();
const modalManager = new ModalManager();
const loadingManager = new LoadingManager();

// Global keyboard shortcuts
document.addEventListener('keydown', (e) => {
    // Escape key to close modal
    if (e.key === 'Escape' && modalManager.currentModal) {
        modalManager.hide();
    }
    
    // Ctrl/Cmd + K for search (if implemented)
    if ((e.ctrlKey || e.metaKey) && e.key === 'k') {
        e.preventDefault();
        const searchInput = document.querySelector('[type="search"], [placeholder*="Cari"]');
        if (searchInput) {
            searchInput.focus();
        }
    }
});

// Export untuk penggunaan global
if (typeof window !== 'undefined') {
    window.notificationManager = notificationManager;
    window.modalManager = modalManager;
    window.loadingManager = loadingManager;
    window.FormValidator = FormValidator;
    window.DataTable = DataTable;
    window.CardBuilder = CardBuilder;
    window.IconHelper = IconHelper;
    window.DateTimeHelper = DateTimeHelper;
    window.Utils = Utils;
}