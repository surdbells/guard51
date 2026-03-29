/** @type {import('tailwindcss').Config} */
module.exports = {
  content: [
    "./src/**/*.{html,ts}",
  ],
  darkMode: 'class',
  theme: {
    extend: {
      colors: {
        brand: {
          50: '#EEF2F7', 100: '#D5DFE9', 200: '#AABFD4', 300: '#7F9FBE',
          400: '#547FA9', 500: '#1B3A5C', 600: '#163050', 700: '#112644',
          800: '#0C1C38', 900: '#07122C',
        },
        accent: {
          50: '#FEF3E8', 100: '#FDDFC5', 200: '#FBBF8B', 300: '#F99F51',
          400: '#F08030', 500: '#E8792D', 600: '#C06224', 700: '#984C1B',
        },
      },
      fontFamily: {
        sans: ['"DM Sans"', 'system-ui', '-apple-system', 'sans-serif'],
        mono: ['"JetBrains Mono"', '"Fira Code"', 'monospace'],
      },
      spacing: {
        'sidebar': '260px',
        'sidebar-collapsed': '72px',
        'header': '64px',
        'mobile-nav': '64px',
      },
      borderRadius: {
        'card': '12px',
        'button': '8px',
        'input': '8px',
        'badge': '6px',
      },
      boxShadow: {
        'card': '0 1px 3px rgba(0,0,0,.06), 0 1px 2px rgba(0,0,0,.04)',
        'card-hover': '0 4px 12px rgba(0,0,0,.08), 0 2px 4px rgba(0,0,0,.04)',
        'dropdown': '0 10px 25px rgba(0,0,0,.1), 0 4px 10px rgba(0,0,0,.05)',
        'modal': '0 20px 60px rgba(0,0,0,.15), 0 8px 20px rgba(0,0,0,.1)',
      },
      transitionDuration: {
        'fast': '150ms',
        'normal': '250ms',
        'slow': '400ms',
      },
    },
  },
  plugins: [],
};
