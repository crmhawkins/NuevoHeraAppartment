// vite.config.js
import { defineConfig } from 'vite'
import laravel from 'laravel-vite-plugin'

const isDev = process.env.NODE_ENV !== 'production'

export default defineConfig({
  server: isDev ? {
    host: '0.0.0.0',
    port: 5173,
    strictPort: true,
    // pon tu IP local aquí:
    hmr: { host: '192.168.1.24', port: 5173 },
  } : undefined,

  plugins: [
    laravel({
      input: ['resources/js/app.js', 'resources/sass/app.scss'],
      refresh: true,
    }),
  ],
})
