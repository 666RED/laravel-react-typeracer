import tailwindcss from '@tailwindcss/vite';
import react from '@vitejs/plugin-react';
import laravel from 'laravel-vite-plugin';
import { resolve } from 'node:path';
import path from 'path';
import { defineConfig } from 'vite';

export default defineConfig(({ mode }) => ({
  plugins: [
    laravel({
      input: ['resources/css/app.css', 'resources/js/app.tsx'],
      ssr: 'resources/js/ssr.tsx',
      refresh: mode === 'production',
    }),
    react(),
    tailwindcss(),
  ],
  esbuild: {
    jsx: 'automatic',
  },
  resolve: {
    alias: {
      'ziggy-js': resolve(__dirname, 'vendor/tightenco/ziggy'),
      '@': path.resolve(__dirname, 'resources/js'),
    },
  },
  server:
    mode === 'development'
      ? {
          host: '0.0.0.0',
          port: 5173,
          hmr: {
            host: 'localhost',
            port: 5173,
          },
          watch: {
            usePolling: process.env.APP_URL === 'http://localhost', // only run in docker dev
          },
        }
      : undefined,
}));
