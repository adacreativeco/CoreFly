// CoreFly API Client
const API_BASE = '/api';

export async function apiCall(endpoint, method = 'GET', data = null) {
    const token = localStorage.getItem('token');
    if (!token && endpoint !== '/auth/login' && !endpoint.startsWith('/auth/')) {
        window.location.href = '/login.html';
        return null;
    }

    const headers = {
        'Content-Type': 'application/json',
        'Accept': 'application/json'
    };

    if (token) {
        headers['Authorization'] = `Bearer ${token}`;
    }

    const config = {
        method,
        headers
    };

    if (data) {
        config.body = JSON.stringify(data);
    }

    try {
        const response = await fetch(`${API_BASE}${endpoint}`, config);

        // Handle 401 Unauthorized
        if (response.status === 401) {
            localStorage.removeItem('token');
            if (!window.location.pathname.includes('login.html')) {
                window.location.href = '/login.html';
            }
            return null;
        }

        let responseData = null;
        const contentType = response.headers.get('content-type');
        if (contentType && contentType.includes('application/json')) {
            try {
                responseData = await response.json();
            } catch (e) {
                // JSON parsing failed, maybe empty body
                responseData = {};
            }
        } else {
            responseData = await response.text();
        }

        if (!response.ok) {
            const errorMessage = responseData?.message || responseData?.error || response.statusText;
            console.error(`API Error ${response.status} for ${endpoint}: ${errorMessage}`);
            return { error: true, status: response.status, message: errorMessage };
        }

        return responseData;
    } catch (error) {
        console.error('Network or Parse Error:', error);
        return { error: true, message: 'Bağlantı hatası oluştu.' };
    }
}

export function getToken() {
    return localStorage.getItem('token');
}

export function getCurrentUser() {
    const userStr = localStorage.getItem('user');
    return userStr ? JSON.parse(userStr) : null;
}
