import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.vue',
        './resources/js/**/*.js',
    ],

    theme: {
        extend: {
            colors: {
                primary: {
                    DEFAULT: 'var(--color-primary)',
                    hover: 'var(--color-primary-hover)',
                },
                dark: 'var(--color-dark)',
                surface: 'var(--color-surface)',
                border: 'var(--color-border)',
                whatsapp: 'var(--color-whatsapp)',
            },
            fontFamily: {
                sans: ['var(--font-sans)', ...defaultTheme.fontFamily.sans],
            },
            borderRadius: {
                base: 'var(--radius-base)',
                card: 'var(--radius-card)',
            },
        },
    },

    plugins: [forms],
};
