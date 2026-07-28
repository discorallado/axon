import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js', 'resources/css/filament/admin/theme.css'],
            refresh: true,
        }),
        tailwindcss(),
    ],
    server: {
        host: '0.0.0.0', // Permite que Vite escuche fuera del contenedor ddev
        port: 5173,
        strictPort: true,
        hmr: {
            host: process.env.DDEV_HOSTNAME, // Usa el dominio de tu proyecto DDEV
            protocol: 'wss', // Fuerza WebSocket Seguro para el Hot Reload
        },
        watch: {
            ignored: ['**/storage/framework/views/**'], // <--- TU CONFIGURACIÓN ACTUAL SE QUEDA AQUÍ
        },
    },
});
