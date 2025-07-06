<template>
  <div class="min-h-screen bg-gray-900 p-6">
    <div class="max-w-7xl mx-auto">
      <!-- Заголовок -->
      <div class="flex justify-between items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-white">
            {{ mode === 'edit' ? 'Редактировать товар' : 'Создать новый товар' }}
          </h1>
          <p class="text-gray-400 mt-1">Заполните информацию о товаре</p>
        </div>
        
        <div class="flex space-x-3">
          <router-link 
            to="/admin" 
            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors"
          >
            ← Назад к админке
          </router-link>
          
          <button 
            v-if="mode === 'edit'"
            @click="previewProduct"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
          >
            Предпросмотр
          </button>
        </div>
      </div>

      <!-- Индикатор загрузки -->
      <div v-if="loading" class="flex justify-center items-center py-12">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-blue-500"></div>
      </div>

      <!-- Форма создания/редактирования товара -->
      <form v-else @submit.prevent="saveProduct" class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        
        <!-- Левая колонка: Основная информация -->
        <div class="lg:col-span-2 space-y-8">
          
          <!-- Основная информация -->
          <div class="bg-gray-800 rounded-lg p-6">
            <h2 class="text-xl font-semibold text-white mb-4">Основная информация</h2>
            
            <div class="space-y-6">
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
                  @input="generateMetaTags"
                >
              </div>

              <div>
                <label class="block text-sm font-medium text-white mb-2">
                  Артикул (SKU)
                </label>
                <div class="flex space-x-2">
                  <input
                    v-model="form.sku"
                    type="text"
                    class="flex-1 bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                    placeholder="Будет сгенерирован автоматически"
                  >
                  <button 
                    type="button"
                    @click="generateSKU"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg transition-colors"
                  >
                    Генерировать
                  </button>
                </div>
              </div>

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
                  @input="generateMetaTags"
                ></textarea>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-white mb-2">
                    Цена (рубли) *
                  </label>
                  <input
                    v-model.number="form.price"
                    type="number"
                    required
                    min="0"
                    step="0.01"
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                    placeholder="0.00"
                  >
                </div>

                <div>
                  <label class="block text-sm font-medium text-white mb-2">
                    Количество в наличии
                  </label>
                  <input
                    v-model.number="form.stock_quantity"
                    type="number"
                    min="0"
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                    placeholder="0"
                  >
                </div>
              </div>

              <div class="grid grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-white mb-2">
                    Вес (граммы)
                  </label>
                  <input
                    v-model.number="form.weight"
                    type="number"
                    min="0"
                    step="0.1"
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                    placeholder="0.0"
                  >
                </div>

                <div>
                  <label class="block text-sm font-medium text-white mb-2">
                    Тема
                  </label>
                  <select
                    v-model="form.theme_id"
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white focus:border-blue-500 focus:outline-none"
                  >
                    <option value="">Без темы</option>
                    <option 
                      v-for="theme in themes" 
                      :key="theme.id" 
                      :value="theme.id"
                    >
                      {{ theme.name }}
                    </option>
                  </select>
                </div>
              </div>

              <!-- Размеры -->
              <div>
                <label class="block text-sm font-medium text-white mb-2">
                  Размеры (мм)
                </label>
                <div class="grid grid-cols-3 gap-2">
                  <input
                    v-model.number="form.dimensions.length"
                    type="number"
                    min="0"
                    step="0.1"
                    placeholder="Длина"
                    class="bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                  >
                  <input
                    v-model.number="form.dimensions.width"
                    type="number"
                    min="0"
                    step="0.1"
                    placeholder="Ширина"
                    class="bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                  >
                  <input
                    v-model.number="form.dimensions.height"
                    type="number"
                    min="0"
                    step="0.1"
                    placeholder="Высота"
                    class="bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                  >
                </div>
              </div>
            </div>
          </div>

          <!-- Эффект матрёшки -->
          <div class="bg-gray-800 rounded-lg p-6">
            <div class="flex items-center justify-between mb-4">
              <h2 class="text-xl font-semibold text-white">Эффект матрёшки</h2>
              <label class="flex items-center">
                <input
                  v-model="form.use_matryoshka"
                  type="checkbox"
                  class="mr-2"
                >
                <span class="text-white">Использовать эффект</span>
              </label>
            </div>

            <div v-if="form.use_matryoshka" class="grid grid-cols-2 gap-6">
              <!-- Внешнее изображение -->
              <div>
                <label class="block text-sm font-medium text-white mb-2">
                  Внешнее изображение *
                </label>
                <div class="space-y-3">
                  <div v-if="form.image_layers.outer" class="relative">
                    <img 
                      :src="getImageUrl(form.image_layers.outer)" 
                      alt="Внешнее изображение"
                      class="w-full h-32 object-cover rounded-lg"
                      @error="handleImageError"
                    >
                    <button 
                      type="button"
                      @click="form.image_layers.outer = ''"
                      class="absolute top-2 right-2 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm"
                    >
                      ×
                    </button>
                  </div>
                  <button 
                    type="button"
                    @click="openImageSelector('outer')"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg transition-colors"
                  >
                    {{ form.image_layers.outer ? 'Изменить изображение' : 'Выбрать изображение' }}
                  </button>
                </div>
              </div>

              <!-- Внутреннее изображение -->
              <div>
                <label class="block text-sm font-medium text-white mb-2">
                  Внутреннее изображение *
                </label>
                <div class="space-y-3">
                  <div v-if="form.image_layers.inner" class="relative">
                    <img 
                      :src="getImageUrl(form.image_layers.inner)" 
                      alt="Внутреннее изображение"
                      class="w-full h-32 object-cover rounded-lg"
                      @error="handleImageError"
                    >
                    <button 
                      type="button"
                      @click="form.image_layers.inner = ''"
                      class="absolute top-2 right-2 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm"
                    >
                      ×
                    </button>
                  </div>
                  <button 
                    type="button"
                    @click="openImageSelector('inner')"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg transition-colors"
                  >
                    {{ form.image_layers.inner ? 'Изменить изображение' : 'Выбрать изображение' }}
                  </button>
                </div>
              </div>
            </div>
          </div>

          <!-- Галерея изображений -->
          <div class="bg-gray-800 rounded-lg p-6">
            <h2 class="text-xl font-semibold text-white mb-4">Галерея изображений</h2>
            
            <div class="space-y-4">
              <!-- Список изображений -->
              <div class="grid grid-cols-3 gap-4">
                <div 
                  v-for="(image, index) in form.gallery_images" 
                  :key="index"
                  class="space-y-2"
                >
                  <div v-if="image" class="relative">
                    <img 
                      :src="getImageUrl(image)" 
                      :alt="`Изображение ${index + 1}`"
                      class="w-full h-full object-cover rounded-lg"
                      @error="handleImageError"
                    >
                    <button 
                      type="button"
                      @click="removeGalleryImage(index)"
                      class="absolute top-2 right-2 bg-red-600 text-white rounded-full w-6 h-6 flex items-center justify-center text-sm"
                    >
                      ×
                    </button>
                  </div>
                  <button 
                    type="button"
                    @click="openImageSelector('gallery', index)"
                    class="w-full bg-blue-600 hover:bg-blue-700 text-white py-2 rounded-lg transition-colors text-sm"
                  >
                    {{ image ? 'Изменить' : 'Выбрать изображение' }}
                  </button>
                </div>
              </div>

              <button 
                type="button"
                @click="addGalleryImage"
                class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg transition-colors"
              >
                + Добавить изображение
              </button>

              <!-- Загрузка новых изображений -->
              <div class="border-t border-gray-700 pt-4">
                <h3 class="text-lg font-medium text-white mb-4">Загрузить новые изображения</h3>
                <input
                  ref="imageUpload"
                  type="file"
                  multiple
                  accept="image/*"
                  @change="uploadNewImages"
                  class="hidden"
                >
                <button 
                  type="button"
                  @click="$refs.imageUpload.click()"
                  class="bg-green-600 hover:bg-green-700 text-white px-4 py-3 rounded-lg transition-colors"
                  :disabled="uploading"
                >
                  {{ uploading ? 'Загрузка...' : 'Выбрать файлы для загрузки' }}
                </button>
              </div>
            </div>
          </div>

          <!-- SEO и мета-теги -->
          <div class="bg-gray-800 rounded-lg p-6">
            <h2 class="text-xl font-semibold text-white mb-4">SEO и мета-теги</h2>
            
            <div class="space-y-4">
              <div>
                <label class="block text-sm font-medium text-white mb-2">
                  Мета-заголовок
                </label>
                <input
                  v-model="form.meta_title"
                  type="text"
                  maxlength="60"
                  class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                  placeholder="Автоматически генерируется из названия"
                >
                <div class="text-xs text-gray-400 mt-1">
                  {{ form.meta_title.length }}/60 символов
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-white mb-2">
                  Мета-описание
                </label>
                <textarea
                  v-model="form.meta_description"
                  rows="3"
                  maxlength="160"
                  class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                  placeholder="Автоматически генерируется из описания"
                ></textarea>
                <div class="text-xs text-gray-400 mt-1">
                  {{ form.meta_description.length }}/160 символов
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Правая колонка: Категории и атрибуты -->
        <div class="space-y-8">
          
          <!-- Категории -->
          <div class="bg-gray-800 rounded-lg p-6">
            <h2 class="text-xl font-semibold text-white mb-4">Категории</h2>
            
            <div class="space-y-2 max-h-64 overflow-y-auto">
              <div 
                v-for="category in categoryTree" 
                :key="category.id"
                class="space-y-1"
              >
                <label class="flex items-center space-x-2 p-2 hover:bg-gray-700 rounded">
                  <input
                    v-model="form.category_ids"
                    :value="category.id"
                    type="checkbox"
                    class="rounded"
                  >
                  <span class="text-white">{{ category.name }}</span>
                  <span class="text-gray-400 text-sm">({{ category.products_count || 0 }})</span>
                </label>
                
                <!-- Дочерние категории -->
                <div v-if="category.children && category.children.length" class="ml-6 space-y-1">
                  <label 
                    v-for="child in category.children" 
                    :key="child.id"
                    class="flex items-center space-x-2 p-2 hover:bg-gray-700 rounded"
                  >
                    <input
                      v-model="form.category_ids"
                      :value="child.id"
                      type="checkbox"
                      class="rounded"
                    >
                    <span class="text-white">{{ child.name }}</span>
                    <span class="text-gray-400 text-sm">({{ child.products_count || 0 }})</span>
                  </label>
                </div>
              </div>
            </div>
          </div>

          <!-- Атрибуты -->
          <div class="bg-gray-800 rounded-lg p-6">
            <h2 class="text-xl font-semibold text-white mb-4">Атрибуты</h2>
            
            <div class="space-y-6">
              <div 
                v-for="attribute in attributes" 
                :key="attribute.id"
                class="space-y-3"
              >
                <h3 class="text-white font-medium">{{ attribute.name }}</h3>
                
                <!-- Значения атрибута -->
                <div v-if="attribute.attribute_values && attribute.attribute_values.length" class="space-y-2">
                  <label 
                    v-for="value in attribute.attribute_values" 
                    :key="value.id"
                    class="flex items-center space-x-2 p-2 hover:bg-gray-700 rounded"
                  >
                    <input
                      :checked="isAttributeValueSelected(attribute.id, value.id)"
                      @change="toggleAttributeValue(attribute.id, value.id)"
                      type="checkbox"
                      class="rounded"
                    >
                    <span class="text-white">{{ value.value }}</span>
                  </label>
                </div>
                
                <!-- Кастомное значение -->
                <div>
                  <label class="block text-sm text-gray-400 mb-1">
                    Кастомное значение для "{{ attribute.name }}"
                  </label>
                  <input
                    :value="getCustomAttributeValue(attribute.id)"
                    @input="setCustomAttributeValue(attribute.id, $event.target.value)"
                    type="text"
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                    placeholder="Введите значение..."
                  >
                </div>
              </div>
            </div>
          </div>

          <!-- Кнопки сохранения -->
          <div class="bg-gray-800 rounded-lg p-6">
            <div class="space-y-3">
              <button 
                type="submit"
                class="w-full bg-blue-600 hover:bg-blue-700 text-white py-3 rounded-lg transition-colors"
                :disabled="saving"
              >
                {{ saving ? 'Сохранение...' : (mode === 'edit' ? 'Обновить товар' : 'Создать товар') }}
              </button>
              
              <button 
                v-if="mode === 'edit'"
                type="button"
                @click="deleteProduct"
                class="w-full bg-red-600 hover:bg-red-700 text-white py-3 rounded-lg transition-colors"
              >
                Удалить товар
              </button>
            </div>
          </div>
        </div>
      </form>
    </div>

    <!-- Модальное окно выбора изображений -->
    <div v-if="showImageSelector" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-gray-800 rounded-lg p-6 max-w-4xl w-full max-h-96 overflow-hidden flex flex-col">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl text-white">Выберите изображение</h3>
          <button 
            @click="closeImageSelector"
            class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors"
          >
            Закрыть
          </button>
        </div>
        
        <div class="flex-1 overflow-y-auto">
          <div class="grid grid-cols-4 gap-4">
            <div 
              v-for="image in availableImages" 
              :key="image.filename"
              class="relative cursor-pointer group"
              @click="selectImage(image.filename)"
            >
              <img 
                :src="getImageUrl(image.filename)" 
                :alt="image.filename"
                class="w-full h-24 object-cover rounded-lg group-hover:opacity-75 transition-opacity"
                @error="handleImageError"
              >
              <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-25 rounded-lg transition-opacity"></div>
              <div class="absolute bottom-1 left-1 right-1">
                <p class="text-white text-xs bg-black bg-opacity-50 p-1 rounded truncate">
                  {{ image.filename }}
                </p>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter, useRoute } from 'vue-router';
import axios from 'axios';

export default {
  name: 'ProductCreate',
  setup() {
    const router = useRouter();
    const route = useRoute();
    
    // Состояние
    const loading = ref(false);
    const saving = ref(false);
    const uploading = ref(false);
    const mode = computed(() => route.params.id ? 'edit' : 'create');
    
    // Данные
    const themes = ref([]);
    const categoryTree = ref([]);
    const attributes = ref([]);
    const availableImages = ref([]);
    
    // Модальные окна
    const showImageSelector = ref(false);
    const currentImageType = ref(null);
    const currentImageIndex = ref(null);
    
    // Форма
    const form = reactive({
      name: '',
      sku: '',
      description: '',
      price: 0,
      stock_quantity: 0,
      weight: 0,
      use_matryoshka: false,
      image_layers: {
        outer: '',
        inner: ''
      },
      gallery_images: [''],
      theme_id: '',
      category_ids: [],
      attribute_values: {},
      meta_title: '',
      meta_description: '',
      dimensions: {
        length: 0,
        width: 0,
        height: 0
      }
    });

    // Загрузка данных
    const loadThemes = async () => {
      try {
        const response = await axios.get('/admin/themes');
        themes.value = response.data;
      } catch (error) {
        console.error('Ошибка загрузки тем:', error);
      }
    };

    const loadCategoryTree = async () => {
      try {
        const response = await axios.get('/admin/categories/tree');
        categoryTree.value = response.data;
      } catch (error) {
        console.error('Ошибка загрузки категорий:', error);
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

    const loadAvailableImages = async () => {
      try {
        const response = await axios.get('/admin/images');
        availableImages.value = response.data.data || response.data || [];
      } catch (error) {
        console.error('Ошибка загрузки изображений:', error);
      }
    };

    const loadProduct = async () => {
      if (mode.value === 'create') return;
      
      try {
        loading.value = true;
        const response = await axios.get(`/admin/products/${route.params.id}`);
        const product = response.data;
        
        // Преобразование атрибутов
        const attributeValues = {};
        if (product.product_attributes) {
          product.product_attributes.forEach(attr => {
            if (attr.attribute_value_id) {
              if (!attributeValues[attr.attribute_id]) {
                attributeValues[attr.attribute_id] = [];
              }
              attributeValues[attr.attribute_id].push(attr.attribute_value_id);
            }
            if (attr.custom_value) {
              attributeValues[`custom_${attr.attribute_id}`] = attr.custom_value;
            }
          });
        }

        Object.assign(form, {
          ...product,
          attribute_values: attributeValues
        });
        
      } catch (error) {
        console.error('Ошибка загрузки товара:', error);
        alert('Ошибка загрузки товара');
        router.push('/admin');
      } finally {
        loading.value = false;
      }
    };

    // Работа с атрибутами
    const toggleAttributeValue = (attributeId, valueId) => {
      if (!form.attribute_values[attributeId]) {
        form.attribute_values[attributeId] = [];
      }
      
      const values = form.attribute_values[attributeId];
      const index = values.indexOf(valueId);
      
      if (index > -1) {
        values.splice(index, 1);
      } else {
        values.push(valueId);
      }
    };

    const setCustomAttributeValue = (attributeId, value) => {
      form.attribute_values[`custom_${attributeId}`] = value.trim();
    };

    const isAttributeValueSelected = (attributeId, valueId) => {
      const values = form.attribute_values[attributeId];
      return values && values.includes(valueId);
    };

    const getCustomAttributeValue = (attributeId) => {
      return form.attribute_values[`custom_${attributeId}`] || '';
    };

    // Работа с изображениями
    const addGalleryImage = () => {
      form.gallery_images.push('');
    };

    const removeGalleryImage = (index) => {
      form.gallery_images.splice(index, 1);
    };

    const openImageSelector = (type, index = null) => {
      currentImageType.value = type;
      currentImageIndex.value = index;
      showImageSelector.value = true;
      loadAvailableImages();
    };

    const closeImageSelector = () => {
      showImageSelector.value = false;
      currentImageType.value = null;
      currentImageIndex.value = null;
    };

    const selectImage = (filename) => {
      const type = currentImageType.value;
      
      if (type === 'outer') {
        form.image_layers.outer = filename;
      } else if (type === 'inner') {
        form.image_layers.inner = filename;
      } else if (type === 'gallery') {
        form.gallery_images[currentImageIndex.value] = filename;
      }
      
      closeImageSelector();
    };

    const uploadNewImages = async (event) => {
      const files = event.target.files;
      if (!files.length) return;

      uploading.value = true;
      
      try {
        for (let file of files) {
          const formData = new FormData();
          formData.append('image', file);
          formData.append('type', 'product');
          
          await axios.post('/admin/images', formData, {
            headers: {
              'Content-Type': 'multipart/form-data'
            }
          });
        }
        
        alert('Изображения загружены!');
        loadAvailableImages();
        
      } catch (error) {
        console.error('Ошибка загрузки изображений:', error);
        alert('Ошибка загрузки изображений');
      } finally {
        uploading.value = false;
        event.target.value = '';
      }
    };

    // Утилиты
    const generateSKU = () => {
      form.sku = 'STJ-' + Date.now();
    };

    const generateMetaTags = () => {
      if (!form.meta_title && form.name) {
        form.meta_title = form.name.substring(0, 60);
      }
      if (!form.meta_description && form.description) {
        form.meta_description = form.description.substring(0, 160);
      }
    };

    const formatPrice = (price) => {
      return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB'
      }).format(price);
    };

    const getImageUrl = (filename) => {
      if (!filename) return '/placeholder.jpg';
      if (filename.startsWith('http')) return filename;
      return `/storage/images/products/${filename}`;
    };

    const handleImageError = (event) => {
      event.target.src = '/placeholder.jpg';
    };

    const previewProduct = () => {
      if (mode.value === 'edit') {
        window.open(`/product/${form.slug}`, '_blank');
      }
    };

    // Сохранение товара
    const saveProduct = async () => {
      try {
        saving.value = true;
        
        // Подготовка данных
        const data = {
          ...form,
          price: parseFloat(form.price) || 0,
          stock_quantity: parseInt(form.stock_quantity) || 0,
          weight: parseFloat(form.weight) || 0,
        };

        let response;
        if (mode.value === 'edit') {
          response = await axios.put(`/admin/products/${route.params.id}`, data);
        } else {
          response = await axios.post('/admin/products', data);
        }

        alert(mode.value === 'edit' ? 'Товар обновлен!' : 'Товар создан!');
        router.push('/admin');
        
      } catch (error) {
        console.error('Ошибка сохранения товара:', error);
        const message = error.response?.data?.message || 'Ошибка сохранения';
        alert('Ошибка: ' + message);
      } finally {
        saving.value = false;
      }
    };
    
    const deleteProduct = async () => {
      if (!confirm('Вы уверены, что хотите удалить этот товар? Это действие нельзя отменить.')) return;
      
      try {
        await axios.delete(`/admin/products/${route.params.id}`);
        alert('Товар удален!');
        router.push('/admin');
      } catch (error) {
        console.error('Ошибка удаления товара:', error);
        alert('Ошибка удаления товара');
      }
    };

    // Инициализация
    onMounted(async () => {
      try {
        await Promise.all([
          loadThemes(),
          loadCategoryTree(),
          loadAttributes(),
          loadProduct()
        ]);
        
        // Генерируем SKU для нового товара
        if (mode.value === 'create' && !form.sku) {
          generateSKU();
        }
      } catch (error) {
        console.error('Ошибка инициализации:', error);
      }
    });

    return {
      // Состояние
      loading,
      saving,
      uploading,
      mode,
      
      // Данные
      themes,
      categoryTree,
      attributes,
      availableImages,
      form,
      
      // Модальные окна
      showImageSelector,
      
      // Методы
      toggleAttributeValue,
      setCustomAttributeValue,
      isAttributeValueSelected,
      getCustomAttributeValue,
      
      addGalleryImage,
      removeGalleryImage,
      openImageSelector,
      closeImageSelector,
      selectImage,
      uploadNewImages,
      
      generateSKU,
      generateMetaTags,
      
      formatPrice,
      getImageUrl,
      handleImageError,
      previewProduct,
      
      saveProduct,
      deleteProduct
    };
  }
};
</script>

<style scoped>
/* Дополнительные стили */
.product-create {
  font-family: 'Inter', sans-serif;
}

/* Скроллбары */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #1F2937;
}

::-webkit-scrollbar-thumb {
  background: #374151;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #4B5563;
}

/* Анимации */
.transition-colors {
  transition: background-color 0.2s ease, color 0.2s ease;
}

.transition-opacity {
  transition: opacity 0.2s ease;
}

/* Кастомные чекбоксы */
input[type="checkbox"] {
  @apply w-4 h-4 text-blue-600 bg-gray-700 border-gray-600 rounded focus:ring-blue-500 focus:ring-2;
}

/* Улучшение видимости плейсхолдеров */
::placeholder {
  color: #9CA3AF;
  opacity: 1;
}

/* Стили для загрузки */
.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Responsive улучшения */
@media (max-width: 1024px) {
  .grid.lg\\:grid-cols-3 {
    grid-template-columns: 1fr;
  }
}

/* Стили для файлового инпута */
input[type="file"] {
  @apply bg-gray-700 border border-gray-600 rounded-lg text-white file:mr-4 file:py-2 file:px-4 file:rounded-l-lg file:border-0 file:text-white file:bg-blue-600 hover:file:bg-blue-700;
}
</style>