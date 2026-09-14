import axios from "axios";

const baseURL = import.meta.env.VITE_API_BASE_URL || '/api';

const instance = axios.create({
    baseURL: baseURL,
    withCredentials: true, // CRUCIAL: Allows the browser to send/receive Laravel Sanctum cookies
    headers: {
        'Accept': 'application/json',
        'Content-Type': 'application/json',
    }
});

// Helper function to read cookies from the browser
const getCookie = (name) => {
    const value = `; ${document.cookie}`;
    const parts = value.split(`; ${name}=`);
    if (parts.length === 2) return decodeURIComponent(parts.pop().split(';').shift());
    return null;
};

// -----------------------------------------------------------------------------
// REQUEST INTERCEPTOR: Automatically attach CSRF token to requests
// -----------------------------------------------------------------------------
instance.interceptors.request.use(
    (config) => {
        const token = getCookie('XSRF-TOKEN');
        if (token) {
            config.headers['X-XSRF-TOKEN'] = token;
        }
        return config;
    },
    (error) => {
        return Promise.reject(error);
    }
);

// -----------------------------------------------------------------------------
// RESPONSE INTERCEPTOR: Handle global auth/CSRF errors gracefully
// -----------------------------------------------------------------------------
instance.interceptors.response.use(
    (response) => {
        return response;
    },
    (error) => {
        if (error.response) {
            // 401 Unauthorized: Session expired or invalid token
            if (error.response.status === 401) {
                console.warn("Session expired. Redirecting to login...");
                // Uncomment the line below if you use Vue Router to redirect:
                // router.push('/login'); 
                // Or for a hard reload: window.location.href = '/login';
            }
            
            // 419 Page Expired: CSRF token mismatch
            if (error.response.status === 419) {
                console.warn("CSRF token mismatch. Refreshing page to get a new token...");
                window.location.reload();
            }
        }
        return Promise.reject(error);
    }
);

// -----------------------------------------------------------------------------
// HELPER: Fetch initial CSRF cookie (Call this before login/registration)
// -----------------------------------------------------------------------------
export const fetchCsrfToken = async () => {
    try {
        await instance.get("/sanctum/csrf-cookie");
    } catch (error) {
        console.error("Failed to fetch CSRF token:", error);
        throw error;
    }
};

export default instance;