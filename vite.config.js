import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { bunny } from 'laravel-vite-plugin/fonts';
import tailwindcss from '@tailwindcss/vite';

export default defineConfig({
    plugins: [
        laravel({
            input: [
                'resources/css/app.css',
                'resources/js/app.js',
                // 管理画面（office）。Tailwind + Flowbiteで完結させる。
                'resources/css/office.css',
                'resources/js/office.js',
                // ページ固有のJS。必要なページだけが読み込むよう個別のエントリーにする
                // （officeに全部入れると、使わないページでも実行されて要素が無いと落ちる）。
                'resources/js/office/budget.js',
                'resources/js/office/password-manager.js',
                'resources/js/office/profile.js',
                'resources/js/office/role-routes.js',
                'resources/js/office/secrets-gallery.js',
                'resources/js/office/secrets-upload.js',
                'resources/js/office/wedding-rsvp-form.js',
                // 招待状サイト（wedding）。管理画面とはテーマが別なのでバンドルも分ける。
                'resources/css/wedding.css',
                'resources/js/wedding.js',
            ],
            refresh: true,
            fonts: [
                bunny('Instrument Sans', {
                    weights: [400, 500, 600],
                }),
                bunny('Shippori Mincho', {
                    weights: [400, 500, 600, 700],
                }),
            ],
        }),
        tailwindcss(),
    ],
    server: {
        watch: {
            ignored: ['**/storage/framework/views/**'],
        },
    },
});
