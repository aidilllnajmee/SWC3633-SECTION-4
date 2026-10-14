// =====================================================================
// Shared REST API Client & Helper Utility
// =====================================================================

const API_BASE_URL = window.location.origin.includes('localhost') || window.location.origin.includes('127.0.0.1')
    ? `${window.location.origin}${window.location.pathname.substring(0, window.location.pathname.indexOf('/frontends'))}/api/v1`
    : '/api/v1';

class ApiClient {
    static getBaseUrl() {
        // Automatically determine correct API path
        const currentPath = window.location.pathname;
        if (currentPath.includes('/frontends/')) {
            const root = currentPath.substring(0, currentPath.indexOf('/frontends'));
            return `${window.location.origin}${root}/api/index.php`;
        }
        return `${window.location.origin}/api/index.php`;
    }

    static getToken() {
        return localStorage.getItem('eventhub_token');
    }

    static setSession(token, user) {
        localStorage.setItem('eventhub_token', token);
        localStorage.setItem('eventhub_user', JSON.stringify(user));
    }

    static getUser() {
        const u = localStorage.getItem('eventhub_user');
        return u ? JSON.parse(u) : null;
    }

    static logout(redirectUrl = 'index.html') {
        localStorage.removeItem('eventhub_token');
        localStorage.removeItem('eventhub_user');
        window.location.href = redirectUrl;
    }

    static async request(endpoint, options = {}) {
        const url = `${this.getBaseUrl()}/${endpoint.replace(/^\//, '')}`;
        const headers = {
            'Content-Type': 'application/json',
            'Accept': 'application/json',
            ...(options.headers || {})
        };

        const token = this.getToken();
        if (token) {
            headers['Authorization'] = `Bearer ${token}`;
        }

        const config = {
            ...options,
            headers
        };

        try {
            const response = await fetch(url, config);
            const data = await response.json().catch(() => ({}));

            if (!response.ok) {
                const errorMsg = data.error?.message || data.message || `HTTP Error ${response.status}`;
                throw new Error(errorMsg);
            }

            return data;
        } catch (err) {
            console.error(`API Call [${options.method || 'GET'} ${endpoint}] failed:`, err);
            throw err;
        }
    }

    static get(endpoint, params = {}) {
        const queryString = new URLSearchParams(params).toString();
        const url = queryString ? `${endpoint}?${queryString}` : endpoint;
        return this.request(url, { method: 'GET' });
    }

    static post(endpoint, body = {}) {
        return this.request(endpoint, {
            method: 'POST',
            body: JSON.stringify(body)
        });
    }

    static put(endpoint, body = {}) {
        return this.request(endpoint, {
            method: 'PUT',
            body: JSON.stringify(body)
        });
    }

    static delete(endpoint) {
        return this.request(endpoint, { method: 'DELETE' });
    }
}

// Toast Notification Helper
function showToast(message, type = 'info') {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        container.style.cssText = 'position:fixed;bottom:20px;right:20px;z-index:9999;display:flex;flex-direction:column;gap:10px;';
        document.body.appendChild(container);
    }

    const toast = document.createElement('div');
    const bgColors = {
        success: '#10b981',
        error: '#ef4444',
        info: '#3b82f6',
        warning: '#f59e0b'
    };

    toast.style.cssText = `background:${bgColors[type] || '#334155'};color:#fff;padding:12px 20px;border-radius:8px;box-shadow:0 10px 15px -3px rgba(0,0,0,0.1);font-size:14px;font-weight:500;transition:all 0.3s ease;transform:translateY(10px);opacity:0;`;
    toast.textContent = message;
    container.appendChild(toast);

    setTimeout(() => {
        toast.style.transform = 'translateY(0)';
        toast.style.opacity = '1';
    }, 10);

    setTimeout(() => {
        toast.style.transform = 'translateY(10px)';
        toast.style.opacity = '0';
        setTimeout(() => toast.remove(), 300);
    }, 3500);
}

