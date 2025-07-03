/** @type {import('tailwindcss').Config} */
export default {
  content: [
    "./client/src/**/*.{vue,js,jsx}",
    "./resources/views/**/*.blade.php",
  ],
  theme: {
    extend: {
      colors: {
        // Цвета из дизайн-системы (ОБЯЗАТЕЛЬНЫЕ)
        'stuzha-bg': '#121212',
        'stuzha-text': '#FFFFFF',
        'stuzha-accent': '#E63946',
      },
      animation: {
        'rotate-slow': 'rotate 20s linear infinite',
        'rotate-medium': 'rotate 15s linear infinite',
        'slide-up': 'slideUp 0.5s ease-out',
      },
      keyframes: {
        rotate: {
          '0%': { transform: 'rotate(0deg)' },
          '100%': { transform: 'rotate(360deg)' },
        },
        slideUp: {
          '0%': { transform: 'translateY(100%)' },
          '100%': { transform: 'translateY(0)' },
        },
      },
      screens: {
        // Responsive точки из документации
        'mobile': { 'max': '640px' },
        'tablet': { 'min': '641px', 'max': '1024px' },
        'desktop': { 'min': '1025px' },
      },
    },
  },
  plugins: [
    require('@tailwindcss/forms'),
  ],
}