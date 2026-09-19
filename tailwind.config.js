import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

/** @type {import('tailwindcss').Config} */
export default {
    darkMode: 'class',
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
    ],

    theme: {
        extend: {
            fontFamily: {
                sans: ['Inter', ...defaultTheme.fontFamily.sans],
                body: ['Inter', 'system-ui', 'sans-serif'],
            },
            colors: {
                mist: {
                    50: '#f9fbfb',
                    100: '#f1f3f3',
                    200: '#e3e7e8',
                    300: '#d0d6d8',
                    400: '#9ca8ab',
                    500: '#67787c',
                    600: '#4b585b',
                    700: '#394447',
                    800: '#22292b',
                    900: '#161b1d',
                    950: '#090b0c',
                },
            },
        },
    },

    plugins: [forms],
};
