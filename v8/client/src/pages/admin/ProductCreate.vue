<template>
  <div class="bg-gray-900 min-h-screen p-8">
    <div class="max-w-6xl mx-auto">
      <!-- Заголовок -->
      <div class="flex justify-between items-center mb-8">
        <h1 class="text-3xl text-white font-bold">Создание товара</h1>
        <router-link to="/admin/products" class="px-4 py-2 bg-gray-700 text-white rounded hover:bg-gray-600">
          ← Назад к списку
        </router-link>
      </div>

      <!-- Форма -->
      <form @submit.prevent="saveProduct">
        <!-- Основная информация -->
        <div class="bg-gray-800 rounded-lg p-6 mb-6">
          <h2 class="text-xl text-white font-semibold mb-4">Основная информация</h2>
          
          <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label class="block text-gray-400 mb-2">Название товара *</label>
              <input 
                v-model="product.name" 
                type="text" 
                class="w-full px-4 py-2 bg-gray-700 text-white rounded border border-gray-600 focus:border-blue-500 focus:outline-none"
                required
              >
            </div>
            
            <div>
              <label class="block text-gray-400 mb-2">Цена *</label>
              <input 
                v-model.number="product.price" 
                type="number" 
                step="0.01"
                class="w-full px-4 py-2 bg-gray-700 text-white rounded border border-gray-600 focus:border-blue-500 focus:outline-none"
                required
              >
            </div>
          </div>
          
          <div class="mt-4">
            <label class="block text-gray-400 mb-2">Описание</label>
            <textarea 
              v-model="product.description" 
              rows="4"
              class="w-full px-4 py-2 bg-gray-700 text-white rounded border border-gray-600 focus:border-blue-500 focus:outline-none"
            ></textarea>
          </div>
          
          <div class="mt-4">
            <label class="flex items-center text-white cursor-pointer">
              <input 
                v-model="product.use_matryoshka" 
                type="checkbox"
                class="mr-2 w-5 h-5"
              >
              <span>Использовать эффект матрёшки</span>
            </label>
          </div>
        </div>

        <!-- Изображения -->
        <div class="bg-gray-800 rounded-lg p-6 mb-6">
          <h2 class="text-xl text-white font-semibold mb-4">Изображения</h2>
          
          <!-- Слои матрёшки -->
          <div v-if="product.use_matryoshka" class="mb-6 p-4 bg-gray-700 rounded">
            <h3 class="text-lg text-white mb-4">Слои для эффекта матрёшки</h3>
            
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
              <div>
                <label class="block text-gray-400 mb-2">Внешний слой *</label>
                <ImagePicker 
                  v-model="product.image_layers.outer"
                  placeholder="Выберите внешний слой"
                />
              </div>
              
              <div>
                <label class="block text-gray-400 mb-2">Внутренний слой *</label>
                <ImagePicker 
                  v-model="product.image_layers.inner"
                  placeholder="Выберите внутренний слой"
                />
              </div>
            </div>
          </div>
          
          <!-- Галерея -->
          <div>
            <h3 class="text-lg text-white mb-4">Галерея изображений</h3>
            <MultiImagePicker v-model="product.gallery_images" />
          </div>
        </div>

        <!-- Классификация -->
        <div class="bg-gray-800 rounded-lg p-6 mb-6">
          <h2 class="text-xl text-white font-semibold mb-4">Классификация</h2>
          
          <!-- Тема -->
          <div class="mb-6">
            <label class="block text-gray-400 mb-2">Тема</label>
            <div class="flex gap-2">
              <select 
                v-model="product.theme_id" 
                class="flex-1 px-4 py-2 bg-gray-700 text-white rounded border border-gray-600 focus:border-blue-500 focus:outline-none"
              >
                <option value="">Выберите тему</option>
                <option v-for="theme in themes" :key="theme.id" :value="theme.id">
                  {{ theme.name }}
                </option>
              </select>
              <button 
                @click="showAddTheme = true" 
                type="button"
                class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
              >
                + Добавить
              </button>
            </div>
          </div>
          
          <!-- Категории -->
          <div class="mb-6">
            <div class="flex justify-between items-center mb-2">
              <label class="block text-gray-400">Категории</label>
              <button 
                @click="showAddCategory = true" 
                type="button"
                class="text-sm px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700"
              >
                + Добавить категорию
              </button>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
              <label 
                v-for="category in categories" 
                :key="category.id"
                class="flex items-center p-2 bg-gray-700 rounded cursor-pointer hover:bg-gray-600"
              >
                <input 
                  type="checkbox" 
                  :value="category.id" 
                  v-model="product.category_ids"
                  class="mr-2"
                >
                <span class="text-white">{{ category.name }}</span>
              </label>
            </div>
          </div>
          
          <!-- Атрибуты -->
          <div>
            <div class="flex justify-between items-center mb-2">
              <label class="block text-gray-400">Атрибуты (камни, материалы)</label>
              <button 
                @click="showAddAttribute = true" 
                type="button"
                class="text-sm px-3 py-1 bg-blue-600 text-white rounded hover:bg-blue-700"
              >
                + Добавить атрибут
              </button>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
              <label 
                v-for="attribute in attributes" 
                :key="attribute.id"
                class="flex items-center p-2 bg-gray-700 rounded cursor-pointer hover:bg-gray-600"
              >
                <input 
                  type="checkbox" 
                  :value="attribute.id" 
                  v-model="product.attribute_ids"
                  class="mr-2"
                >
                <span class="text-white">{{ attribute.name }}</span>
              </label>
            </div>
          </div>
        </div>

        <!-- Кнопки действий -->
        <div class="flex gap-4">
          <button 
            type="submit" 
            :disabled="loading"
            class="px-6 py-3 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50"
          >
            {{ loading ? 'Сохранение...' : 'Сохранить товар' }}
          </button>
          <router-link 
            to="/admin/products" 
            class="px-6 py-3 bg-gray-600 text-white rounded-lg hover:bg-gray-700"
          >
            Отмена
          </router-link>
        </div>
      </form>
    </div>

    <!-- Модальные окна для быстрого добавления -->
    <QuickAddTheme 
      v-if="showAddTheme" 
      @close="showAddTheme = false" 
      @added="handleThemeAdded" 
    />
    <QuickAddCategory 
      v-if="showAddCategory" 
      @close="showAddCategory = false" 
      @added="handleCategoryAdded" 
    />
    <QuickAddAttribute 
      v-if="showAddAttribute" 
      @close="showAddAttribute = false" 
      @added="handleAttributeAdded" 
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import axios from 'axios'
import ImagePicker from '../../components/admin/ImagePicker.vue'
import MultiImagePicker from '../../components/admin/MultiImagePicker.vue'
import QuickAddTheme from '../../components/admin/QuickAddTheme.vue'
import QuickAddCategory from '../../components/admin/QuickAddCategory.vue'
import QuickAddAttribute from '../../components/admin/QuickAddAttribute.vue'

const router = useRouter()
const loading = ref(false)

// Данные товара
const product = ref({
  name: '',
  description: '',
  price: 0,
  use_matryoshka: false,
  image_layers: {
    outer: '',
    inner: ''
  },
  gallery_images: [],
  theme_id: '',
  category_ids: [],
  attribute_ids: []
})

// Справочники
const themes = ref([])
const categories = ref([])
const attributes = ref([])

// Управление модалками
const showAddTheme = ref(false)
const showAddCategory = ref(false)
const showAddAttribute = ref(false)

// Загрузка справочников
const loadThemes = async () => {
  try {
    const response = await axios.get('/api/admin/themes')
    themes.value = response.data.data || []
  } catch (error) {
    console.error('Ошибка загрузки тем:', error)
  }
}

const loadCategories = async () => {
  try {
    const response = await axios.get('/api/admin/categories')
    categories.value = response.data.data || []
  } catch (error) {
    console.error('Ошибка загрузки категорий:', error)
  }
}

const loadAttributes = async () => {
  try {
    const response = await axios.get('/api/admin/attributes')
    attributes.value = response.data.data || []
  } catch (error) {
    console.error('Ошибка загрузки атрибутов:', error)
  }
}

// Сохранение товара
const saveProduct = async () => {
  loading.value = true
  
  try {
    // Подготовка данных
    const data = {
      ...product.value,
      slug: '', // Будет сгенерирован на бэкенде
      image_layers: product.value.use_matryoshka ? product.value.image_layers : null,
      gallery_images: product.value.gallery_images.filter(img => img) // Убираем пустые
    }
    
    await axios.post('/api/admin/products', data)
    
    // Успех
    alert('Товар успешно создан!')
    router.push('/admin/products')
  } catch (error) {
    console.error('Ошибка сохранения:', error)
    alert('Ошибка при сохранении товара: ' + (error.response?.data?.message || error.message))
  } finally {
    loading.value = false
  }
}

// Обработчики добавления
const handleThemeAdded = (theme) => {
  themes.value.push(theme)
  product.value.theme_id = theme.id
  showAddTheme.value = false
}

const handleCategoryAdded = (category) => {
  categories.value.push(category)
  product.value.category_ids.push(category.id)
  showAddCategory.value = false
}

const handleAttributeAdded = (attribute) => {
  attributes.value.push(attribute)
  product.value.attribute_ids.push(attribute.id)
  showAddAttribute.value = false
}

// При монтировании
onMounted(() => {
  loadThemes()
  loadCategories()
  loadAttributes()
})
</script>