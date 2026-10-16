import axios from 'axios';

// One axios instance for the whole app. Every request goes to Laravel's /api routes.
// In development, Vite (vite.config.js) forwards /api to the Laravel server, so the
// browser sees one site and the login session cookie and the CSRF token
// (XSRF-TOKEN cookie) are sent along automatically.
const api = axios.create({
    baseURL: '/api',
    headers: { Accept: 'application/json' },
    withCredentials: true,
    withXSRFToken: true,
});

api.interceptors.response.use(
    (response) => response,
    (error) => {
        // 419 means the page's security token expired (e.g. left open for hours).
        // Reloading the page gets a fresh one.
        if (error.response?.status === 419) {
            window.location.reload();
        }
        return Promise.reject(error);
    }
);

// Turns a failed request into one message you can show in an alert box.
// Laravel validation errors (422) look like { errors: { email: ["..."] } }.
export function errorMessage(error) {
    const data = error.response?.data;
    if (data?.errors) {
        return Object.values(data.errors).flat().join(' ');
    }
    return data?.message || 'Something went wrong. Please try again.';
}

export default api;
