/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/client/src/main.js
| Описание: Главный файл Vue.js приложения для проекта Стужа
|--------------------------------------------------------------------------
*/

import { createApp } from 'vue'
import { createRouter, createWebHistory } from 'vue-router'
import axios from 'axios'
import App from './App.vue'

// Импорт стилей
import './assets/css/app.css'

// Импорт страниц
import Home from './pages/Home.vue'
import Catalog from './pages/Catalog.vue'
import Product from './pages/Product.vue'
import Quiz from './pages/Quiz.vue'
import AdminLogin from './pages/AdminLogin.vue'
import AdminPanel from './pages/AdminPanel.vue'

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

// Роуты
const routes = [
    {
        path: '/',
        name: 'home',
        component: Home,
        meta: { title: 'Стужа - Уникальные украшения' }
    },
    {
        path: '/catalog',
        name: 'catalog',
        component: Catalog,
        meta: { title: 'Каталог украшений - Стужа' }
    },
    {
        path: '/product/:slug',
        name: 'product',
        component: Product,
        meta: { title: 'Товар - Стужа' }
    },
    {
        path: '/quiz',
        name: 'quiz',
        component: Quiz,
        meta: { title: 'Подбор украшения - Стужа' }
    },
    {
        path: '/admin/login',
        name: 'admin-login',
        component: AdminLogin,
        meta: { title: 'Вход в админ-панель', hideNavigation: true }
    },
    {
        path: '/admin',
        name: 'admin',
        component: AdminPanel,
        meta: { 
            title: 'Админ-панель - Стужа',
            requiresAuth: true,
            hideNavigation: true
        }
    }
]

// Создание роутера
const router = createRouter({
    history: createWebHistory(),
    routes,
    scrollBehavior(to, from, savedPosition) {
        if (savedPosition) {
            return savedPosition
        } else {
            return { top: 0 }
        }
    }
})

// Middleware для проверки авторизации
router.beforeEach((to, from, next) => {
    // Установка заголовка страницы
    document.title = to.meta.title || 'Стужа'
    
    // Проверка авторизации для админских страниц
    if (to.meta.requiresAuth) {
        const token = localStorage.getItem('auth_token')
        if (!token) {
            next('/admin/login')
            return
        }
    }
    
    next()
})

// Создание приложения
const app = createApp(App)

// Использование роутера
app.use(router)

// Глобальные свойства
app.config.globalProperties.$http = axios

// Монтирование приложения
app.mount('#app')