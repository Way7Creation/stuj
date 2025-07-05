<template>
  <div class="multi-image-picker">
    <!-- Список выбранных изображений -->
    <div v-if="images.length > 0" class="grid grid-cols-3 lg:grid-cols-4 gap-3 mb-4">
      <div 
        v-for="(image, index) in images" 
        :key="index"
        class="relative group"
      >
        <img 
          :src="getImageUrl(image)" 
          :alt="`Изображение ${index + 1}`"
          class="w-full h-32 object-cover rounded-lg border-2 border-gray-600"
          @error="handleImageError"
        >
        <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 rounded-lg transition-opacity flex items-center justify-center gap-2">
          <button 
            @click="moveLeft(index)"
            v-if="index > 0"
            type="button"
            class="opacity-0 group-hover:opacity-100 p-2 bg-white text-black rounded-full hover:bg-gray-200 transition-all"
            title="Переместить влево"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7" />
            </svg>
          </button>
          <button 
            @click="removeImage(index)"
            type="button"
            class="opacity-0 group-hover:opacity-100 p-2 bg-red-600 text-white rounded-full hover:bg-red-700 transition-all"
            title="Удалить"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
            </svg>
          </button>
          <button 
            @click="moveRight(index)"
            v-if="index < images.length - 1"
            type="button"
            class="opacity-0 group-hover:opacity-100 p-2 bg-white text-black rounded-full hover:bg-gray-200 transition-all"
            title="Переместить вправо"
          >
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
            </svg>
          </button>
        </div>
        <div class="absolute top-2 left-2 bg-black bg-opacity-75 text-white text-xs px-2 py-1 rounded">
          {{ index + 1 }}
        </div>
      </div>
    </div>
    
    <div v-else class="text-gray-500 mb-4 text-center py-8 border-2 border-dashed border-gray-600 rounded-lg">
      Изображения не добавлены
    </div>
    
    <!-- Кнопка добавления -->
    <button 
      @click="showLibrary = true" 
      type="button"
      class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition-colors"
      :disabled="disabled"
    >
      + {{ images.length === 0 ? 'Добавить изображения' : 'Добавить еще' }}
    </button>
    
    <!-- Модальное окно библиотеки -->
    <div 
      v-if="showLibrary" 
      class="fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4"
      @click.self="showLibrary = false"
    >
      <div class="bg-gray-800 rounded-lg p-6 max-w-5xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl text-white">Добавление изображений в галерею</h3>
          <button 
            @click="closeLibrary"
            class="text-gray-400 hover:text-white text-2xl leading-none"
          >
            ×
          </button>
        </div>
        
        <!-- Загрузка новых файлов -->
        <div class="mb-4 p-4 bg-gray-700 rounded-lg">
          <label class="block text-gray-400 mb-2">Загрузить новые изображения</label>
          <input 
            type="file" 
            @change="uploadImages" 
            accept="image/*"
            multiple
            class="text-white bg-gray-600 rounded px-3 py-2 w-full"
            :disabled="uploading"
          >
          <div v-if="uploading" class="text-blue-400 mt-2 flex items-center">
            <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-400 mr-2"></div>
            Загрузка {{ uploadProgress }}...
          </div>
        </div>
        
        <!-- Счетчик выбранных -->
        <div v-if="tempSelected.length > 0" class="mb-3 p-2 bg-blue-900 rounded">
          <p class="text-blue-200 text-sm">
            Выбрано: {{ tempSelected.length }} {{ tempSelected.length === 1 ? 'изображение' : 'изображений' }}
          </p>
        </div>
        
        <!-- Существующие изображения -->
        <div class="flex-1 overflow-y-auto">
          <div v-if="library.length === 0" class="text-gray-400 text-center py-8">
            Изображения не найдены
          </div>
          <div v-else>
            <p class="text-gray-400 mb-3">Выберите изображения (можно выбрать несколько):</p>
            <div class="grid grid-cols-4 lg:grid-cols-6 gap-3">
              <div 
                v-for="image in library" 
                :key="image.filename"
                @click="toggleImage(image.filename)"
                class="cursor-pointer group relative transition-all"
                :class="{ 
                  'ring-2 ring-blue-500': tempSelected.includes(image.filename),
                  'opacity-50': images.includes(image.filename) && !tempSelected.includes(image.filename)
                }"
              >
                <img 
                  :src="getImageUrl(image.filename)" 
                  :alt="image.filename"
                  class="w-full h-24 object-cover rounded-lg"
                  :class="{ 'opacity-75': tempSelected.includes(image.filename) }"
                  @error="handleImageError"
                >
                <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-25 rounded-lg transition-opacity"></div>
                
                <!-- Индикатор выбора -->
                <div 
                  v-if="tempSelected.includes(image.filename)"
                  class="absolute top-1 right-1 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold"
                >
                  ✓
                </div>
                
                <!-- Индикатор уже добавленного -->
                <div 
                  v-else-if="images.includes(image.filename)"
                  class="absolute top-1 right-1 w-6 h-6 bg-gray-600 text-white rounded-full flex items-center justify-center text-sm"
                >
                  ●
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
            @click="selectAll"
            v-if="availableForSelection.length > 0"
            class="px-4 py-2 bg-yellow-600 text-white rounded-lg hover:bg-yellow-700 transition-colors"
          >
            Выбрать все доступные
          </button>
          <button 
            @click="clearSelection"
            v-if="tempSelected.length > 0"
            class="px-4 py-2 bg-red-600 text-white rounded-lg hover:bg-red-700 transition-colors"
          >
            Снять выбор
          </button>
          <button 
            @click="applySelection"
            :disabled="tempSelected.length === 0"
            class="px-4 py-2 bg-green-600 text-white rounded-lg hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-colors"
          >
            Добавить выбранные ({{ tempSelected.length }})
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import axios from 'axios'

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
  },
  disabled: {
    type: Boolean,
    default: false
  },
  maxImages: {
    type: Number,
    default: 20
  },
  imageType: {
    type: String,
    default: 'product'
  }
})

const emit = defineEmits(['update:modelValue'])

const showLibrary = ref(false)
const library = ref([])
const tempSelected = ref([])
const uploading = ref(false)
const uploadProgress = ref('')

// Локальная копия изображений для работы
const images = computed({
  get: () => props.modelValue,
  set: (value) => emit('update:modelValue', value)
})

// Доступные для выбора изображения (исключая уже добавленные)
const availableForSelection = computed(() => {
  return library.value.filter(image => !images.value.includes(image.filename))
})

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

// Переключение выбора изображения
const toggleImage = (filename) => {
  // Не даем выбрать уже добавленное изображение
  if (images.value.includes(filename)) return
  
  const index = tempSelected.value.indexOf(filename)
  if (index > -1) {
    tempSelected.value.splice(index, 1)
  } else {
    tempSelected.value.push(filename)
  }
}

// Применение выбора
const applySelection = () => {
  const newImages = [...images.value, ...tempSelected.value]
  
  // Ограничиваем количество изображений
  if (newImages.length > props.maxImages) {
    alert(`Максимальное количество изображений: ${props.maxImages}`)
    return
  }
  
  emit('update:modelValue', newImages)
  closeLibrary()
}

// Выбрать все доступные
const selectAll = () => {
  tempSelected.value = availableForSelection.value.map(img => img.filename)
}

// Снять весь выбор
const clearSelection = () => {
  tempSelected.value = []
}

// Закрытие библиотеки
const closeLibrary = () => {
  tempSelected.value = []
  showLibrary.value = false
}

// Удаление изображения
const removeImage = (index) => {
  const newImages = [...images.value]
  newImages.splice(index, 1)
  emit('update:modelValue', newImages)
}

// Перемещение влево
const moveLeft = (index) => {
  if (index > 0) {
    const newImages = [...images.value]
    ;[newImages[index - 1], newImages[index]] = [newImages[index], newImages[index - 1]]
    emit('update:modelValue', newImages)
  }
}

// Перемещение вправо
const moveRight = (index) => {
  if (index < images.value.length - 1) {
    const newImages = [...images.value]
    ;[newImages[index], newImages[index + 1]] = [newImages[index + 1], newImages[index]]
    emit('update:modelValue', newImages)
  }
}

// Загрузка изображений
const uploadImages = async (event) => {
  const files = Array.from(event.target.files)
  if (files.length === 0) return
  
  uploading.value = true
  const uploaded = []
  
  for (let i = 0; i < files.length; i++) {
    uploadProgress.value = `${i + 1} из ${files.length}`
    
    const formData = new FormData()
    formData.append('image', files[i])
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
        library.value.unshift({
          filename: filename,
          url: response.data.url,
          thumbnail_url: response.data.thumbnail_url
        })
        uploaded.push(filename)
      }
    } catch (error) {
      console.error(`Ошибка загрузки файла ${files[i].name}:`, error)
      alert(`Ошибка загрузки файла ${files[i].name}`)
    }
  }
  
  // Добавляем загруженные к выбранным
  tempSelected.value.push(...uploaded)
  
  uploading.value = false
  uploadProgress.value = ''
  event.target.value = ''
}

// Получение URL изображения
const getImageUrl = (filename) => {
  if (!filename) return '/placeholder.jpg'
  if (filename.startsWith('http')) return filename
  
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

// При открытии модалки
watch(showLibrary, (newVal) => {
  if (newVal) {
    loadLibrary()
    tempSelected.value = []
  }
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

/* Улучшение файлового инпута */
input[type="file"] {
  cursor: pointer;
}

input[type="file"]:disabled {
  opacity: 0.5;
  cursor: not-allowed;
}

/* Responsive */
@media (max-width: 1024px) {
  .grid-cols-4 {
    grid-template-columns: repeat(3, 1fr);
  }
  
  .lg\\:grid-cols-6 {
    grid-template-columns: repeat(4, 1fr);
  }
}

@media (max-width: 768px) {
  .grid-cols-3 {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .grid-cols-4 {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .lg\\:grid-cols-4 {
    grid-template-columns: repeat(2, 1fr);
  }
  
  .lg\\:grid-cols-6 {
    grid-template-columns: repeat(3, 1fr);
  }
}

@media (max-width: 480px) {
  .grid-cols-3,
  .grid-cols-4,
  .lg\\:grid-cols-4,
  .lg\\:grid-cols-6 {
    grid-template-columns: repeat(2, 1fr);
  }
}
</style>