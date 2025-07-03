// Импорт стилей
import './assets/css/app.css';

// Vue и основные библиотеки
import { createApp } from 'vue';
import App from './App.vue';
import router from './router';
import axios from 'axios';

// Настройка axios
axios.defaults.baseURL = '/api';
axios.defaults.headers.common['X-Requested-With'] = 'XMLHttpRequest';
axios.defaults.headers.common['Accept'] = 'application/json';

// CSRF токен для Laravel
const token = document.head.querySelector('meta[name="csrf-token"]');
if (token) {
  axios.defaults.headers.common['X-CSRF-TOKEN'] = token.content;
} else {
  console.error('CSRF token not found');
}

// Интерцептор для обработки ошибок
axios.interceptors.response.use(
  response => response,
  error => {
    if (error.response?.status === 401) {
      // Перенаправление на страницу входа при ошибке авторизации
      router.push('/admin/login');
    }
    return Promise.reject(error);
  }
);

// Создание и монтирование приложения
const app = createApp(App);

// Глобальные свойства
app.config.globalProperties.$axios = axios;

// Глобальные компоненты можно регистрировать здесь
// app.component('ComponentName', Component);

// Монтирование приложения
app.use(router).mount('#app');

// Обработка ошибок Vue
app.config.errorHandler = (err, vm, info) => {
  console.error('Vue Error:', err, info);
};

// Экспорт для использования в других модулях
export { axios };