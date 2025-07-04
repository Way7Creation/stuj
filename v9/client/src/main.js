/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/client/src/main.js
| Описание: Главный файл Vue.js приложения для проекта Стужа
|--------------------------------------------------------------------------
*/

import { createApp } from 'vue'
import axios from 'axios'
import App from './App.vue'
import router from './router/index.js' // ← ТОЛЬКО импорт роутера

// Импорт стилей
import './assets/css/app.css'

// Настройка Axios
axios.defaults.baseURL = window.APP_CONFIG?.apiUrl || '/api'
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest'
axios.defaults.headers.common['Accept'] = 'application/json'

// Добавляем токен из localStorage если есть
const token = localStorage.getItem('auth_token')
if (token) {
    axios.defaults.headers.common['Authorization'] = `Bearer ${token}`
}

// Интерцептор для обработки 401 ошибок
axios.interceptors.response.use(
    response => response,
    error => {
        if (error.response?.status === 401) {
            localStorage.removeItem('auth_token')
            delete axios.defaults.headers.common['Authorization']
            
            // Перенаправляем на логин, если не на публичных страницах
            const currentPath = window.location.pathname
            if (currentPath.startsWith('/admin') && currentPath !== '/admin/login') {
                window.location.href = '/admin/login'
            }
        }
        return Promise.reject(error)
    }
)

// Экспортируем axios для использования в компонентах
export { axios }

// Создание приложения
const app = createApp(App)

// Использование роутера
app.use(router)

// Глобальные свойства
app.config.globalProperties.$http = axios

// Монтирование приложения
app.mount('#app')