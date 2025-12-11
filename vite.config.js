import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import path from 'path';

export default defineConfig({
  resolve: {
    alias: [{ find: '@', replacement: path.resolve(__dirname, 'resources') }],
  },
  plugins: [
    laravel({
      input: [
        'resources/css/app.scss',
        'resources/js/app.js',
        'resources/js/password-strength.js',
        'resources/js/auto-submit.js',
        'resources/js/hide-table-columns.js',
        'resources/js/scrollable-table.js',
        'resources/css/manon.scss',
      ],
      refresh: true,
    }),
  ],
});
