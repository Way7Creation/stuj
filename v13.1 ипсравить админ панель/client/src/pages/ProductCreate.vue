<template>
  <div class="product-create min-h-screen bg-gray-900 py-8">
    <div class="max-w-6xl mx-auto px-4">
      <!-- Заголовок -->
      <div class="flex items-center justify-between mb-8">
        <div>
          <h1 class="text-3xl font-bold text-white">
            {{ mode === 'edit' ? 'Редактировать товар' : 'Создать новый товар' }}
          </h1>
          <p class="text-gray-400 mt-1">Заполните информацию о товаре</p>
        </div>
        <router-link
          to="/admin"
          class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors"
        >
          ← Назад к админке
        </router-link>
      </div>

      <form @submit.prevent="saveProduct" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- Основная информация -->
        <div class="lg:col-span-2 space-y-6">
          <!-- Базовая информация -->
          <div class="bg-gray-800 rounded-xl p-6">
            <h2 class="text-xl font-semibold text-white mb-4">Основная информация</h2>
            
            <div class="space-y-4">
              <!-- Название -->
              <div>
                <label class="block text-sm font-medium text-white mb-2">
                  Название товара *
                </label>
                <input
                  v-model="form.name"
                  type="text"
                  required
                  class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                  placeholder="Например: Кольцо с агатом"
                />
              </div>

              <!-- Описание -->
              <div>
                <label class="block text-sm font-medium text-white mb-2">
                  Описание *
                </label>
                <textarea
                  v-model="form.description"
                  required
                  rows="4"
                  class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                  placeholder="Подробное описание товара..."
                />
              </div>

              <!-- Цена -->
              <div>
                <label class="block text-sm font-medium text-white mb-2">
                  Цена (рубли) *
                </label>
                <input
                  v-model="form.price"
                  type="number"
                  required
                  min="0"
                  step="0.01"
                  class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                  placeholder="2500.00"
                />
              </div>
            </div>
          </div>

          <!-- Категории и свойства -->
          <div class="bg-gray-800 rounded-xl p-6">
            <h2 class="text-xl font-semibold text-white mb-4">Категории и свойства</h2>
            
            <div class="space-y-4">
              <!-- Тема -->
              <div>
                <div class="flex items-center justify-between mb-2">
                  <label class="block text-sm font-medium text-white">
                    Тема *
                  </label>
                  <button
                    type="button"
                    @click="showQuickAdd('theme')"
                    class="text-blue-400 hover:text-blue-300 text-sm"
                  >
                    + Добавить тему
                  </button>
                </div>
                <select
                  v-model="form.theme_id"
                  required
                  class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-blue-500 focus:outline-none"
                >
                  <option value="">Выберите тему</option>
                  <option v-for="theme in themes" :key="theme.id" :value="theme.id">
                    {{ theme.name }}
                  </option>
                </select>
              </div>

              <!-- Категории -->
              <div>
                <div class="flex items-center justify-between mb-2">
                  <label class="block text-sm font-medium text-white">
                    Категории
                  </label>
                  <button
                    type="button"
                    @click="showQuickAdd('category')"
                    class="text-blue-400 hover:text-blue-300 text-sm"
                  >
                    + Добавить категорию
                  </button>
                </div>
                <div class="space-y-2 max-h-32 overflow-y-auto bg-gray-700 rounded-lg p-3">
                  <label v-for="category in categories" :key="category.id" class="flex items-center">
                    <input
                      v-model="form.category_ids"
                      :value="category.id"
                      type="checkbox"
                      class="mr-3 rounded border-gray-600 bg-gray-700 text-blue-600 focus:ring-blue-500"
                    />
                    <span class="text-white">{{ category.name }}</span>
                  </label>
                </div>
              </div>

              <!-- Атрибуты -->
              <div>
                <div class="flex items-center justify-between mb-2">
                  <label class="block text-sm font-medium text-white">
                    Атрибуты (камни, металлы)
                  </label>
                  <button
                    type="button"
                    @click="showQuickAdd('attribute')"
                    class="text-blue-400 hover:text-blue-300 text-sm"
                  >
                    + Добавить атрибут
                  </button>
                </div>
                <div class="space-y-2 max-h-32 overflow-y-auto bg-gray-700 rounded-lg p-3">
                  <label v-for="attribute in attributes" :key="attribute.id" class="flex items-center">
                    <input
                      v-model="form.attribute_ids"
                      :value="attribute.id"
                      type="checkbox"
                      class="mr-3 rounded border-gray-600 bg-gray-700 text-blue-600 focus:ring-blue-500"
                    />
                    <span class="text-white">{{ attribute.name }}</span>
                    <span v-if="attribute.is_stone" class="ml-2 text-xs text-green-400">(камень)</span>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- Эффект матрёшки -->
          <div class="bg-gray-800 rounded-xl p-6">
            <h2 class="text-xl font-semibold text-white mb-4">Эффект матрёшки</h2>
            
            <div class="space-y-4">
              <label class="flex items-center">
                <input
                  v-model="form.use_matryoshka"
                  type="checkbox"
                  class="mr-3 rounded border-gray-600 bg-gray-700 text-blue-600 focus:ring-blue-500"
                />
                <span class="text-white font-medium">Использовать эффект матрёшки</span>
              </label>

              <div v-if="form.use_matryoshka" class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-white mb-2">
                    Внешний слой
                  </label>
                  <input
                    v-model="form.image_layers.outer"
                    type="url"
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                    placeholder="URL внешнего слоя"
                  />
                </div>
                <div>
                  <label class="block text-sm font-medium text-white mb-2">
                    Внутренний слой
                  </label>
                  <input
                    v-model="form.image_layers.inner"
                    type="url"
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                    placeholder="URL внутреннего слоя"
                  />
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Боковая панель -->
        <div class="space-y-6">
          <!-- Изображения -->
          <div class="bg-gray-800 rounded-xl p-6">
            <h2 class="text-xl font-semibold text-white mb-4">Изображения товара</h2>
            
            <ImageUpload
              @selected="onImagesSelected"
              @uploaded="onImagesUploaded"
            />

            <!-- Галерея товара -->
            <div v-if="form.gallery_images.length > 0" class="mt-6">
              <h4 class="text-white font-medium mb-3">Галерея товара:</h4>
              <div class="space-y-2">
                <div
                  v-for="(image, index) in form.gallery_images"
                  :key="index"
                  class="flex items-center space-x-3 bg-gray-700 rounded-lg p-3"
                >
                  <img :src="image" :alt="`Изображение ${index + 1}`" class="w-12 h-12 object-cover rounded" />
                  <input
                    v-model="form.gallery_images[index]"
                    type="url"
                    class="flex-1 bg-gray-600 border border-gray-500 rounded px-3 py-2 text-white text-sm"
                    placeholder="URL изображения"
                  />
                  <button
                    type="button"
                    @click="removeGalleryImage(index)"
                    class="text-red-400 hover:text-red-300"
                  >
                    ✕
                  </button>
                </div>
              </div>
              <button
                type="button"
                @click="addGalleryImage"
                class="mt-3 text-blue-400 hover:text-blue-300 text-sm"
              >
                + Добавить изображение вручную
              </button>
            </div>
          </div>

          <!-- Предварительный просмотр -->
          <div class="bg-gray-800 rounded-xl p-6">
            <h2 class="text-xl font-semibold text-white mb-4">Предварительный просмотр</h2>
            
            <div class="bg-gray-900 rounded-lg p-4">
              <div v-if="form.gallery_images[0]" class="mb-3">
                <img
                  :src="form.gallery_images[0]"
                  :alt="form.name"
                  class="w-full h-32 object-cover rounded"
                />
              </div>
              <h3 class="text-white font-medium">{{ form.name || 'Название товара' }}</h3>
              <p class="text-gray-400 text-sm mt-1">{{ form.description || 'Описание товара' }}</p>
              <div class="flex items-center justify-between mt-3">
                <span class="text-lg font-bold text-blue-400">
                  {{ form.price ? formatPrice(form.price) : '0' }} ₽
                </span>
                <span v-if="form.use_matryoshka" class="text-xs bg-purple-600 text-white px-2 py-1 rounded">
                  Матрёшка
                </span>
              </div>
            </div>
          </div>

          <!-- Действия -->
          <div class="bg-gray-800 rounded-xl p-6">
            <div class="space-y-3">
              <button
                type="submit"
                :disabled="loading || !isFormValid"
                class="w-full bg-blue-600 hover:bg-blue-700 disabled:bg-gray-600 text-white py-3 rounded-lg font-medium transition-colors"
              >
                {{ loading ? 'Сохранение...' : (mode === 'edit' ? 'Обновить товар' : 'Создать товар') }}
              </button>
              
              <router-link
                to="/admin"
                class="block w-full bg-gray-600 hover:bg-gray-700 text-white py-3 rounded-lg font-medium text-center transition-colors"
              >
                Отмена
              </router-link>
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- Модальное окно быстрого добавления -->
    <QuickAddModal
      v-if="quickAddOpen"
      :type="quickAddType"
      @close="quickAddOpen = false"
      @added="onQuickAdded"
    />
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRoute, useRouter } from 'vue-router';
import { axios } from '../main.js';
import ImageUpload from '../components/admin/ImageUpload.vue';
import QuickAddModal from '../components/admin/QuickAddModal.vue';

export default {
  name: 'ProductCreate',
  components: {
    ImageUpload,
    QuickAddModal
  },
  setup() {
    const route = useRoute();
    const router = useRouter();
    
    const loading = ref(false);
    const mode = computed(() => route.params.id ? 'edit' : 'create');
    
    // Данные
    const themes = ref([]);
    const categories = ref([]);
    const attributes = ref([]);
    
    // Быстрое добавление
    const quickAddOpen = ref(false);
    const quickAddType = ref('');
    
    // Форма товара
    const form = reactive({
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
    
    // Валидация формы
    const isFormValid = computed(() => {
      return form.name && form.description && form.price && form.theme_id;
    });
    
    // Загрузка данных
    const loadData = async () => {
      await Promise.all([
        loadThemes(),
        loadCategories(),
        loadAttributes()
      ]);
      
      if (mode.value === 'edit') {
        await loadProduct();
      }
    };
    
    const loadThemes = async () => {
      try {
        console.log('ProductCreate: Загружаем темы...');
        const response = await axios.get('/admin/themes');
        console.log('ProductCreate: Ответ API тем:', response);
        
        themes.value = response.data || [];
        console.log('ProductCreate: Темы загружены:', themes.value);
      } catch (error) {
        console.error('ProductCreate: Ошибка загрузки тем:', error);
        alert('Ошибка загрузки тем: ' + (error.response?.data?.message || 'Неизвестная ошибка'));
      }
    };
    
    const loadCategories = async () => {
      try {
        console.log('ProductCreate: Загружаем категории...');
        const response = await axios.get('/admin/categories');
        console.log('ProductCreate: Ответ API категорий:', response);
        
        categories.value = response.data || [];
        console.log('ProductCreate: Категории загружены:', categories.value);
      } catch (error) {
        console.error('ProductCreate: Ошибка загрузки категорий:', error);
        alert('Ошибка загрузки категорий: ' + (error.response?.data?.message || 'Неизвестная ошибка'));
      }
    };
    
    const loadAttributes = async () => {
      try {
        console.log('ProductCreate: Загружаем атрибуты...');
        const response = await axios.get('/admin/attributes');
        console.log('ProductCreate: Ответ API атрибутов:', response);
        
        attributes.value = response.data || [];
        console.log('ProductCreate: Атрибуты загружены:', attributes.value);
      } catch (error) {
        console.error('ProductCreate: Ошибка загрузки атрибутов:', error);
        alert('Ошибка загрузки атрибутов: ' + (error.response?.data?.message || 'Неизвестная ошибка'));
      }
    };
    
    const loadProduct = async () => {
      try {
        loading.value = true;
        const response = await axios.get(`/admin/products/${route.params.id}`);
        const data = response.data;
        
        Object.assign(form, {
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
      } catch (error) {
        console.error('Ошибка загрузки товара:', error);
        alert('Товар не найден');
        router.push('/admin');
      } finally {
        loading.value = false;
      }
    };
    
    // Сохранение товара
    const saveProduct = async () => {
      loading.value = true;
      try {
        const data = {
          ...form,
          price: parseFloat(form.price)
        };

        if (mode.value === 'create') {
          await axios.post('/admin/products', data);
          alert('Товар успешно создан!');
        } else {
          await axios.put(`/admin/products/${form.id}`, data);
          alert('Товар успешно обновлен!');
        }

        router.push('/admin');
      } catch (error) {
        console.error('Ошибка сохранения товара:', error);
        alert(error.response?.data?.message || 'Ошибка сохранения');
      } finally {
        loading.value = false;
      }
    };
    
    // Работа с изображениями
    const onImagesSelected = (urls) => {
      form.gallery_images.push(...urls);
    };
    
    const onImagesUploaded = (urls) => {
      form.gallery_images.push(...urls);
    };
    
    const addGalleryImage = () => {
      form.gallery_images.push('');
    };
    
    const removeGalleryImage = (index) => {
      form.gallery_images.splice(index, 1);
    };
    
    // Быстрое добавление
    const showQuickAdd = (type) => {
      quickAddType.value = type;
      quickAddOpen.value = true;
    };
    
    const onQuickAdded = (item, type) => {
      switch (type) {
        case 'theme':
          themes.value.push(item);
          form.theme_id = item.id;
          break;
        case 'category':
          categories.value.push(item);
          form.category_ids.push(item.id);
          break;
        case 'attribute':
          attributes.value.push(item);
          form.attribute_ids.push(item.id);
          break;
      }
      quickAddOpen.value = false;
    };
    
    // Утилиты
    const formatPrice = (price) => {
      return new Intl.NumberFormat('ru-RU').format(price);
    };
    
    onMounted(() => {
      loadData();
    });
    
    return {
      loading,
      mode,
      themes,
      categories,
      attributes,
      quickAddOpen,
      quickAddType,
      form,
      isFormValid,
      saveProduct,
      onImagesSelected,
      onImagesUploaded,
      addGalleryImage,
      removeGalleryImage,
      showQuickAdd,
      onQuickAdded,
      formatPrice
    };
  }
};
</script>