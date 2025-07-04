<template>
  <div class="admin-panel min-h-screen bg-gray-900 p-6">
    <div class="max-w-7xl mx-auto">
      <!-- Заголовок -->
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
        <nav class="flex space-x-1 overflow-x-auto">
          <button
            v-for="tab in tabs"
            :key="tab.key"
            @click="activeTab = tab.key"
            class="px-4 py-2 rounded-lg font-medium transition-colors whitespace-nowrap"
            :class="activeTab === tab.key ? 'bg-red-600 text-white' : 'text-gray-400 hover:text-white'"
          >
            {{ tab.name }}
          </button>
        </nav>
      </div>

      <!-- Контент вкладок -->
      <div class="bg-gray-800/50 rounded-xl p-6 backdrop-blur-sm border border-gray-700">
        
        <!-- Товары -->
        <div v-if="activeTab === 'products'">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-white">Управление товарами</h2>
            <router-link
              to="/admin/products/create"
              class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
            >
              + Создать товар
            </router-link>
          </div>

          <!-- Поиск и фильтры товаров -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <input
              v-model="productSearch"
              type="text"
              placeholder="Поиск товаров..."
              class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
            />
            <select
              v-model="productThemeFilter"
              class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white focus:border-blue-500 focus:outline-none"
            >
              <option value="">Все темы</option>
              <option v-for="theme in themes" :key="theme.id" :value="theme.id">{{ theme.name }}</option>
            </select>
            <button
              @click="loadProducts(1)"
              class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
            >
              Применить фильтры
            </button>
          </div>

          <!-- Список товаров -->
          <div v-if="loading" class="text-center py-8">
            <div class="text-gray-400">Загрузка товаров...</div>
          </div>

          <div v-else-if="products.length === 0" class="text-center py-8">
            <div class="text-gray-400">Товары не найдены</div>
            <router-link
              to="/admin/products/create"
              class="inline-block mt-4 bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors"
            >
              Создать первый товар
            </router-link>
          </div>

          <div v-else class="space-y-4">
            <div
              v-for="product in products"
              :key="product.id"
              class="bg-gray-800 rounded-lg p-4 flex items-center justify-between hover:bg-gray-700 transition-colors"
            >
              <div class="flex items-center space-x-4">
                <img
                  v-if="product.gallery_images?.[0]"
                  :src="product.gallery_images[0]"
                  :alt="product.name"
                  class="w-16 h-16 object-cover rounded-lg"
                />
                <div class="w-16 h-16 bg-gray-600 rounded-lg flex items-center justify-center" v-else>
                  <span class="text-gray-400 text-xs">Нет фото</span>
                </div>
                
                <div>
                  <h4 class="text-white font-medium">{{ product.name }}</h4>
                  <p class="text-gray-400 text-sm">{{ formatPrice(product.price) }} ₽</p>
                  <p class="text-gray-500 text-xs">Тема: {{ product.theme?.name || 'Не указана' }}</p>
                  <div v-if="product.use_matryoshka" class="mt-1">
                    <span class="bg-purple-600 text-white px-2 py-1 rounded text-xs">Матрёшка</span>
                  </div>
                </div>
              </div>
              
              <div class="flex space-x-2">
                <router-link
                  :to="`/admin/products/edit/${product.id}`"
                  class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm transition-colors"
                >
                  Редактировать
                </router-link>
                <button
                  @click="confirmDelete(product, 'product')"
                  class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm transition-colors"
                >
                  Удалить
                </button>
              </div>
            </div>

            <!-- Пагинация -->
            <div v-if="totalPages > 1" class="flex justify-center space-x-2 mt-6">
              <button
                v-for="page in totalPages"
                :key="page"
                @click="loadProducts(page)"
                class="px-3 py-1 rounded transition-colors"
                :class="productsPagination.current_page === page ? 'bg-red-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'"
              >
                {{ page }}
              </button>
            </div>
          </div>
        </div>

        <!-- Категории -->
        <SimpleList
          v-else-if="activeTab === 'categories'"
          title="Категории"
          :items="categories"
          :loading="loading"
          @create="openCreateSimple('category')"
          @edit="(item) => openEditSimple(item, 'category')"
          @delete="(item) => confirmDelete(item, 'category')"
        />

        <!-- Темы -->
        <SimpleList
          v-else-if="activeTab === 'themes'"
          title="Темы"
          :items="themes"
          :loading="loading"
          @create="openCreateSimple('theme')"
          @edit="(item) => openEditSimple(item, 'theme')"
          @delete="(item) => confirmDelete(item, 'theme')"
        />

        <!-- Атрибуты -->
        <SimpleList
          v-else-if="activeTab === 'attributes'"
          title="Атрибуты"
          :items="attributes"
          :loading="loading"
          @create="openCreateSimple('attribute')"
          @edit="(item) => openEditSimple(item, 'attribute')"
          @delete="(item) => confirmDelete(item, 'attribute')"
        />

        <!-- Правила квиза -->
        <div v-else-if="activeTab === 'quiz'">
          <h2 class="text-2xl font-semibold text-white mb-6">Правила астрологического квиза</h2>
          <p class="text-gray-400 mb-4">Здесь будет управление правилами подбора камней по дате рождения</p>
          <div class="bg-gray-700 rounded-lg p-4">
            <p class="text-gray-300">Функционал в разработке</p>
          </div>
        </div>

        <!-- Маркетплейсы -->
        <div v-else-if="activeTab === 'marketplace'">
          <h2 class="text-2xl font-semibold text-white mb-6">Синхронизация с маркетплейсами</h2>
          <p class="text-gray-400 mb-4">Здесь будет управление синхронизацией с Wildberries, Ozon, Яндекс.Маркет, flowwow</p>
          <div class="bg-gray-700 rounded-lg p-4">
            <p class="text-gray-300">Функционал в разработке</p>
          </div>
        </div>
      </div>
    </div>

    <!-- Модальные окна -->
    <SimpleModal
      v-if="modalOpen"
      :mode="modalMode"
      :entity-type="getEntityTypeName(modalType)"
      :form="simpleForm"
      :loading="loading"
      @save="saveSimple"
      @close="modalOpen = false"
    />

    <ConfirmModal
      v-if="deleteConfirmOpen"
      :item-name="itemToDelete?.item?.name || 'элемент'"
      :loading="loading"
      @confirm="deleteItem"
      @cancel="deleteConfirmOpen = false"
    />
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { axios } from '../main.js';
import SimpleList from '../components/admin/SimpleList.vue';
import SimpleModal from '../components/admin/SimpleModal.vue';
import ConfirmModal from '../components/admin/ConfirmModal.vue';

export default {
  name: 'AdminPanel',
  components: {
    SimpleList,
    SimpleModal,
    ConfirmModal
  },
  setup() {
    const router = useRouter();
    const activeTab = ref('products');
    const loading = ref(false);
    const modalOpen = ref(false);
    const modalMode = ref('create');
    const modalType = ref('category');
    const deleteConfirmOpen = ref(false);
    const itemToDelete = ref(null);

    // Поиск и фильтры
    const productSearch = ref('');
    const productThemeFilter = ref('');

    // Данные
    const products = ref([]);
    const categories = ref([]);
    const themes = ref([]);
    const attributes = ref([]);

    // Форма простых сущностей
    const simpleForm = reactive({
      id: null,
      name: '',
      slug: ''
    });

    // Пагинация
    const productsPagination = reactive({
      current_page: 1,
      per_page: 15,
      total: 0
    });

    // Вкладки
    const tabs = [
      { key: 'products', name: 'Товары' },
      { key: 'categories', name: 'Категории' },
      { key: 'themes', name: 'Темы' },
      { key: 'attributes', name: 'Атрибуты' },
      { key: 'quiz', name: 'Квиз' },
      { key: 'marketplace', name: 'Маркетплейсы' }
    ];

    // Вычисляемые свойства
    const totalPages = computed(() => Math.ceil(productsPagination.total / productsPagination.per_page));

    // Методы загрузки данных
    const loadProducts = async (page = 1) => {
      loading.value = true;
      try {
        const params = {
          page,
          per_page: productsPagination.per_page,
          search: productSearch.value,
          theme_id: productThemeFilter.value
        };
        
        const response = await axios.get('/admin/products', { params });
        products.value = response.data.data || response.data;
        
        if (response.data.current_page) {
          Object.assign(productsPagination, {
            current_page: response.data.current_page,
            total: response.data.total,
            per_page: response.data.per_page
          });
        }
      } catch (error) {
        console.error('Ошибка загрузки товаров:', error);
      } finally {
        loading.value = false;
      }
    };

    const loadCategories = async () => {
      try {
        const response = await axios.get('/admin/categories');
        categories.value = response.data;
      } catch (error) {
        console.error('Ошибка загрузки категорий:', error);
      }
    };

    const loadThemes = async () => {
      try {
        const response = await axios.get('/admin/themes');
        themes.value = response.data;
      } catch (error) {
        console.error('Ошибка загрузки тем:', error);
      }
    };

    const loadAttributes = async () => {
      try {
        const response = await axios.get('/admin/attributes');
        attributes.value = response.data;
      } catch (error) {
        console.error('Ошибка загрузки атрибутов:', error);
      }
    };

    // Методы работы с модальными окнами
    const openCreateSimple = (type) => {
      modalMode.value = 'create';
      modalType.value = type;
      resetSimpleForm();
      modalOpen.value = true;
    };

    const openEditSimple = (item, type) => {
      modalMode.value = 'edit';
      modalType.value = type;
      Object.assign(simpleForm, {
        id: item.id,
        name: item.name,
        slug: item.slug || ''
      });
      modalOpen.value = true;
    };

    // Сохранение простых сущностей
    const saveSimple = async () => {
      loading.value = true;
      try {
        const endpoint = `/admin/${modalType.value}s`;
        
        if (modalMode.value === 'create') {
          await axios.post(endpoint, simpleForm);
        } else {
          await axios.put(`${endpoint}/${simpleForm.id}`, simpleForm);
        }

        modalOpen.value = false;
        
        // Перезагружаем соответствующие данные
        switch (modalType.value) {
          case 'category':
            loadCategories();
            break;
          case 'theme':
            loadThemes();
            break;
          case 'attribute':
            loadAttributes();
            break;
        }
      } catch (error) {
        console.error('Ошибка сохранения:', error);
        alert(error.response?.data?.message || 'Ошибка сохранения');
      } finally {
        loading.value = false;
      }
    };

    // Методы удаления
    const confirmDelete = (item, type) => {
      itemToDelete.value = { item, type };
      deleteConfirmOpen.value = true;
    };

    const deleteItem = async () => {
      if (!itemToDelete.value) return;
      
      loading.value = true;
      try {
        const { item, type } = itemToDelete.value;
        
        switch (type) {
          case 'product':
            await axios.delete(`/admin/products/${item.id}`);
            loadProducts(productsPagination.current_page);
            break;
          case 'category':
            await axios.delete(`/admin/categories/${item.id}`);
            loadCategories();
            break;
          case 'theme':
            await axios.delete(`/admin/themes/${item.id}`);
            loadThemes();
            break;
          case 'attribute':
            await axios.delete(`/admin/attributes/${item.id}`);
            loadAttributes();
            break;
        }
        
        deleteConfirmOpen.value = false;
        itemToDelete.value = null;
      } catch (error) {
        console.error('Ошибка удаления:', error);
        alert(error.response?.data?.message || 'Ошибка удаления');
      } finally {
        loading.value = false;
      }
    };

    // Вспомогательные методы
    const resetSimpleForm = () => {
      Object.assign(simpleForm, {
        id: null,
        name: '',
        slug: ''
      });
    };

    const logout = () => {
      localStorage.removeItem('auth_token');
      delete axios.defaults.headers.common['Authorization'];
      router.push('/admin/login');
    };

    const formatPrice = (price) => {
      return new Intl.NumberFormat('ru-RU').format(price);
    };

    const getEntityTypeName = (type) => {
      const types = {
        category: 'категорию',
        theme: 'тему',
        attribute: 'атрибут'
      };
      return types[type] || type;
    };

    // При монтировании
    onMounted(() => {
      loadProducts();
      loadCategories();
      loadThemes();
      loadAttributes();
    });

    return {
      activeTab,
      loading,
      modalOpen,
      modalMode,
      modalType,
      deleteConfirmOpen,
      itemToDelete,
      productSearch,
      productThemeFilter,
      products,
      categories,
      themes,
      attributes,
      simpleForm,
      productsPagination,
      tabs,
      totalPages,
      loadProducts,
      openCreateSimple,
      openEditSimple,
      saveSimple,
      confirmDelete,
      deleteItem,
      logout,
      formatPrice,
      getEntityTypeName
    };
  }
};
</script>