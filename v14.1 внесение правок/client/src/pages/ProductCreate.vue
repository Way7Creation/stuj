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
                    Тема оформления *
                  </label>
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
              </div>

              <!-- Размеры -->
              <div>
                <label class="block text-sm font-medium text-white mb-2">
                  Размеры (мм)
                </label>
                <div class="grid grid-cols-3 gap-4">
                  <div>
                    <input
                      v-model.number="form.dimensions.length"
                      type="number"
                      min="0"
                      step="0.1"
                      class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                      placeholder="Длина"
                    >
                  </div>
                  <div>
                    <input
                      v-model.number="form.dimensions.width"
                      type="number"
                      min="0"
                      step="0.1"
                      class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                      placeholder="Ширина"
                    >
                  </div>
                  <div>
                    <input
                      v-model.number="form.dimensions.height"
                      type="number"
                      min="0"
                      step="0.1"
                      class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                      placeholder="Высота"
                    >
                  </div>
                </div>
              </div>
            </div>
          </div>

          <!-- Изображения и матрёшка -->
          <div class="bg-gray-800 rounded-lg p-6">
            <h2 class="text-xl font-semibold text-white mb-4">Изображения</h2>
            
            <!-- Переключатель матрёшки -->
            <div class="mb-6">
              <label class="flex items-center space-x-3">
                <input
                  v-model="form.use_matryoshka"
                  type="checkbox"
                  class="rounded"
                >
                <span class="text-white font-medium">Использовать эффект матрёшки</span>
                <span class="text-gray-400 text-sm">(два слоя изображений)</span>
              </label>
            </div>

            <!-- Матрёшка слои -->
            <div v-if="form.use_matryoshka" class="space-y-4 mb-6">
              <div>
                <label class="block text-sm font-medium text-white mb-2">
                  Внешний слой матрёшки
                </label>
                <div class="flex space-x-4">
                  <input
                    v-model="form.image_layers.outer"
                    type="text"
                    class="flex-1 bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                    placeholder="Имя файла внешнего слоя"
                  >
                  <button 
                    type="button"
                    @click="openImageSelector('outer')"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg transition-colors"
                  >
                    Выбрать
                  </button>
                </div>
                <div v-if="form.image_layers.outer" class="mt-2">
                  <img 
                    :src="getImageUrl(form.image_layers.outer)" 
                    alt="Внешний слой"
                    class="w-32 h-32 object-cover rounded-lg"
                    @error="handleImageError"
                  >
                </div>
              </div>

              <div>
                <label class="block text-sm font-medium text-white mb-2">
                  Внутренний слой матрёшки
                </label>
                <div class="flex space-x-4">
                  <input
                    v-model="form.image_layers.inner"
                    type="text"
                    class="flex-1 bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                    placeholder="Имя файла внутреннего слоя"
                  >
                  <button 
                    type="button"
                    @click="openImageSelector('inner')"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg transition-colors"
                  >
                    Выбрать
                  </button>
                </div>
                <div v-if="form.image_layers.inner" class="mt-2">
                  <img 
                    :src="getImageUrl(form.image_layers.inner)" 
                    alt="Внутренний слой"
                    class="w-32 h-32 object-cover rounded-lg"
                    @error="handleImageError"
                  >
                </div>
              </div>
            </div>

            <!-- Обычная галерея -->
            <div v-else>
              <label class="block text-sm font-medium text-white mb-2">
                Галерея изображений
              </label>
              
              <div class="space-y-4">
                <div 
                  v-for="(image, index) in form.gallery_images" 
                  :key="index"
                  class="flex items-center space-x-4"
                >
                  <input
                    v-model="form.gallery_images[index]"
                    type="text"
                    class="flex-1 bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                    placeholder="Имя файла изображения"
                  >
                  <button 
                    type="button"
                    @click="openImageSelector('gallery', index)"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-3 rounded-lg transition-colors"
                  >
                    Выбрать
                  </button>
                  <button 
                    type="button"
                    @click="removeGalleryImage(index)"
                    class="bg-red-600 hover:bg-red-700 text-white px-4 py-3 rounded-lg transition-colors"
                  >
                    Удалить
                  </button>
                  
                  <div v-if="image" class="w-16 h-16">
                    <img 
                      :src="getImageUrl(image)" 
                      :alt="`Изображение ${index + 1}`"
                      class="w-full h-full object-cover rounded-lg"
                      @error="handleImageError"
                    >
                  </div>
                </div>
                
                <button 
                  type="button"
                  @click="addGalleryImage"
                  class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg transition-colors"
                >
                  + Добавить изображение
                </button>
              </div>
            </div>

            <!-- Загрузка новых изображений -->
            <div class="mt-6 pt-6 border-t border-gray-700">
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
              >
                Выбрать файлы для загрузки
              </button>
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
                <!-- Основная категория -->
                <label class="flex items-center space-x-3 cursor-pointer p-2 hover:bg-gray-700 rounded">
                  <input
                    v-model="form.category_ids"
                    :value="category.id"
                    type="checkbox"
                    class="rounded"
                  >
                  <span class="text-white">{{ category.name }}</span>
                  <span class="text-gray-400 text-sm">({{ category.products_count || 0 }})</span>
                </label>

                <!-- Подкатегории -->
                <div v-if="category.children && category.children.length" class="ml-6 space-y-1">
                  <label 
                    v-for="child in category.children" 
                    :key="child.id"
                    class="flex items-center space-x-3 cursor-pointer p-2 hover:bg-gray-700 rounded"
                  >
                    <input
                      v-model="form.category_ids"
                      :value="child.id"
                      type="checkbox"
                      class="rounded"
                    >
                    <span class="text-gray-300">→ {{ child.name }}</span>
                    <span class="text-gray-400 text-sm">({{ child.products_count || 0 }})</span>
                  </label>
                </div>
              </div>
            </div>

            <div v-if="!categoryTree.length" class="text-gray-400 text-center py-4">
              Нет доступных категорий
            </div>
          </div>

          <!-- Атрибуты -->
          <div class="bg-gray-800 rounded-lg p-6">
            <h2 class="text-xl font-semibold text-white mb-4">Атрибуты товара</h2>
            
            <div class="space-y-6">
              <div 
                v-for="attribute in attributes" 
                :key="attribute.id"
                class="border border-gray-700 rounded-lg p-4"
              >
                <h3 class="text-white font-medium mb-3 flex items-center space-x-2">
                  <span>{{ attribute.name }}</span>
                  <span v-if="attribute.is_stone" class="bg-purple-600 text-white px-2 py-1 rounded text-xs">
                    Камень
                  </span>
                </h3>

                <!-- Стандартные значения -->
                <div v-if="attribute.values && attribute.values.length" class="mb-4">
                  <label class="block text-sm text-gray-300 mb-2">Стандартные значения:</label>
                  <div class="grid grid-cols-2 gap-2">
                    <label 
                      v-for="value in attribute.values" 
                      :key="value.id"
                      class="flex items-center space-x-2 cursor-pointer p-2 hover:bg-gray-700 rounded"
                    >
                      <input
                        :checked="isAttributeValueSelected(attribute.id, value.id)"
                        @change="toggleAttributeValue(attribute.id, value.id)"
                        type="checkbox"
                        class="rounded"
                      >
                      <span class="text-white text-sm">{{ value.value }}</span>
                    </label>
                  </div>
                </div>

                <!-- Кастомное значение -->
                <div>
                  <label class="block text-sm text-gray-300 mb-2">Или введите кастомное значение:</label>
                  <input
                    :value="getCustomAttributeValue(attribute.id)"
                    @input="addCustomAttributeValue(attribute.id, $event.target.value)"
                    type="text"
                    class="w-full bg-gray-700 border border-gray-600 rounded-lg px-3 py-2 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
                    :placeholder="`Кастомное значение для ${attribute.name}`"
                  >
                </div>
              </div>
            </div>

            <div v-if="!attributes.length" class="text-gray-400 text-center py-4">
              Нет доступных атрибутов
            </div>
          </div>

          <!-- Предпросмотр данных -->
          <div class="bg-gray-800 rounded-lg p-6">
            <h2 class="text-xl font-semibold text-white mb-4">Предпросмотр</h2>
            
            <div class="space-y-3 text-sm">
              <div>
                <span class="text-gray-400">Название:</span>
                <span class="text-white ml-2">{{ form.name || 'Не указано' }}</span>
              </div>
              
              <div>
                <span class="text-gray-400">Цена:</span>
                <span class="text-white ml-2">{{ formatPrice(form.price) }} ₽</span>
              </div>
              
              <div>
                <span class="text-gray-400">Тема:</span>
                <span class="text-white ml-2">{{ getThemeName(form.theme_id) }}</span>
              </div>
              
              <div>
                <span class="text-gray-400">Категории:</span>
                <div class="ml-2 mt-1">
                  <span 
                    v-for="categoryId in form.category_ids" 
                    :key="categoryId"
                    class="inline-block bg-blue-600 text-white px-2 py-1 rounded text-xs mr-1 mb-1"
                  >
                    {{ getCategoryName(categoryId) }}
                  </span>
                  <span v-if="!form.category_ids.length" class="text-gray-400">Не выбраны</span>
                </div>
              </div>
              
              <div>
                <span class="text-gray-400">Атрибуты:</span>
                <div class="ml-2 mt-1 space-y-1">
                  <div 
                    v-for="[attributeId, values] in Object.entries(form.attribute_values)" 
                    :key="attributeId"
                    v-if="values.valueIds.length > 0 || values.customValue"
                    class="text-xs"
                  >
                    <span class="text-gray-300">{{ getAttributeName(attributeId) }}:</span>
                    <span class="text-white ml-1">{{ getAttributeDisplayValue(attributeId, values) }}</span>
                  </div>
                  <span v-if="!Object.keys(form.attribute_values).length" class="text-gray-400 text-xs">
                    Не указаны
                  </span>
                </div>
              </div>
            </div>
          </div>

          <!-- Действия -->
          <div class="bg-gray-800 rounded-lg p-6">
            <div class="space-y-4">
              <button 
                type="submit"
                class="w-full bg-green-600 hover:bg-green-700 text-white py-3 rounded-lg transition-colors font-medium"
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
import { axios } from '../main.js';

export default {
  name: 'ProductCreate',
  setup() {
    const router = useRouter();
    const route = useRoute();
    
    // Состояние
    const loading = ref(false);
    const saving = ref(false);
    const mode = computed(() => route.params.id ? 'edit' : 'create');
    
    // Данные для селектов
    const themes = ref([]);
    const categoryTree = ref([]);
    const attributes = ref([]);
    const availableImages = ref([]);
    
    // Модальные окна
    const showImageSelector = ref(false);
    const imageSelectorTarget = ref(null);
    const imageSelectorIndex = ref(null);
    
    // Форма товара
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
    
    // Методы загрузки данных
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
        availableImages.value = response.data;
      } catch (error) {
        console.error('Ошибка загрузки изображений:', error);
      }
    };
    
    const loadProduct = async () => {
      try {
        loading.value = true;
        const response = await axios.get(`/admin/products/${route.params.id}`);
        const product = response.data;
        
        // Конвертируем старую структуру атрибутов в новую
        const attributeValues = {};
        
        if (product.product_attributes) {
          product.product_attributes.forEach(pa => {
            if (pa.attribute_value_id) {
              if (!attributeValues[pa.attribute_id]) {
                attributeValues[pa.attribute_id] = [];
              }
              attributeValues[pa.attribute_id].push(pa.attribute_value_id);
            }
            
            if (pa.custom_value) {
              attributeValues[`custom_${pa.attribute_id}`] = pa.custom_value;
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
    
    // Методы работы с атрибутами
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
    
    const addCustomAttributeValue = (attributeId, customValue) => {
      // Для кастомных значений используем специальный ключ
      form.attribute_values[`custom_${attributeId}`] = customValue.trim();
    };
    // КРИТИЧЕСКОЕ ИСПРАВЛЕНИЕ: Добавляем отсутствующую функцию setCustomAttributeValue
    const setCustomAttributeValue = (attributeId, customValue) => {
      form.attribute_values[`custom_${attributeId}`] = customValue.trim();
    };
    
    const getAttributeValueDisplay = (attributeId, value) => {
      if (Array.isArray(value)) {
        const attribute = attributesWithValues.value.find(a => a.id == attributeId);
        if (!attribute) return value.join(', ');
        
        return value.map(valId => {
          const val = attribute.values.find(v => v.id == valId);
          return val ? val.value : valId;
        }).join(', ');
      }
      return value;
    };
    

    const isAttributeValueSelected = (attributeId, valueId) => {
      const values = form.attribute_values[attributeId];
      return values && values.includes(valueId);
    };
    
    const getCustomAttributeValue = (attributeId) => {
      return form.attribute_values[attributeId]?.customValue || '';
    };
    
    // Методы работы с изображениями
    const addGalleryImage = () => {
      form.gallery_images.push('');
    };
    
    const removeGalleryImage = (index) => {
      form.gallery_images.splice(index, 1);
    };
    
    const openImageSelector = (target, index = null) => {
      imageSelectorTarget.value = target;
      imageSelectorIndex.value = index;
      showImageSelector.value = true;
      loadAvailableImages();
    };
    
    const closeImageSelector = () => {
      showImageSelector.value = false;
      imageSelectorTarget.value = null;
      imageSelectorIndex.value = null;
    };
    
    const selectImage = (filename) => {
      const target = imageSelectorTarget.value;
      
      if (target === 'outer') {
        form.image_layers.outer = filename;
      } else if (target === 'inner') {
        form.image_layers.inner = filename;
      } else if (target === 'gallery') {
        form.gallery_images[imageSelectorIndex.value] = filename;
      }
      
      closeImageSelector();
    };
    
    const uploadNewImages = async (event) => {
      const files = event.target.files;
      if (!files.length) return;
      
      const formData = new FormData();
      for (let file of files) {
        formData.append('images[]', file);
      }
      
      try {
        saving.value = true;
        await axios.post('/admin/images/upload', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });
        alert('Изображения загружены!');
        loadAvailableImages();
      } catch (error) {
        console.error('Ошибка загрузки изображений:', error);
        alert('Ошибка загрузки изображений');
      } finally {
        saving.value = false;
      }
    };
    
    // Утилиты
    const generateSKU = () => {
      const prefix = 'STJ';
      const timestamp = Date.now().toString().slice(-6);
      const random = Math.random().toString(36).substring(2, 5).toUpperCase();
      form.sku = `${prefix}-${timestamp}-${random}`;
    };
    
    const generateMetaTags = () => {
      if (form.name && !form.meta_title) {
        form.meta_title = `${form.name} - купить в интернет-магазине Стужа`;
      }
      
      if (form.description && !form.meta_description) {
        form.meta_description = form.description.substring(0, 150) + '...';
      }
    };
    
    const formatPrice = (price) => {
      return new Intl.NumberFormat('ru-RU').format(price);
    };
    
    const getCategoryName = (categoryId) => {
      const findCategory = (categories) => {
        for (const category of categories) {
          if (category.id === categoryId) return category.name;
          if (category.children) {
            const found = findCategory(category.children);
            if (found) return found;
          }
        }
        return null;
      };
      
      return findCategory(categoryTree.value) || 'Неизвестная категория';
    };
    
    const getThemeName = (themeId) => {
      const theme = themes.value.find(t => t.id === themeId);
      return theme ? theme.name : 'Неизвестная тема';
    };
    
    const getAttributeName = (attributeId) => {
      const attribute = attributes.value.find(a => a.id === parseInt(attributeId));
      return attribute ? attribute.name : 'Неизвестный атрибут';
    };
    
    const getAttributeDisplayValue = (attributeId, values) => {
      const attribute = attributes.value.find(a => a.id === parseInt(attributeId));
      if (!attribute) return '';
      
      const displayValues = [];
      
      values.valueIds.forEach(valueId => {
        const value = attribute.values.find(v => v.id === valueId);
        if (value) displayValues.push(value.value);
      });
      
      if (values.customValue) {
        displayValues.push(`"${values.customValue}"`);
      }
      
      return displayValues.join(', ');
    };
    
    const getImageUrl = (filename) => {
      return `/storage/images/${filename}`;
    };
    
    const handleImageError = (event) => {
      event.target.src = '/images/placeholder.jpg';
    };
    
    const previewProduct = () => {
      // Здесь можно открыть предпросмотр товара в новой вкладке
      window.open(`/product/${form.slug || form.name}`, '_blank');
    };
    
    // Основные действия
    const saveProduct = async () => {
      try {
        loading.value = true;
        
        const productData = {
          ...form,
          // Добавляем атрибуты в плоском формате
          attributes: form.attribute_values
        };
        
        const isEdit = mode.value === 'edit';
        const url = isEdit ? `/admin/products/${route.params.id}` : '/admin/products';
        const method = isEdit ? 'put' : 'post';
        
        await axios[method](url, productData);
        
        alert(isEdit ? 'Товар обновлен!' : 'Товар создан!');
        router.push('/admin');
      } catch (error) {
        console.error('Ошибка сохранения товара:', error);
        const message = error.response?.data?.message || 'Ошибка сохранения';
        alert('Ошибка: ' + message);
      } finally {
        loading.value = false;
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
      getCategoryName,
      getThemeName,
      getAttributeName,
      getAttributeDisplayValue,
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