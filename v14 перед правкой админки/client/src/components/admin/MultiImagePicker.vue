<template>
  <div class="multi-image-picker">
    <!-- Список выбранных изображений -->
    <div v-if="images.length > 0" class="grid grid-cols-4 gap-3 mb-4">
      <div 
        v-for="(image, index) in images" 
        :key="index"
        class="relative group"
      >
        <img 
          :src="image" 
          class="w-full h-32 object-cover rounded border-2 border-gray-600"
        >
        <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition flex items-center justify-center gap-2">
          <button 
            @click="moveLeft(index)"
            v-if="index > 0"
            type="button"
            class="p-1 bg-white text-black rounded hover:bg-gray-200"
            title="Переместить влево"
          >
            ←
          </button>
          <button 
            @click="removeImage(index)"
            type="button"
            class="p-1 bg-red-600 text-white rounded hover:bg-red-700"
            title="Удалить"
          >
            ×
          </button>
          <button 
            @click="moveRight(index)"
            v-if="index < images.length - 1"
            type="button"
            class="p-1 bg-white text-black rounded hover:bg-gray-200"
            title="Переместить вправо"
          >
            →
          </button>
        </div>
      </div>
    </div>
    
    <p v-else class="text-gray-500 mb-4">
      Изображения не добавлены
    </p>
    
    <!-- Кнопка добавления -->
    <button 
      @click="showLibrary = true" 
      type="button"
      class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
    >
      + Добавить изображения
    </button>
    
    <!-- Модальное окно библиотеки -->
    <div 
      v-if="showLibrary" 
      class="fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4"
      @click.self="showLibrary = false"
    >
      <div class="bg-gray-800 rounded-lg p-6 max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <h3 class="text-xl text-white mb-4">Добавление изображений в галерею</h3>
        
        <!-- Загрузка новых файлов -->
        <div class="mb-4 p-4 bg-gray-700 rounded">
          <label class="block text-gray-400 mb-2">Загрузить новые изображения</label>
          <input 
            type="file" 
            @change="uploadImages" 
            accept="image/*"
            multiple
            class="text-white"
            :disabled="uploading"
          >
          <p v-if="uploading" class="text-blue-400 mt-2">Загрузка {{ uploadProgress }}...</p>
        </div>
        
        <!-- Существующие изображения -->
        <div class="flex-1 overflow-y-auto">
          <p class="text-gray-400 mb-2">Выберите изображения (можно выбрать несколько):</p>
          <div class="grid grid-cols-4 gap-3">
            <div 
              v-for="image in library" 
              :key="image.url"
              @click="toggleImage(image.url)"
              class="cursor-pointer group relative"
              :class="{ 'ring-2 ring-blue-500': tempSelected.includes(image.url) }"
            >
              <img 
                :src="image.url" 
                class="w-full h-32 object-cover rounded"
                :class="{ 'opacity-75': tempSelected.includes(image.url) }"
              >
              <div 
                v-if="tempSelected.includes(image.url)"
                class="absolute top-1 right-1 w-6 h-6 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm"
              >
                ✓
              </div>
            </div>
          </div>
        </div>
        
        <!-- Кнопки действий -->
        <div class="mt-4 pt-4 border-t border-gray-700 flex gap-3">
          <button 
            @click="applySelection"
            :disabled="tempSelected.length === 0"
            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50"
          >
            Добавить выбранные ({{ tempSelected.length }})
          </button>
          <button 
            @click="cancelSelection" 
            class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700"
          >
            Отмена
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, watch } from 'vue'
import { axios } from '../../main.js'

const props = defineProps({
  modelValue: {
    type: Array,
    default: () => []
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

// ✅ ИСПРАВЛЕННЫЕ API ПУТИ (убраны дублированные /api/)
const loadLibrary = async () => {
  try {
    const response = await axios.get('/admin/images')
    library.value = response.data.data || response.data || []
  } catch (error) {
    console.error('Ошибка загрузки библиотеки:', error)
  }
}

// Переключение выбора изображения
const toggleImage = (imageUrl) => {
  const index = tempSelected.value.indexOf(imageUrl)
  if (index > -1) {
    tempSelected.value.splice(index, 1)
  } else {
    tempSelected.value.push(imageUrl)
  }
}

// Применение выбора
const applySelection = () => {
  const newImages = [...images.value, ...tempSelected.value]
  emit('update:modelValue', newImages)
  cancelSelection()
}

// Отмена выбора
const cancelSelection = () => {
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

// ✅ ИСПРАВЛЕННАЯ ЗАГРУЗКА ИЗОБРАЖЕНИЙ
const uploadImages = async (event) => {
  const files = Array.from(event.target.files)
  if (files.length === 0) return
  
  uploading.value = true
  const uploaded = []
  
  for (let i = 0; i < files.length; i++) {
    uploadProgress.value = `${i + 1} из ${files.length}`
    
    const formData = new FormData()
    formData.append('image', files[i])
    formData.append('type', 'product') // указываем тип изображения
    
    try {
      // ✅ ИСПРАВЛЕННЫЙ API ПУТЬ
      const response = await axios.post('/admin/images', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        }
      })
      
      // Получаем URL загруженного изображения
      const newImageUrl = response.data.url || response.data.path
      if (newImageUrl) {
        library.value.unshift({
          url: newImageUrl,
          filename: files[i].name
        })
        uploaded.push(newImageUrl)
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

// При открытии модалки
watch(showLibrary, (newVal) => {
  if (newVal) {
    loadLibrary()
    tempSelected.value = []
  }
})
</script>