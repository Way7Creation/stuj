<template>
  <div class="image-picker">
    <!-- Выбранное изображение -->
    <div v-if="modelValue" class="mb-3">
      <div class="relative inline-block">
        <img 
          :src="getImageUrl(modelValue)" 
          :alt="alt || 'Выбранное изображение'"
          class="w-32 h-32 object-cover rounded-lg border-2 border-gray-600"
          @error="handleImageError"
        >
        <button 
          @click="removeImage" 
          type="button"
          class="absolute -top-2 -right-2 w-6 h-6 bg-red-600 text-white rounded-full text-xs hover:bg-red-700 transition-colors"
        >
          ×
        </button>
      </div>
    </div>
    
    <!-- Кнопка выбора -->
    <button 
      @click="showLibrary = true" 
      type="button"
      class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
      :disabled="disabled"
    >
      {{ modelValue ? 'Изменить изображение' : (placeholder || 'Выбрать изображение') }}
    </button>
    
    <!-- Модальное окно библиотеки -->
    <div 
      v-if="showLibrary" 
      class="fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4"
      @click.self="showLibrary = false"
    >
      <div class="bg-gray-800 rounded-lg p-6 max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl text-white">{{ title || 'Выбор изображения' }}</h3>
          <button 
            @click="closeLibrary"
            class="text-gray-400 hover:text-white text-2xl leading-none"
          >
            ×
          </button>
        </div>
        
        <!-- Загрузка нового файла -->
        <div class="mb-4 p-4 bg-gray-700 rounded-lg">
          <label class="block text-gray-400 mb-2">Загрузить новое изображение</label>
          <input 
            type="file" 
            @change="uploadImage" 
            accept="image/*"
            class="text-white bg-gray-600 rounded px-3 py-2 w-full"
            :disabled="uploading"
          >
          <div v-if="uploading" class="text-blue-400 mt-2 flex items-center">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-400 mr-2"></div>
            Загрузка...
          </div>
        </div>
        
        <!-- Существующие изображения -->
        <div class="flex-1 overflow-y-auto">
          <div v-if="library.length === 0" class="text-gray-400 text-center py-8">
            Изображения не найдены
          </div>
          <div v-else>
            <p class="text-gray-400 mb-3">Или выберите из загруженных:</p>
            <div class="grid grid-cols-4 gap-3">
              <div 
                v-for="image in library" 
                :key="image.filename"
                @click="selectImage(image.filename)"
                class="cursor-pointer group relative hover:ring-2 hover:ring-blue-500 rounded-lg transition-all"
                :class="{ 'ring-2 ring-blue-500': modelValue === image.filename }"
              >
                <img 
                  :src="getImageUrl(image.filename)" 
                  :alt="image.filename"
                  class="w-full h-32 object-cover rounded-lg"
                  @error="handleImageError"
                >
                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-25 rounded-lg transition-opacity"></div>
                <div 
                  v-if="modelValue === image.filename"
                  class="absolute top-2 right-2 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm"
                >
                  ✓
                </div>
                <div class="absolute bottom-1 left-1 right-1">
                  <p class="text-white text-xs bg-black bg-opacity-75 p-1 rounded truncate">
                    {{ image.filename }}
                  </p>
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Кнопки действий -->
        <div class="mt-4 pt-4 border-t border-gray-700 flex gap-3 justify-end">
          <button 
            @click="closeLibrary" 
            class="px-4 py-2 bg-gray-600 text-white rounded-lg hover:bg-gray-700 transition-colors"
          >
            Отмена
          </button>
          <button 
            v-if="selectedImage && selectedImage !== modelValue"
            @click="confirmSelection"
            class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
          >
            Выбрать
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  modelValue: {
    type: String,
    default: ''
  },
  placeholder: {
    type: String,
    default: 'Выбрать изображение'
  },
  title: {
    type: String,
    default: 'Выбор изображения'
  },
  alt: {
    type: String,
    default: ''
  },
  disabled: {
    type: Boolean,
    default: false
  },
  imageType: {
    type: String,
    default: 'product',
    validator: (value) => ['product', 'matryoshka_outer', 'matryoshka_inner'].includes(value)
  }
})

const emit = defineEmits(['update:modelValue'])

const showLibrary = ref(false)
const library = ref([])
const uploading = ref(false)
const selectedImage = ref(props.modelValue)

// Загрузка библиотеки изображений
const loadLibrary = async () => {
  try {
    const response = await axios.get('/admin/images')
    library.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Ошибка загрузки библиотеки:', error)
    library.value = []
  }
}

// Загрузка нового изображения
const uploadImage = async (event) => {
  const file = event.target.files[0]
  if (!file) return
  
  uploading.value = true
  
  const formData = new FormData()
  formData.append('image', file)
  formData.append('type', props.imageType)
  
  try {
    const response = await axios.post('/admin/images', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    
    // Получаем filename из ответа
    const filename = response.data.filename
    
    if (filename) {
      // Добавляем новое изображение в начало списка
      library.value.unshift({
        filename: filename,
        url: response.data.url,
        thumbnail_url: response.data.thumbnail_url
      })
      
      // Автоматически выбираем загруженное изображение
      selectedImage.value = filename
      emit('update:modelValue', filename)
    }
    
  } catch (error) {
    console.error('Ошибка загрузки изображения:', error)
    alert('Ошибка загрузки изображения')
  } finally {
    uploading.value = false
    event.target.value = ''
  }
}

// Выбор изображения
const selectImage = (filename) => {
  selectedImage.value = filename
  emit('update:modelValue', filename)
  closeLibrary()
}

// Подтверждение выбора
const confirmSelection = () => {
  emit('update:modelValue', selectedImage.value)
  closeLibrary()
}

// Удаление выбранного изображения
const removeImage = () => {
  emit('update:modelValue', '')
  selectedImage.value = ''
}

// Закрытие библиотеки
const closeLibrary = () => {
  showLibrary.value = false
  selectedImage.value = props.modelValue
}

// Получение URL изображения
const getImageUrl = (filename) => {
  if (!filename) return '/placeholder.jpg'
  if (filename.startsWith('http')) return filename
  
  // Определяем папку по типу изображения
  const folder = props.imageType === 'matryoshka_outer' 
    ? 'matryoshka/outer' 
    : props.imageType === 'matryoshka_inner' 
    ? 'matryoshka/inner' 
    : 'products'
    
  return `/storage/images/${folder}/${filename}`
}

// Обработка ошибки загрузки изображения
const handleImageError = (event) => {
  event.target.src = '/placeholder.jpg'
}

// Следим за изменениями modelValue
watch(() => props.modelValue, (newValue) => {
  selectedImage.value = newValue
})

// При открытии модалки
watch(showLibrary, (newVal) => {
  if (newVal) {
    loadLibrary()
    selectedImage.value = props.modelValue
  }
})

// Загружаем библиотеку при монтировании компонента
onMounted(() => {
  selectedImage.value = props.modelValue
})
</script>

<style scoped>
/* Анимация загрузки */
.animate-spin {
  animation: spin 1s linear infinite;
}

@keyframes spin {
  from { transform: rotate(0deg); }
  to { transform: rotate(360deg); }
}

/* Улучшение файлового инпута */
input[type="file"] {
  cursor: pointer;
}

input[type="file"]:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Стили для скроллбара */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #374151;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb {
  background: #6B7280;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #9CA3AF;
}

/* Responsive */
@media (max-width: 768px) {
  .grid-cols-4 {
    grid-template-columns: repeat(2, 1fr);
  }
}

@media (max-width: 480px) {
  .grid-cols-4 {
    grid-template-columns: 1fr;
  }
}
</style>