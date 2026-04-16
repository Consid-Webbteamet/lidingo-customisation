import { defineConfig } from 'vite';
import { resolve } from 'path';

const devServerCorsOrigin = /^https?:\/\/(?:(?:[^:]+\.)?municipio-deployment\.test|(?:[^:]+\.)?localhost|127\.0\.0\.1|\[::1\])(?::\d+)?$/;

export default defineConfig({
    server: {
        host: 'localhost',
        port: 5173,
        strictPort: true,
        origin: 'http://localhost:5173',
        cors: {
            origin: devServerCorsOrigin
        },
        hmr: {
            host: 'localhost',
            port: 5173,
            protocol: 'ws'
        }
    },
    build: {
        outDir: 'dist',
        emptyOutDir: true,
        manifest: 'manifest.json',
        rollupOptions: {
            input: {
                main: resolve(__dirname, 'source/js/main.js'),
                style: resolve(__dirname, 'source/sass/style.scss'),
                admin: resolve(__dirname, 'source/js/admin.js'),
                'admin-style': resolve(__dirname, 'source/sass/admin.scss')
            },
            output: {
                entryFileNames: 'js/[name].[hash].js',
                chunkFileNames: 'js/[name].[hash].js',
                assetFileNames: (assetInfo) => {
                    if (assetInfo.name && assetInfo.name.endsWith('.css')) {
                        return 'css/[name].[hash][extname]';
                    }

                    return 'assets/[name].[hash][extname]';
                }
            }
        }
    },
    css: {
        devSourcemap: true
    }
});
