<template>
  <div class="admin-panel min-h-screen bg-stuzha-bg p-6">
    <div class="max-w-7xl mx-auto">
      <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl font-bold text-white">Админ панель</h1>
        <button
          @click="logout"
          class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors"
        >
          Выйти
        </button>
      </div>

      <!-- Вкладки -->
      <div class="mb-8">
        <nav class="flex space-x-1">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="activeTab = tab.key"
            class="px-4 py-2 rounded-lg font-medium transition-colors"
            :class="activeTab === tab.key ? 'bg-stuzha-accent text-white' : 'text-gray-400 hover:text-white'"
          >
            {{ tab.name }}
          </button>
        </nav>
      </div>

      <!-- Контент вкладок -->
      <div class="bg-gray-900/50 rounded-xl p-6 backdrop-blur-sm border border-gray-800">
        <component :is="currentTabComponent" />
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue';
import { useRouter } from 'vue-router';

export default {
  name: 'AdminPanel',
  setup() {
    const router = useRouter();
    const activeTab = ref('products');

    const tabs = [
      { key: 'products', name: 'Товары' },
      { key: 'categories', name: 'Категории' },
      { key: 'themes', name: 'Темы' },
      { key: 'attributes', name: 'Атрибуты' },
      { key: 'quiz', name: 'Квиз' },
      { key: 'marketplace', name: 'Маркетплейсы' }
    ];

    const currentTabComponent = computed(() => {
      // Здесь будут компоненты для каждой вкладки
      return 'div'; // Заглушка
    });

    const logout = () => {
      localStorage.removeItem('auth_token');
      delete axios.defaults.headers.common['Authorization'];
      router.push('/admin/login');
    };

    return {
      activeTab,
      tabs,
      currentTabComponent,
      logout
    };
  }
};
</script>