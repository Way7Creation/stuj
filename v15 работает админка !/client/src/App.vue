<template>
  <div id="app" class="min-h-screen bg-stuzha-bg text-stuzha-text">
    <!-- Навигация (только если не админка) -->
    <nav v-if="!isAdminRoute" class="bg-black/90 backdrop-blur-sm border-b border-gray-800 sticky top-0 z-50">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <!-- Логотип -->
          <div class="flex items-center">
            <router-link to="/" class="text-2xl font-bold text-white hover:text-stuzha-accent transition-colors">
              Стужа
            </router-link>
          </div>

          <!-- Навигация для десктопа -->
          <div class="hidden md:block">
            <div class="ml-10 flex items-baseline space-x-4">
              <router-link
                to="/"
                class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors"
                :class="{ 'text-stuzha-accent': $route.name === 'home' }"
              >
                Главная
              </router-link>
              <router-link
                to="/catalog"
                class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors"
                :class="{ 'text-stuzha-accent': $route.name === 'catalog' }"
              >
                Каталог
              </router-link>
              <router-link
                to="/quiz"
                class="text-gray-300 hover:text-white px-3 py-2 rounded-md text-sm font-medium transition-colors"
                :class="{ 'text-stuzha-accent': $route.name === 'quiz' }"
              >
                Подбор украшения
              </router-link>
            </div>
          </div>

          <!-- Мобильное меню кнопка -->
          <div class="md:hidden">
            <button
              @click="mobileMenuOpen = !mobileMenuOpen"
              class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-white hover:bg-gray-700 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-white"
            >
              <svg
                class="h-6 w-6"
                :class="{ 'hidden': mobileMenuOpen, 'block': !mobileMenuOpen }"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
              </svg>
              <svg
                class="h-6 w-6"
                :class="{ 'block': mobileMenuOpen, 'hidden': !mobileMenuOpen }"
                xmlns="http://www.w3.org/2000/svg"
                fill="none"
                viewBox="0 0 24 24"
                stroke="currentColor"
              >
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
              </svg>
            </button>
          </div>
        </div>
      </div>

      <!-- Мобильное меню -->
      <div v-show="mobileMenuOpen" class="md:hidden">
        <div class="px-2 pt-2 pb-3 space-y-1 sm:px-3 bg-black/95">
          <router-link
            to="/"
            @click="mobileMenuOpen = false"
            class="text-gray-300 hover:text-white block px-3 py-2 rounded-md text-base font-medium transition-colors"
            :class="{ 'text-stuzha-accent bg-gray-800': $route.name === 'home' }"
          >
            Главная
          </router-link>
          <router-link
            to="/catalog"
            @click="mobileMenuOpen = false"
            class="text-gray-300 hover:text-white block px-3 py-2 rounded-md text-base font-medium transition-colors"
            :class="{ 'text-stuzha-accent bg-gray-800': $route.name === 'catalog' }"
          >
            Каталог
          </router-link>
          <router-link
            to="/quiz"
            @click="mobileMenuOpen = false"
            class="text-gray-300 hover:text-white block px-3 py-2 rounded-md text-base font-medium transition-colors"
            :class="{ 'text-stuzha-accent bg-gray-800': $route.name === 'quiz' }"
          >
            Подбор украшения
          </router-link>
        </div>
      </div>
    </nav>

    <!-- Основной контент -->
    <main class="min-h-screen">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>

    <!-- Футер (только если не админка) -->
    <footer v-if="!isAdminRoute" class="bg-black border-t border-gray-800 mt-20">
      <div class="max-w-7xl mx-auto py-12 px-4 sm:px-6 lg:px-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <!-- О нас -->
          <div>
            <h3 class="text-lg font-semibold text-white mb-4">О Стуже</h3>
            <p class="text-gray-400 text-sm">
              Уникальные украшения с натуральными камнями. Подбираем украшения по астрологическим расчетам для гармонии и стиля.
            </p>
          </div>

          <!-- Быстрые ссылки -->
          <div>
            <h3 class="text-lg font-semibold text-white mb-4">Навигация</h3>
            <ul class="space-y-2">
              <li><router-link to="/" class="text-gray-400 hover:text-white text-sm transition-colors">Главная</router-link></li>
              <li><router-link to="/catalog" class="text-gray-400 hover:text-white text-sm transition-colors">Каталог</router-link></li>
              <li><router-link to="/quiz" class="text-gray-400 hover:text-white text-sm transition-colors">Подбор украшения</router-link></li>
            </ul>
          </div>

          <!-- Контакты -->
          <div>
            <h3 class="text-lg font-semibold text-white mb-4">Связь с нами</h3>
            <p class="text-gray-400 text-sm mb-2">Telegram: @stuzha_bot</p>
            <p class="text-gray-400 text-sm">Email: info@stuj.ru</p>
          </div>
        </div>

        <div class="mt-8 pt-8 border-t border-gray-800">
          <p class="text-center text-gray-400 text-sm">
            © 2025 Стужа. Все права защищены.
          </p>
        </div>
      </div>
    </footer>
  </div>
</template>

<script>
import { ref, computed } from 'vue';
import { useRoute } from 'vue-router';

export default {
  name: 'App',
  setup() {
    const route = useRoute();
    const mobileMenuOpen = ref(false);
    
    // Проверка, находимся ли мы в админке
    const isAdminRoute = computed(() => {
      return route.path.startsWith('/admin');
    });
    
    return {
      mobileMenuOpen,
      isAdminRoute
    };
  }
};
</script>

<style>
/* Анимация перехода между страницами */
.fade-enter-active,
.fade-leave-active {
  transition: opacity 0.3s ease;
}

.fade-enter-from,
.fade-leave-to {
  opacity: 0;
}

/* Кастомный скроллбар */
::-webkit-scrollbar {
  width: 8px;
  height: 8px;
}

::-webkit-scrollbar-track {
  background: #1a1a1a;
}

::-webkit-scrollbar-thumb {
  background: #444;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #555;
}

/* Цвета для Стужи */
:root {
  --stuzha-bg: #121212;
  --stuzha-text: #ffffff;
  --stuzha-accent: #e63946;
}
</style>