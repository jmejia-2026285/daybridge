import defaultTheme from 'tailwindcss/defaultTheme';
import forms from '@tailwindcss/forms';

const token = (name) => `rgb(var(${name}) / <alpha-value>)`;

/** @type {import('tailwindcss').Config} */
export default {
    content: [
        './vendor/laravel/framework/src/Illuminate/Pagination/resources/views/*.blade.php',
        './storage/framework/views/*.php',
        './resources/views/**/*.blade.php',
        './resources/js/**/*.js',
    ],

    darkMode: 'class',

    theme: {
        extend: {
            colors: {
                canvas: token('--canvas'),
                surface: token('--surface'),
                sunken: token('--sunken'),
                inverse: token('--inverse'),

                ink: {
                    DEFAULT: token('--ink'),
                    secondary: token('--ink-secondary'),
                    muted: token('--ink-muted'),
                    disabled: token('--ink-disabled'),
                    inverse: token('--ink-inverse'),
                },

                line: {
                    DEFAULT: token('--line'),
                    strong: token('--line-strong'),
                },

                brand: {
                    DEFAULT: token('--brand'),
                    soft: token('--brand-soft'),
                    strong: token('--brand-strong'),
                    foreground: token('--brand-foreground'),
                },

                accent: {
                    DEFAULT: token('--accent'),
                    soft: token('--accent-soft'),
                    strong: token('--accent-strong'),
                    foreground: token('--accent-foreground'),
                },

                success: {
                    DEFAULT: token('--success'),
                    soft: token('--success-soft'),
                },

                warning: {
                    DEFAULT: token('--warning'),
                    soft: token('--warning-soft'),
                },

                danger: {
                    DEFAULT: token('--danger'),
                    soft: token('--danger-soft'),
                },

                info: {
                    DEFAULT: token('--info'),
                    soft: token('--info-soft'),
                },

                signal: {
                    50: '#FFF3ED',
                    100: '#FFE1D2',
                    200: '#FFC2A8',
                    300: '#FF9B73',
                    400: '#FF7A45',
                    500: '#FF5A1F',
                    600: '#E8480F',
                    700: '#C03A0B',
                    800: '#98300C',
                    900: '#7A2A0E',
                },

                petrol: {
                    50: '#EFF7F5',
                    100: '#D7EBE7',
                    200: '#AFD7D0',
                    300: '#7DBBB2',
                    400: '#4E9A90',
                    500: '#2E7D74',
                    600: '#1F635C',
                    700: '#174F4A',
                    800: '#0E3B3A',
                    900: '#0A2B2A',
                    950: '#061B1B',
                },

                bone: {
                    50: '#F8F6F1',
                    100: '#EFECE5',
                    200: '#E2DED5',
                    300: '#CFCBC2',
                    400: '#A9A69D',
                    500: '#7F7D75',
                    600: '#5F5E58',
                    700: '#45443F',
                    800: '#2C2C29',
                    900: '#1A1A18',
                    950: '#0F0F0E',
                },
            },

            fontFamily: {
                sans: ['Archivo', ...defaultTheme.fontFamily.sans],
                display: ['Archivo', ...defaultTheme.fontFamily.sans],
            },

            fontSize: {
                '3xs': ['0.625rem', { lineHeight: '0.875rem', letterSpacing: '0.04em' }],
                '2xs': ['0.6875rem', { lineHeight: '1rem', letterSpacing: '0.06em' }],
                xs: ['0.75rem', { lineHeight: '1.125rem' }],
                sm: ['0.875rem', { lineHeight: '1.375rem' }],
                base: ['1rem', { lineHeight: '1.625rem' }],
                lg: ['1.125rem', { lineHeight: '1.75rem' }],
                xl: ['1.25rem', { lineHeight: '1.75rem' }],
                '2xl': ['1.5rem', { lineHeight: '1.9rem' }],
                '3xl': ['1.875rem', { lineHeight: '2.2rem', letterSpacing: '-0.015em' }],
                '4xl': ['2.25rem', { lineHeight: '2.5rem', letterSpacing: '-0.02em' }],
                '5xl': ['2.75rem', { lineHeight: '2.9rem', letterSpacing: '-0.025em' }],
                '6xl': ['3.5rem', { lineHeight: '3.6rem', letterSpacing: '-0.03em' }],
                '7xl': ['4.5rem', { lineHeight: '4.4rem', letterSpacing: '-0.035em' }],
                display: ['clamp(2.75rem, 6vw, 4.5rem)', { lineHeight: '0.95', letterSpacing: '-0.035em', fontWeight: '900' }],
            },

            letterSpacing: {
                label: '0.08em',
                display: '-0.035em',
            },

            spacing: {
                '4.5': '1.125rem',
                13: '3.25rem',
                15: '3.75rem',
                18: '4.5rem',
                22: '5.5rem',
                26: '6.5rem',
                30: '7.5rem',
                rail: '4rem',
                section: 'clamp(4rem, 8vw, 7rem)',
            },

            maxWidth: {
                content: '77.5rem',
                prose: '68ch',
                narrow: '44rem',
            },

            gridTemplateColumns: {
                page: 'repeat(12, minmax(0, 1fr))',
            },

            borderRadius: {
                xs: '0.125rem',
                sm: '0.25rem',
                md: '0.375rem',
                lg: '0.5rem',
                xl: '0.75rem',
            },

            boxShadow: {
                overlay: '0 32px 64px -32px rgb(16 24 32 / 0.55)',
            },

            transitionDuration: {
                fast: '120ms',
                base: '180ms',
                slow: '240ms',
            },

            transitionTimingFunction: {
                signal: 'cubic-bezier(0.2, 0, 0, 1)',
                out: 'cubic-bezier(0.16, 1, 0.3, 1)',
            },

            zIndex: {
                rail: '30',
                nav: '40',
                overlay: '50',
                modal: '60',
                toast: '70',
            },

            keyframes: {
                now: {
                    '0%, 100%': { opacity: '1' },
                    '50%': { opacity: '0.35' },
                },
            },

            animation: {
                now: 'now 2.4s cubic-bezier(0.4, 0, 0.6, 1) infinite',
            },
        },
    },

    plugins: [forms],
};
