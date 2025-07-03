import { createRouter, createWebHistory } from 'vue-router';

// Импорт страниц (будут созданы на этапе 7)
const Home = () => import('../pages/Home.vue');
const Catalog = () => import('../pages/Catalog.vue');
const Product = () => import('../pages/Product.vue');
const Quiz = () => import('../pages/Quiz.vue');
const AdminLogin = () => import('../pages/AdminLogin.vue');
const AdminPanel = () => import('../pages/AdminPanel.vue');

const routes = [
  {
    path: '/',
    name: 'home',
    component: Home,
    meta: { title: 'Главная' }
  },
  {
    path: '/catalog',
    name: 'catalog', 
    component: Catalog,
    meta: { title: 'Каталог' }
  },
  {
    path: '/product/:slug',
    name: 'product',
    component: Product,
    meta: { title: 'Товар' }
  },
  {
    path: '/quiz',
    name: 'quiz',
    component: Quiz,
    meta: { title: 'Подбор украшения' }
  },
  {
    path: '/admin/login',
    name: 'admin-login',
    component: AdminLogin,
    meta: { title: 'Вход в админку' }
  },
  {
    path: '/admin',
    name: 'admin',
    component: AdminPanel,
    meta: { 
      title: 'Админ панель',
      requiresAuth: true 
    }
  },
  // 404 страница
  {
    path: '/:pathMatch(.*)*',
    name: 'not-found',
    component: () => import('../pages/NotFound.vue'),
    meta: { title: '404 - Страница не найдена' }
  }
];

const router = createRouter({
  history: createWebHistory(),
  routes,
  scrollBehavior(to, from, savedPosition) {
    if (savedPosition) {
      return savedPosition;
    } else {
      return { top: 0 };
    }
  }
});

// Навигационный guard для проверки авторизации
router.beforeEach((to, from, next) => {
  // Обновление заголовка страницы
  document.title = `${to.meta.title || 'Страница'} - Стужа`;
  
  // Проверка авторизации для защищенных маршрутов
  if (to.meta.requiresAuth) {
    const token = localStorage.getItem('auth_token');
    if (!token) {
      next({ name: 'admin-login' });
    } else {
      next();
    }
  } else {
    next();
  }
});

export default router;