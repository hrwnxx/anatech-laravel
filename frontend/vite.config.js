import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

// The Laravel API started with `php artisan serve` (see ../backend).
const laravelServer = 'http://127.0.0.1:8000';

// React calls /api/... and Vite forwards it to Laravel. The browser only ever
// talks to this dev server (http://localhost:5173), so the login cookie works
// without any CORS setup, and the backend address lives in one place.
export default defineConfig({
    plugins: [react()],
    server: {
        port: 5173,
        proxy: {
            '/api': { target: laravelServer, changeOrigin: true },
            '/auth': { target: laravelServer, changeOrigin: true },
        },
    },
});
