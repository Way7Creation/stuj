<template>
  <div class="admin-panel min-h-screen bg-gray-900 text-white">
    <!-- Шапка -->
    <header class="bg-black border-b border-gray-800">
      <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
          <h1 class="text-xl font-semibold">Админ-панель Стужа</h1>
          <button
            @click="logout"
            class="bg-red-600 hover:bg-red-700 px-4 py-2 rounded text-sm transition-colors"
          >
            Выйти
          </button>
        </div>
      </div>
    </header>

    <!-- Основной контент -->
    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
      <!-- Табы -->
      <div class="flex space-x-1 mb-8 bg-gray-800 p-1 rounded-lg">
        <button
          @click="activeTab = 'products'"
          :class="[
            'flex-1 py-2 px-4 rounded transition-colors',
            activeTab === 'products' 
              ? 'bg-gray-700 text-white' 
              : 'text-gray-400 hover:text-white'
          ]"
        >
          Товары
        </button>
        <button
          @click="activeTab = 'categories'"
          :class="[
            'flex-1 py-2 px-4 rounded transition-colors',
            activeTab === 'categories' 
              ? 'bg-gray-700 text-white' 
              : 'text-gray-400 hover:text-white'
          ]"
        >
          Категории
        </button>
        <button
          @click="activeTab = 'themes'"
          :class="[
            'flex-1 py-2 px-4 rounded transition-colors',
            activeTab === 'themes' 
              ? 'bg-gray-700 text-white' 
              : 'text-gray-400 hover:text-white'
          ]"
        >
          Темы
        </button>
        <button
          @click="activeTab = 'attributes'"
          :class="[
            'flex-1 py-2 px-4 rounded transition-colors',
            activeTab === 'attributes' 
              ? 'bg-gray-700 text-white' 
              : 'text-gray-400 hover:text-white'
          ]"
        >
          Атрибуты
        </button>
      </div>

      <!-- Контент табов -->
      <!-- Товары -->
      <div v-if="activeTab === 'products'">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-2xl font-semibold">Товары</h2>
          <button
            @click="openCreateProduct"
            class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded transition-colors"
          >
            Добавить товар
          </button>
        </div>

        <div v-if="loading" class="text-center py-20">
          <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white mx-auto"></div>
        </div>

        <div v-else>
          <div class="bg-gray-800 rounded-lg overflow-hidden">
            <table class="min-w-full divide-y divide-gray-700">
              <thead class="bg-gray-900">
                <tr>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Название
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Цена
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Тема
                  </th>
                  <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Матрёшка
                  </th>
                  <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">
                    Действия
                  </th>
                </tr>
              </thead>
              <tbody class="divide-y divide-gray-700">
                <tr v-for="product in products" :key="product.id" class="hover:bg-gray-700 transition-colors">
                  <td class="px-6 py-4 whitespace-nowrap">
                    {{ product.name }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    {{ formatPrice(product.price) }} ₽
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    {{ product.theme?.name || '-' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap">
                    {{ product.use_matryoshka ? '✓' : '-' }}
                  </td>
                  <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                    <button
                      @click="openEditProduct(product)"
                      class="text-indigo-400 hover:text-indigo-300 mr-4"
                    >
                      Изменить
                    </button>
                    <button
                      @click="confirmDelete(product, 'product')"
                      class="text-red-400 hover:text-red-300"
                    >
                      Удалить
                    </button>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          <!-- Пагинация -->
          <div v-if="totalPages > 1" class="mt-6 flex justify-center">
            <nav class="flex items-center space-x-2">
              <button
                @click="loadProducts(productsPagination.current_page - 1)"
                :disabled="productsPagination.current_page <= 1"
                class="px-3 py-2 text-sm bg-gray-800 text-white rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-700 transition-colors"
              >
                Назад
              </button>
              <span class="px-4 py-2 text-sm">
                Страница {{ productsPagination.current_page }} из {{ totalPages }}
              </span>
              <button
                @click="loadProducts(productsPagination.current_page + 1)"
                :disabled="productsPagination.current_page >= totalPages"
                class="px-3 py-2 text-sm bg-gray-800 text-white rounded disabled:opacity-50 disabled:cursor-not-allowed hover:bg-gray-700 transition-colors"
              >
                Далее
              </button>
            </nav>
          </div>
        </div>
      </div>

      <!-- Категории -->
      <div v-if="activeTab === 'categories'">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-2xl font-semibold">Категории</h2>
          <button
            @click="openCreateSimple('category')"
            class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded transition-colors"
          >
            Добавить категорию
          </button>
        </div>
        <SimpleList
          :items="categories"
          :loading="loading"
          @edit="openEditSimple($event, 'category')"
          @delete="confirmDelete($event, 'category')"
        />
      </div>

      <!-- Темы -->
      <div v-if="activeTab === 'themes'">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-2xl font-semibold">Темы</h2>
          <button
            @click="openCreateSimple('theme')"
            class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded transition-colors"
          >
            Добавить тему
          </button>
        </div>
        <SimpleList
          :items="themes"
          :loading="loading"
          @edit="openEditSimple($event, 'theme')"
          @delete="confirmDelete($event, 'theme')"
        />
      </div>

      <!-- Атрибуты -->
      <div v-if="activeTab === 'attributes'">
        <div class="flex justify-between items-center mb-6">
          <h2 class="text-2xl font-semibold">Атрибуты</h2>
          <button
            @click="openCreateSimple('attribute')"
            class="bg-green-600 hover:bg-green-700 px-4 py-2 rounded transition-colors"
          >
            Добавить атрибут
          </button>
        </div>
        <SimpleList
          :items="attributes"
          :loading="loading"
          :show-stone="true"
          @edit="openEditSimple($event, 'attribute')"
          @delete="confirmDelete($event, 'attribute')"
        />
      </div>
    </main>

    <!-- Модальное окно товара -->
    <ProductModal
      v-if="modalOpen && modalType === 'product'"
      :mode="modalMode"
      :product-form="productForm"
      :themes="themes"
      :categories="categories"
      :attributes="attributes"
      :loading="loading"
      @save="saveProduct"
      @close="modalOpen = false"
      @add-gallery-image="addGalleryImage"
      @remove-gallery-image="removeGalleryImage"
    />

    <!-- Модальное окно для категорий/тем/атрибутов -->
    <SimpleModal
      v-if="modalOpen && modalType !== 'product'"
      :mode="modalMode"
      :type="modalType"
      :form="simpleForm"
      :loading="loading"
      @save="saveSimple"
      @close="modalOpen = false"
    />

    <!-- Модальное окно подтверждения удаления -->
    <ConfirmModal
      v-if="deleteConfirmOpen"
      :item="itemToDelete"
      :loading="loading"
      @confirm="deleteItem"
      @cancel="deleteConfirmOpen = false"
    />
  </div>
</template>

<script>
import { ref, reactive, onMounted, computed } from 'vue';
import { useRouter } from 'vue-router';
import { axios } from '../main.js';
import SimpleList from '../components/admin/SimpleList.vue';
import ProductModal from '../components/admin/ProductModal.vue';
import SimpleModal from '../components/admin/SimpleModal.vue';
import ConfirmModal from '../components/admin/ConfirmModal.vue';

export default {
  name: 'AdminPanel',
  components: {
    SimpleList,
    ProductModal,
    SimpleModal,
    ConfirmModal
  },
  setup() {
    const router = useRouter();
    const activeTab = ref('products');
    const loading = ref(false);
    const modalOpen = ref(false);
    const modalMode = ref('create'); // 'create' or 'edit'
    const modalType = ref('product'); // 'product', 'category', 'theme', 'attribute'
    const deleteConfirmOpen = ref(false);
    const itemToDelete = ref(null);

    // Данные
    const products = ref([]);
    const categories = ref([]);
    const themes = ref([]);
    const attributes = ref([]);

    // Форма товара
    const productForm = reactive({
      id: null,
      name: '',
      description: '',
      price: '',
      theme_id: '',
      use_matryoshka: false,
      image_layers: { outer: '', inner: '' },
      gallery_images: [],
      category_ids: [],
      attribute_ids: []
    });

    // Форма категории/темы/атрибута
    const simpleForm = reactive({
      id: null,
      name: '',
      slug: ''
    });

    // Пагинация товаров
    const productsPagination = reactive({
      current_page: 1,
      per_page: 15,
      total: 0
    });

    // Загрузка товаров
    const loadProducts = async (page = 1) => {
      loading.value = true;
      try {
        const response = await axios.get(`/admin/products?page=${page}&per_page=${productsPagination.per_page}`);
        products.value = response.data.data;
        Object.assign(productsPagination, {
          current_page: response.data.current_page,
          total: response.data.total,
          per_page: response.data.per_page
        });
      } catch (error) {
        console.error('Ошибка загрузки товаров:', error);
      } finally {
        loading.value = false;
      }
    };

    // Загрузка категорий
    const loadCategories = async () => {
      try {
        const response = await axios.get('/admin/categories');
        categories.value = response.data;
      } catch (error) {
        console.error('Ошибка загрузки категорий:', error);
      }
    };

    // Загрузка тем
    const loadThemes = async () => {
      try {
        const response = await axios.get('/admin/themes');
        themes.value = response.data;
      } catch (error) {
        console.error('Ошибка загрузки тем:', error);
      }
    };

    // Загрузка атрибутов
    const loadAttributes = async () => {
      try {
        const response = await axios.get('/admin/attributes');
        attributes.value = response.data;
      } catch (error) {
        console.error('Ошибка загрузки атрибутов:', error);
      }
    };

    // Открытие модального окна создания товара
    const openCreateProduct = () => {
      modalMode.value = 'create';
      modalType.value = 'product';
      resetProductForm();
      modalOpen.value = true;
    };

    // Открытие модального окна редактирования товара
    const openEditProduct = async (product) => {
      modalMode.value = 'edit';
      modalType.value = 'product';
      loading.value = true;
      try {
        const response = await axios.get(`/admin/products/${product.id}`);
        const data = response.data;
        
        Object.assign(productForm, {
          id: data.id,
          name: data.name,
          description: data.description,
          price: data.price,
          theme_id: data.theme_id,
          use_matryoshka: data.use_matryoshka,
          image_layers: data.image_layers || { outer: '', inner: '' },
          gallery_images: data.gallery_images || [],
          category_ids: data.categories.map(c => c.id),
          attribute_ids: data.attributes.map(a => a.id)
        });
        
        modalOpen.value = true;
      } catch (error) {
        console.error('Ошибка загрузки товара:', error);
      } finally {
        loading.value = false;
      }
    };

    // Открытие модального окна создания категории/темы/атрибута
    const openCreateSimple = (type) => {
      modalMode.value = 'create';
      modalType.value = type;
      resetSimpleForm();
      modalOpen.value = true;
    };

    // Открытие модального окна редактирования категории/темы/атрибута
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

    // Сохранение товара
    const saveProduct = async () => {
      loading.value = true;
      try {
        const data = {
          ...productForm,
          price: parseFloat(productForm.price)
        };

        if (modalMode.value === 'create') {
          await axios.post('/admin/products', data);
        } else {
          await axios.put(`/admin/products/${productForm.id}`, data);
        }

        modalOpen.value = false;
        loadProducts(productsPagination.current_page);
      } catch (error) {
        console.error('Ошибка сохранения товара:', error);
        alert(error.response?.data?.message || 'Ошибка сохранения');
      } finally {
        loading.value = false;
      }
    };

    // Сохранение категории/темы/атрибута
    const saveSimple = async () => {
      loading.value = true;
      try {
        const endpoint = `/admin/${modalType.value}s`; // categories, themes, attributes
        
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

    // Подтверждение удаления
    const confirmDelete = (item, type = 'product') => {
      itemToDelete.value = { item, type };
      deleteConfirmOpen.value = true;
    };

    // Удаление элемента
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

    // Сброс формы товара
    const resetProductForm = () => {
      Object.assign(productForm, {
        id: null,
        name: '',
        description: '',
        price: '',
        theme_id: '',
        use_matryoshka: false,
        image_layers: { outer: '', inner: '' },
        gallery_images: [],
        category_ids: [],
        attribute_ids: []
      });
    };

    // Сброс простой формы
    const resetSimpleForm = () => {
      Object.assign(simpleForm, {
        id: null,
        name: '',
        slug: ''
      });
    };

    // Добавление изображения в галерею
    const addGalleryImage = () => {
      productForm.gallery_images.push('');
    };

    // Удаление изображения из галереи
    const removeGalleryImage = (index) => {
      productForm.gallery_images.splice(index, 1);
    };

    // Выход из админки
    const logout = () => {
      localStorage.removeItem('auth_token');
      delete axios.defaults.headers.common['Authorization'];
      router.push('/admin/login');
    };

    // Форматирование цены
    const formatPrice = (price) => {
      return new Intl.NumberFormat('ru-RU').format(price);
    };

    // Вычисляемые свойства
    const totalPages = computed(() => Math.ceil(productsPagination.total / productsPagination.per_page));

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
      products,
      categories,
      themes,
      attributes,
      productForm,
      simpleForm,
      productsPagination,
      totalPages,
      loadProducts,
      openCreateProduct,
      openEditProduct,
      openCreateSimple,
      openEditSimple,
      saveProduct,
      saveSimple,
      confirmDelete,
      deleteItem,
      addGalleryImage,
      removeGalleryImage,
      logout,
      formatPrice
    };
  }
};
</script>