import forms from "@tailwindcss/forms";
import typography from "@tailwindcss/typography";
import PrimeUI from "tailwindcss-primeui";

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        "./vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php",
        "./vendor/laravel/jetstream/**/*.blade.php",
        "./storage/framework/views/*.php",
        "./resources/views/**/*.blade.php",
        "./resources/js/**/*.{vue,js,ts,jsx,tsx}",
        "./resources/external/sakai-vue/**/*.{vue,js,ts,jsx,tsx}",
    ],
    darkMode: ["selector", '[class*="app-dark"]'],
    theme: {
        screens: {
            sm: "576px",
            md: "768px",
            lg: "992px",
            xl: "1200px",
            "2xl": "1920px",
        },
        extend: {
            colors: {
                'bg-surface-card': 'var(--surface-card)',
            }
        },
    },
    plugins: [forms, typography, PrimeUI],
};
