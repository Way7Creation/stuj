<template>
  <div class="admin-panel min-h-screen bg-gray-900 p-6">
    <div class="max-w-7xl mx-auto">
      <!-- Заголовок с статистикой -->
      <div class="flex justify-between items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-white">Админ-панель "Стужа"</h1>
          <p class="text-gray-400 mt-1">Управление интернет-магазином украшений</p>
        </div>
        
        <!-- Статистика -->
        <div class="hidden lg:flex space-x-4">
          <div class="bg-gray-800 rounded-lg p-4 text-center" v-if="stats">
            <div class="text-2xl font-bold text-blue-400">{{ stats.products?.total || 0 }}</div>
            <div class="text-gray-400 text-sm">Товаров</div>
          </div>
          <div class="bg-gray-800 rounded-lg p-4 text-center" v-if="stats">
            <div class="text-2xl font-bold text-green-400">{{ stats.categories || 0 }}</div>
            <div class="text-gray-400 text-sm">Категорий</div>
          </div>
          <div class="bg-gray-800 rounded-lg p-4 text-center" v-if="stats">
            <div class="text-2xl font-bold text-purple-400">{{ stats.products?.matryoshka || 0 }}</div>
            <div class="text-gray-400 text-sm">Матрёшек</div>
          </div>
        </div>
        
        <button @click="logout" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors">
          Выйти
        </button>
      </div>

      <!-- Навигация -->
      <div class="bg-gray-800 rounded-lg p-2 mb-8">
        <nav class="flex flex-wrap space-x-1">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            :class="activeTab === tab.id ? 'bg-stuzha-accent text-white' : 'text-gray-300 hover:text-white hover:bg-gray-700'"
          >
            {{ tab.name }}
          </button>
        </nav>
      </div>

      <!-- Контент вкладок -->
      <div class="bg-gray-800 rounded-lg p-6">
        
        <!-- Товары -->
        <div v-if="activeTab === 'products'">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-white">Товары</h2>
            <router-link
              to="/admin/products/create"
              class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg transition-colors"
            >
              Создать товар
            </router-link>
          </div>

          <!-- Фильтры -->
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
            <input
              v-model="productSearch"
              @input="debounceSearch"
              placeholder="Поиск товаров..."
              class="bg-gray-700 text-white px-4 py-2 rounded-lg border border-gray-600 focus:border-blue-500 focus:outline-none"
            />
            <select
              v-model="productThemeFilter"
              @change="loadProducts(1)"
              class="bg-gray-700 text-white px-4 py-2 rounded-lg border border-gray-600 focus:border-blue-500 focus:outline-none"
            >
              <option value="">Все темы</option>
              <option v-for="theme in themes" :key="theme.id" :value="theme.id">
                {{ theme.name }}
              </option>
            </select>
            <select
              v-model="productStatusFilter"
              @change="loadProducts(1)"
              class="bg-gray-700 text-white px-4 py-2 rounded-lg border border-gray-600 focus:border-blue-500 focus:outline-none"
            >
              <option value="">Все товары</option>
              <option value="matryoshka">Только матрёшки</option>
              <option value="regular">Обычные товары</option>
            </select>
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
              class="bg-gray-700 rounded-lg p-4 flex items-center justify-between hover:bg-gray-600 transition-colors"
            >
              <div class="flex items-center space-x-4">
                <img
                  v-if="product.gallery_images?.[0]"
                  :src="product.gallery_images[0]"
                  :alt="product.name"
                  class="w-16 h-16 object-cover rounded-lg"
                />
                <div v-else class="w-16 h-16 bg-gray-600 rounded-lg flex items-center justify-center">
                  <span class="text-gray-400 text-xs">Нет фото</span>
                </div>
                
                <div>
                  <h4 class="text-white font-medium">{{ product.name }}</h4>
                  <p class="text-gray-400 text-sm">{{ formatPrice(product.price) }} ₽</p>
                  <p class="text-gray-500 text-xs">Тема: {{ product.theme?.name || 'Не указана' }}</p>
                  <div class="flex space-x-2 mt-1">
                    <span v-if="product.use_matryoshka" class="bg-purple-600 text-white px-2 py-1 rounded text-xs">
                      Матрёшка
                    </span>
                    <span class="bg-gray-600 text-gray-300 px-2 py-1 rounded text-xs">
                      {{ product.categories?.length || 0 }} категорий
                    </span>
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

        <!-- Файловый менеджер -->
        <div v-else-if="activeTab === 'images'">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-2xl font-semibold text-white">Файловый менеджер</h2>
            <div class="flex space-x-2">
              <select
                v-model="imageType"
                @change="loadImages(1)"
                class="bg-gray-700 text-white px-4 py-2 rounded-lg border border-gray-600"
              >
                <option value="product">Товары</option>
                <option value="matryoshka_outer">Матрёшка (внешний слой)</option>
                <option value="matryoshka_inner">Матрёшка (внутренний слой)</option>
              </select>
              <label class="bg-green-600 hover:bg-green-700 text-white px-6 py-2 rounded-lg cursor-pointer transition-colors">
                <input
                  type="file"
                  @change="uploadImage"
                  accept="image/*"
                  class="hidden"
                />
                Загрузить изображение
              </label>
            </div>
          </div>

          <!-- Сетка изображений -->
          <div v-if="imagesLoading" class="text-center py-8">
            <div class="text-gray-400">Загрузка изображений...</div>
          </div>

          <div v-else-if="images.length === 0" class="text-center py-8">
            <div class="text-gray-400">Изображения не найдены</div>
          </div>

          <div v-else>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-6 gap-4 mb-6">
              <div
                v-for="image in images"
                :key="image.filename"
                class="relative group bg-gray-700 rounded-lg overflow-hidden"
              >
                <img
                  :src="image.url"
                  :alt="image.filename"
                  class="w-full h-32 object-cover"
                />
                <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                  <div class="flex space-x-2">
                    <button
                      @click="copyImageUrl(image.url)"
                      class="bg-blue-600 hover:bg-blue-700 text-white p-2 rounded text-xs"
                      title="Скопировать URL"
                    >
                      📋
                    </button>
                    <button
                      @click="deleteImage(image)"
                      class="bg-red-600 hover:bg-red-700 text-white p-2 rounded text-xs"
                      title="Удалить"
                    >
                      🗑️
                    </button>
                  </div>
                </div>
                <div class="p-2">
                  <div class="text-white text-xs truncate">{{ image.filename }}</div>
                  <div class="text-gray-400 text-xs">{{ image.size_human }}</div>
                </div>
              </div>
            </div>

            <!-- Пагинация изображений -->
            <div v-if="imagesTotalPages > 1" class="flex justify-center space-x-2">
              <button
                v-for="page in imagesTotalPages"
                :key="page"
                @click="loadImages(page)"
                class="px-3 py-1 rounded transition-colors"
                :class="imagesPagination.current_page === page ? 'bg-red-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'"
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
          <p class="text-gray-400 mb-4">Управление правилами подбора камней по дате рождения</p>
          <div class="bg-gray-700 rounded-lg p-4">
            <p class="text-gray-300">Функционал в разработке</p>
            <button class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors">
              Создать правило
            </button>
          </div>
        </div>

        <!-- Маркетплейсы -->
        <div v-else-if="activeTab === 'marketplace'">
          <h2 class="text-2xl font-semibold text-white mb-6">Синхронизация с маркетплейсами</h2>
          <p class="text-gray-400 mb-4">Управление синхронизацией с Wildberries, Ozon, Яндекс.Маркет, flowwow</p>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div v-for="marketplace in marketplaces" :key="marketplace.id" class="bg-gray-700 rounded-lg p-4">
              <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-white">{{ marketplace.name }}</h3>
                <span
                  class="px-3 py-1 rounded-full text-xs font-medium"
                  :class="marketplace.status === 'connected' ? 'bg-green-600 text-white' : 'bg-red-600 text-white'"
                >
                  {{ marketplace.status === 'connected' ? 'Подключен' : 'Отключен' }}
                </span>
              </div>
              
              <p class="text-gray-300 text-sm mb-4">{{ marketplace.description }}</p>
              
              <div class="flex space-x-2">
                <button
                  @click="syncMarketplace(marketplace.id)"
                  class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded text-sm transition-colors"
                  :disabled="syncLoading"
                >
                  {{ syncLoading ? 'Синхронизация...' : 'Синхронизировать' }}
                </button>
                <button
                  @click="configureMarketplace(marketplace.id)"
                  class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded text-sm transition-colors"
                >
                  Настройки
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Настройки -->
        <div v-else-if="activeTab === 'settings'">
          <h2 class="text-2xl font-semibold text-white mb-6">Настройки</h2>
          
          <div class="space-y-6">
            <!-- Смена пароля -->
            <div class="bg-gray-700 rounded-lg p-6">
              <h3 class="text-lg font-semibold text-white mb-4">Смена пароля</h3>
              <form @submit.prevent="changePassword" class="space-y-4">
                <input
                  v-model="passwordForm.current_password"
                  type="password"
                  placeholder="Текущий пароль"
                  class="w-full bg-gray-600 text-white px-4 py-2 rounded-lg border border-gray-500 focus:border-blue-500 focus:outline-none"
                />
                <input
                  v-model="passwordForm.new_password"
                  type="password"
                  placeholder="Новый пароль"
                  class="w-full bg-gray-600 text-white px-4 py-2 rounded-lg border border-gray-500 focus:border-blue-500 focus:outline-none"
                />
                <input
                  v-model="passwordForm.new_password_confirmation"
                  type="password"
                  placeholder="Подтверждение нового пароля"
                  class="w-full bg-gray-600 text-white px-4 py-2 rounded-lg border border-gray-500 focus:border-blue-500 focus:outline-none"
                />
                <button
                  type="submit"
                  class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-2 rounded-lg transition-colors"
                  :disabled="loading"
                >
                  {{ loading ? 'Изменение...' : 'Изменить пароль' }}
                </button>
              </form>
            </div>

            <!-- Общая статистика -->
            <div class="bg-gray-700 rounded-lg p-6" v-if="stats">
              <h3 class="text-lg font-semibold text-white mb-4">Статистика системы</h3>
              <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="text-center">
                  <div class="text-2xl font-bold text-blue-400">{{ stats.products?.total || 0 }}</div>
                  <div class="text-gray-400 text-sm">Всего товаров</div>
                </div>
                <div class="text-center">
                  <div class="text-2xl font-bold text-purple-400">{{ stats.products?.matryoshka || 0 }}</div>
                  <div class="text-gray-400 text-sm">Матрёшки</div>
                </div>
                <div class="text-center">
                  <div class="text-2xl font-bold text-green-400">{{ stats.categories || 0 }}</div>
                  <div class="text-gray-400 text-sm">Категорий</div>
                </div>
                <div class="text-center">
                  <div class="text-2xl font-bold text-yellow-400">{{ stats.storage?.total_size_human || '0 B' }}</div>
                  <div class="text-gray-400 text-sm">Размер файлов</div>
                </div>
              </div>
            </div>
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
    const getEndpoint = (type) => {
      const endpoints = {
        'category': 'categories',
        'theme': 'themes', 
        'attribute': 'attributes'
      };
      return `/admin/${endpoints[type]}`;
    };
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
    const productStatusFilter = ref('');
    const searchTimeout = ref(null);

    // Изображения
    const imageType = ref('product');
    const images = ref([]);
    const imagesLoading = ref(false);
    const imagesPagination = reactive({
      current_page: 1,
      per_page: 24,
      total: 0
    });

    // Данные
    const products = ref([]);
    const categories = ref([]);
    const themes = ref([]);
    const attributes = ref([]);
    const stats = ref(null);

    // Формы
    const simpleForm = reactive({
      id: null,
      name: '',
      slug: ''
    });
    
    

    const passwordForm = reactive({
      current_password: '',
      new_password: '',
      new_password_confirmation: ''
    });

    // Пагинация
    const productsPagination = reactive({
      current_page: 1,
      per_page: 15,
      total: 0
    });

    // Маркетплейсы
    const marketplaces = ref([
      {
        id: 'wildberries',
        name: 'Wildberries',
        description: 'Российский маркетплейс товаров для дома и семьи',
        status: 'disconnected'
      },
      {
        id: 'ozon',
        name: 'Ozon',
        description: 'Крупнейший российский интернет-ритейлер',
        status: 'disconnected'
      },
      {
        id: 'yandex_market',
        name: 'Яндекс.Маркет',
        description: 'Торговая площадка Яндекса',
        status: 'disconnected'
      },
      {
        id: 'flowwow',
        name: 'Flowwow',
        description: 'Платформа для доставки цветов и подарков',
        status: 'disconnected'
      }
    ]);
    const syncLoading = ref(false);

    // Вкладки
    const tabs = [
      { id: 'products', name: 'Товары' },
      { id: 'images', name: 'Изображения' },
      { id: 'categories', name: 'Категории' },
      { id: 'themes', name: 'Темы' },
      { id: 'attributes', name: 'Атрибуты' },
      { id: 'quiz', name: 'Квиз' },
      { id: 'marketplace', name: 'Маркетплейсы' },
      { id: 'settings', name: 'Настройки' }
    ];

    // Вычисляемые свойства
    const totalPages = computed(() => {
      return Math.ceil(productsPagination.total / productsPagination.per_page);
    });

    const imagesTotalPages = computed(() => {
      return Math.ceil(imagesPagination.total / imagesPagination.per_page);
    });

    // Методы для работы с товарами
    const loadProducts = async (page = 1) => {
      loading.value = true;
      try {
        const params = {
          page,
          per_page: productsPagination.per_page,
          search: productSearch.value,
          theme_id: productThemeFilter.value,
          status: productStatusFilter.value
        };

        const response = await axios.get('/admin/products', { params });
        products.value = response.data.data;
        Object.assign(productsPagination, response.data.meta || response.data);
      } catch (error) {
        console.error('Ошибка загрузки товаров:', error);
        if (error.response?.status === 401) {
          logout();
        } else {
          alert('Ошибка загрузки товаров: ' + (error.response?.data?.message || 'Неизвестная ошибка'));
        }
      } finally {
        loading.value = false;
      }
    };

    const debounceSearch = () => {
      clearTimeout(searchTimeout.value);
      searchTimeout.value = setTimeout(() => {
        loadProducts(1);
      }, 500);
    };

    // Методы для работы с изображениями
    const loadImages = async (page = 1) => {
      imagesLoading.value = true;
      try {
        const params = {
          page,
          per_page: imagesPagination.per_page,
          type: imageType.value
        };

        const response = await axios.get('/admin/images', { params });
        images.value = response.data.images || [];
        Object.assign(imagesPagination, response.data.pagination || {});
      } catch (error) {
        console.error('Ошибка загрузки изображений:', error);
        alert('Ошибка загрузки изображений');
      } finally {
        imagesLoading.value = false;
      }
    };

    const uploadImage = async (event) => {
      const file = event.target.files[0];
      if (!file) return;

      const formData = new FormData();
      formData.append('image', file);
      formData.append('type', imageType.value);

      try {
        loading.value = true;
        await axios.post('/admin/images/upload', formData, {
          headers: {
            'Content-Type': 'multipart/form-data'
          }
        });
        
        alert('Изображение успешно загружено');
        loadImages(imagesPagination.current_page);
      } catch (error) {
        console.error('Ошибка загрузки изображения:', error);
        alert('Ошибка загрузки изображения: ' + (error.response?.data?.message || 'Неизвестная ошибка'));
      } finally {
        loading.value = false;
        event.target.value = '';
      }
    };

    const copyImageUrl = (url) => {
      navigator.clipboard.writeText(url).then(() => {
        alert('URL скопирован в буфер обмена');
      });
    };

    const deleteImage = async (image) => {
      if (!confirm(`Удалить изображение ${image.filename}?`)) return;

      try {
        await axios.delete(`/admin/images/${image.filename}`, {
          params: { type: imageType.value }
        });
        
        alert('Изображение удалено');
        loadImages(imagesPagination.current_page);
      } catch (error) {
        console.error('Ошибка удаления изображения:', error);
        alert('Ошибка удаления изображения');
      }
    };

    // Методы для простых сущностей
    const loadCategories = async () => {
      try {
        const response = await axios.get('/admin/categories');
        categories.value = response.data || [];
      } catch (error) {
        console.error('Ошибка загрузки категорий:', error);
        if (error.response?.status === 401) logout();
        else alert('Ошибка загрузки категорий');
      }
    };

    const loadThemes = async () => {
      try {
        const response = await axios.get('/admin/themes');
        themes.value = response.data || [];
      } catch (error) {
        console.error('Ошибка загрузки тем:', error);
        if (error.response?.status === 401) logout();
        else alert('Ошибка загрузки тем');
      }
    };

    const loadAttributes = async () => {
      try {
        const response = await axios.get('/admin/attributes');
        attributes.value = response.data || [];
      } catch (error) {
        console.error('Ошибка загрузки атрибутов:', error);
        if (error.response?.status === 401) logout();
        else alert('Ошибка загрузки атрибутов');
      }
    };

    const loadStats = async () => {
      try {
        const response = await axios.get('/admin/stats');
        stats.value = response.data || {};
      } catch (error) {
        console.error('Ошибка загрузки статистики:', error);
      }
    };

    // Модальные окна
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

    const saveSimple = async () => {
      loading.value = true;
      try {
        // Используем функцию getEndpoint, определенную в начале setup()
        const endpoint = getEndpoint(modalType.value);
        
        if (modalMode.value === 'create') {
          await axios.post(endpoint, simpleForm);
        } else {
          await axios.put(`${endpoint}/${simpleForm.id}`, simpleForm);
        }
        
        modalOpen.value = false;
        
        // Обновляем соответствующий список
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
        
        loadStats();
      } catch (error) {
        console.error('Ошибка сохранения:', error);
        alert(error.response?.data?.message || 'Ошибка сохранения');
      } finally {
        loading.value = false;
      }
    };

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
        loadStats();
      } catch (error) {
        console.error('Ошибка удаления:', error);
        alert(error.response?.data?.message || 'Ошибка удаления');
      } finally {
        loading.value = false;
      }
    };

    // Настройки
    const changePassword = async () => {
      loading.value = true;
      try {
        await axios.put('/admin/change-password', passwordForm);
        alert('Пароль успешно изменен');
        Object.assign(passwordForm, {
          current_password: '',
          new_password: '',
          new_password_confirmation: ''
        });
      } catch (error) {
        console.error('Ошибка смены пароля:', error);
        alert(error.response?.data?.message || 'Ошибка смены пароля');
      } finally {
        loading.value = false;
      }
    };

    // Маркетплейсы
    const syncMarketplace = async (marketplaceId) => {
      syncLoading.value = true;
      try {
        await axios.post('/marketplace/sync', { marketplace: marketplaceId });
        alert('Синхронизация завершена');
      } catch (error) {
        console.error('Ошибка синхронизации:', error);
        alert('Ошибка синхронизации: ' + (error.response?.data?.message || 'Неизвестная ошибка'));
      } finally {
        syncLoading.value = false;
      }
    };

    const configureMarketplace = (marketplaceId) => {
      alert(`Настройка ${marketplaceId} в разработке`);
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
      loadStats();
      loadImages();
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
      productStatusFilter,
      products,
      categories,
      themes,
      attributes,
      stats,
      simpleForm,
      passwordForm,
      productsPagination,
      imageType,
      images,
      imagesLoading,
      imagesPagination,
      marketplaces,
      syncLoading,
      tabs,
      totalPages,
      imagesTotalPages,
      loadProducts,
      debounceSearch,
      loadImages,
      uploadImage,
      copyImageUrl,
      deleteImage,
      openCreateSimple,
      openEditSimple,
      saveSimple,
      confirmDelete,
      deleteItem,
      changePassword,
      syncMarketplace,
      configureMarketplace,
      logout,
      formatPrice,
      getEntityTypeName
    };
  }
};
</script>