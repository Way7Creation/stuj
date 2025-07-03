<template>
  <div id="app" class="min-h-screen bg-stuzha-bg text-stuzha-text">
    <!-- Основная навигация -->
    <nav v-if="!isAdminRoute" class="sticky top-0 z-50 bg-black/90 backdrop-blur-sm border-b border-white/10">
      <div class="container mx-auto px-4">
        <div class="flex items-center justify-between h-16">
          <!-- Логотип -->
          <router-link to="/" class="flex items-center space-x-2">
            <span class="text-2xl font-bold tracking-wider">СТУЖА</span>
          </router-link>
          
          <!-- Навигационное меню -->
          <div class="hidden md:flex items-center space-x-8">
            <router-link 
              to="/catalog" 
              class="hover:text-stuzha-accent transition-colors duration-300"
              :class="{ 'text-stuzha-accent': $route.name === 'catalog' }"
            >
              Каталог
            </router-link>
            <router-link 
              to="/quiz" 
              class="hover:text-stuzha-accent transition-colors duration-300"
              :class="{ 'text-stuzha-accent': $route.name === 'quiz' }"
            >
              Подбор украшения
            </router-link>
            <a 
              href="https://t.me/stuzha_bot" 
              target="_blank" 
              class="bg-stuzha-accent text-white px-4 py-2 rounded-lg hover:bg-red-600 transition-colors duration-300"
            >
              Telegram
            </a>
          </div>
          
          <!-- Мобильное меню -->
          <button 
            @click="mobileMenuOpen = !mobileMenuOpen"
            class="md:hidden p-2"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path 
                v-if="!mobileMenuOpen"
                stroke-linecap="round" 
                stroke-linejoin="round" 
                stroke-width="2" 
                d="M4 6h16M4 12h16M4 18h16"
              />
              <path 
                v-else
                stroke-linecap="round" 
                stroke-linejoin="round" 
                stroke-width="2" 
                d="M6 18L18 6M6 6l12 12"
              />
            </svg>
          </button>
        </div>
        
        <!-- Мобильное меню (выпадающее) -->
        <div 
          v-if="mobileMenuOpen" 
          class="md:hidden py-4 border-t border-white/10"
        >
          <router-link 
            to="/catalog" 
            class="block py-2 hover:text-stuzha-accent transition-colors"
            @click="mobileMenuOpen = false"
          >
            Каталог
          </router-link>
          <router-link 
            to="/quiz" 
            class="block py-2 hover:text-stuzha-accent transition-colors"
            @click="mobileMenuOpen = false"
          >
            Подбор украшения
          </router-link>
          <a 
            href="https://t.me/stuzha_bot" 
            target="_blank" 
            class="inline-block mt-4 bg-stuzha-accent text-white px-4 py-2 rounded-lg"
          >
            Telegram
          </a>
        </div>
      </div>
    </nav>
    
    <!-- Основной контент -->
    <main class="flex-1">
      <router-view v-slot="{ Component }">
        <transition name="fade" mode="out-in">
          <component :is="Component" />
        </transition>
      </router-view>
    </main>
    
    <!-- Футер (не показывается в админке) -->
    <footer v-if="!isAdminRoute" class="bg-black/50 border-t border-white/10 mt-16">
      <div class="container mx-auto px-4 py-8">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <!-- О магазине -->
          <div>
            <h3 class="text-lg font-semibold mb-4">О магазине</h3>
            <p class="text-sm text-gray-400">
              Стужа - уникальные украшения с натуральными камнями. 
              Каждое изделие создано с любовью и вниманием к деталям.
            </p>
          </div>
          
          <!-- Контакты -->
          <div>
            <h3 class="text-lg font-semibold mb-4">Контакты</h3>
            <div class="space-y-2 text-sm text-gray-400">
              <p>Telegram: @stuzha_bot</p>
              <p>Email: info@stuj.ru</p>
            </div>
          </div>
          
          <!-- Маркетплейсы -->
          <div>
            <h3 class="text-lg font-semibold mb-4">Мы на маркетплейсах</h3>
            <div class="space-y-2 text-sm">
              <a href="#" class="block hover:text-stuzha-accent transition-colors">Wildberries</a>
              <a href="#" class="block hover:text-stuzha-accent transition-colors">Ozon</a>
              <a href="#" class="block hover:text-stuzha-accent transition-colors">Яндекс.Маркет</a>
              <a href="#" class="block hover:text-stuzha-accent transition-colors">flowwow</a>
            </div>
          </div>
        </div>
        
        <div class="mt-8 pt-8 border-t border-white/10 text-center text-sm text-gray-400">
          <p>&copy; 2025 Стужа. Все права защищены.</p>
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
</style>