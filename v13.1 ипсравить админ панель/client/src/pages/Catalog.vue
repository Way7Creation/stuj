<template>
  <div class="catalog min-h-screen bg-stuzha-bg py-20">
    <div class="max-w-7xl mx-auto px-4">
      <!-- Заголовок -->
      <div class="text-center mb-12">
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-6">Каталог украшений</h1>
        <p class="text-xl text-gray-400 max-w-3xl mx-auto">
          Откройте для себя уникальные украшения с натуральными камнями
        </p>
      </div>

      <!-- Фильтры -->
      <div class="mb-8 bg-gray-900/50 rounded-xl p-6 backdrop-blur-sm border border-gray-800">
        <div class="flex flex-wrap items-center gap-4">
          <!-- Поиск -->
          <div class="flex-1 min-w-64">
            <input
              v-model="filters.search"
              @input="onSearchChange"
              type="text"
              placeholder="Поиск украшений..."
              class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white placeholder-gray-400 focus:border-stuzha-accent focus:outline-none"
            >
          </div>

          <!-- Категории -->
          <select
            v-model="filters.category"
            @change="loadProducts"
            class="bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white focus:border-stuzha-accent focus:outline-none"
          >
            <option value="">Все категории</option>
            <option v-for="category in categories" :key="category.id" :value="category.slug">
              {{ category.name }}
            </option>
          </select>

          <!-- Темы -->
          <select
            v-model="filters.theme"
            @change="loadProducts"
            class="bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white focus:border-stuzha-accent focus:outline-none"
          >
            <option value="">Все темы</option>
            <option v-for="theme in themes" :key="theme.id" :value="theme.id">
              {{ theme.name }}
            </option>
          </select>

          <!-- Сортировка -->
          <select
            v-model="filters.sort"
            @change="loadProducts"
            class="bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white focus:border-stuzha-accent focus:outline-none"
          >
            <option value="name">По алфавиту</option>
            <option value="price_asc">Сначала дешевые</option>
            <option value="price_desc">Сначала дорогие</option>
            <option value="newest">Новинки</option>
          </select>
        </div>
      </div>

      <!-- Лоадер -->
      <div v-if="loading" class="flex justify-center items-center py-20">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-stuzha-accent"></div>
      </div>

      <!-- Товары -->
      <div v-else-if="products.length > 0" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        <div
          v-for="product in products"
          :key="product.id"
          class="bg-gray-900/50 rounded-xl overflow-hidden border border-gray-800 group hover:border-stuzha-accent/50 transition-all duration-300 transform hover:scale-105"
        >
          <!-- Изображение товара -->
          <div class="aspect-w-1 aspect-h-1 bg-gradient-to-br from-gray-800 to-gray-900 relative overflow-hidden">
            <img
              v-if="product.gallery_images?.length > 0"
              :src="product.gallery_images[0]"
              :alt="product.name"
              class="w-full h-full object-cover group-hover:scale-110 transition-transform duration-500"
              @error="handleImageError($event)"
            >
            <div v-else class="flex items-center justify-center h-full">
              <p class="text-gray-500 text-center">Изображение<br>отсутствует</p>
            </div>
            
            <!-- Значок матрёшки -->
            <div v-if="product.use_matryoshka" class="absolute top-3 left-3">
              <span class="bg-stuzha-accent text-white text-xs px-2 py-1 rounded-full">
                Эффект матрёшки
              </span>
            </div>
          </div>

          <!-- Информация о товаре -->
          <div class="p-4">
            <h3 class="text-lg font-semibold text-white mb-2 group-hover:text-stuzha-accent transition-colors">
              {{ product.name }}
            </h3>
            
            <p class="text-gray-400 text-sm mb-3 line-clamp-2">
              {{ product.description }}
            </p>

            <!-- Тема и атрибуты -->
            <div class="mb-3">
              <span v-if="product.theme" class="inline-block bg-gray-800 text-gray-300 text-xs px-2 py-1 rounded mr-2">
                {{ product.theme }}
              </span>
              <span
                v-for="attr in product.attributes?.slice(0, 2)"
                :key="attr"
                class="inline-block bg-stuzha-accent/20 text-stuzha-accent text-xs px-2 py-1 rounded mr-1"
              >
                {{ attr }}
              </span>
            </div>

            <!-- Цена и кнопка -->
            <div class="flex justify-between items-center">
              <span class="text-2xl font-bold text-stuzha-accent">
                {{ formatPrice(product.price) }} ₽
              </span>
              <router-link
                :to="`/product/${product.slug}`"
                class="bg-stuzha-accent hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors"
              >
                Подробнее
              </router-link>
            </div>
          </div>
        </div>
      </div>

      <!-- Пустое состояние -->
      <div v-else class="text-center py-20">
        <div class="max-w-md mx-auto">
          <svg class="w-20 h-20 text-gray-600 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
          </svg>
          <h3 class="text-xl font-semibold text-white mb-2">Товары не найдены</h3>
          <p class="text-gray-400 mb-6">
            Попробуйте изменить фильтры или поисковый запрос
          </p>
          <button
            @click="clearFilters"
            class="bg-stuzha-accent hover:bg-red-700 text-white px-6 py-2 rounded-lg transition-colors"
          >
            Сбросить фильтры
          </button>
        </div>
      </div>

      <!-- Пагинация -->
      <div v-if="pagination.total > pagination.per_page" class="mt-12 flex justify-center">
        <nav class="flex items-center space-x-2">
          <button
            @click="changePage(pagination.current_page - 1)"
            :disabled="pagination.current_page <= 1"
            class="px-3 py-2 text-sm bg-gray-800 text-white rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-700 transition-colors"
          >
            Назад
          </button>
          
          <span class="px-4 py-2 text-sm text-gray-400">
            Страница {{ pagination.current_page }} из {{ Math.ceil(pagination.total / pagination.per_page) }}
          </span>
          
          <button
            @click="changePage(pagination.current_page + 1)"
            :disabled="pagination.current_page >= Math.ceil(pagination.total / pagination.per_page)"
            class="px-3 py-2 text-sm bg-gray-800 text-white rounded-lg disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-700 transition-colors"
          >
            Далее
          </button>
        </nav>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, onMounted, watch } from 'vue';
import { axios } from '../main.js';

export default {
  name: 'Catalog',
  setup() {
    const loading = ref(false);
    const products = ref([]);
    const categories = ref([]);
    const themes = ref([]);
    
    const filters = reactive({
      search: '',
      category: '',
      theme: '',
      sort: 'name'
    });

    const pagination = reactive({
      current_page: 1,
      per_page: 12,
      total: 0
    });

    let searchTimeout = null;

    // Форматирование цены
    const formatPrice = (price) => {
      return new Intl.NumberFormat('ru-RU').format(price);
    };

    // Обработка ошибки загрузки изображения
    const handleImageError = (event) => {
      event.target.src = '/images/placeholder.jpg';
    };

    // Загрузка категорий и тем
    const loadFilters = async () => {
      try {
        const [categoriesRes, themesRes] = await Promise.all([
          axios.get('/admin/categories'),
          axios.get('/admin/themes')
        ]);
        categories.value = categoriesRes.data;
        themes.value = themesRes.data;
      } catch (error) {
        console.error('Ошибка загрузки фильтров:', error);
      }
    };

    // Загрузка товаров
    const loadProducts = async (page = 1) => {
      loading.value = true;
      try {
        const params = new URLSearchParams({
          page,
          per_page: pagination.per_page,
          ...filters
        });

        const response = await axios.get(`/catalog?${params}`);
        products.value = response.data.data;
        
        // Обновляем пагинацию
        Object.assign(pagination, response.data.meta);
        
      } catch (error) {
        console.error('Ошибка загрузки товаров:', error);
        products.value = [];
      } finally {
        loading.value = false;
      }
    };

    // Поиск с задержкой
    const onSearchChange = () => {
      clearTimeout(searchTimeout);
      searchTimeout = setTimeout(() => {
        pagination.current_page = 1;
        loadProducts();
      }, 500);
    };

    // Смена страницы
    const changePage = (page) => {
      if (page >= 1 && page <= Math.ceil(pagination.total / pagination.per_page)) {
        pagination.current_page = page;
        loadProducts(page);
        window.scrollTo({ top: 0, behavior: 'smooth' });
      }
    };

    // Сброс фильтров
    const clearFilters = () => {
      Object.assign(filters, {
        search: '',
        category: '',
        theme: '',
        sort: 'name'
      });
      pagination.current_page = 1;
      loadProducts();
    };

    // Отслеживание изменений фильтров
    watch([() => filters.category, () => filters.theme, () => filters.sort], () => {
      pagination.current_page = 1;
      loadProducts();
    });

    onMounted(() => {
      loadFilters();
      loadProducts();
    });

    return {
      loading,
      products,
      categories,
      themes,
      filters,
      pagination,
      formatPrice,
      handleImageError,
      loadProducts,
      onSearchChange,
      changePage,
      clearFilters
    };
  }
};
</script>

<style scoped>
.line-clamp-2 {
  display: -webkit-box;
  -webkit-line-clamp: 2;
  -webkit-box-orient: vertical;
  overflow: hidden;
}

.aspect-w-1 {
  position: relative;
  width: 100%;
  padding-bottom: 100%;
}

.aspect-h-1 {
  position: absolute;
  height: 100%;
  width: 100%;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
}
</style>