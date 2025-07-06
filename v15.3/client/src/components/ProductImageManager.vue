<!-- ProductImageManager.vue - компонент управления изображениями -->
<template>
  <div class="product-image-manager">
    <!-- Загрузка новых изображений -->
    <div class="upload-section mb-6">
      <h3 class="text-lg font-semibold text-white mb-3">Изображения товара</h3>
      
      <div class="upload-area">
        <input
          ref="fileInput"
          type="file"
          multiple
          accept="image/*"
          class="hidden"
          @change="handleFileSelect"
        />
        
        <div
          @click="$refs.fileInput.click()"
          @drop.prevent="handleDrop"
          @dragover.prevent
          @dragenter.prevent
          class="border-2 border-dashed border-gray-600 rounded-lg p-8 text-center cursor-pointer hover:border-blue-500 transition-colors"
        >
          <svg class="w-12 h-12 mx-auto mb-4 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"></path>
          </svg>
          <p class="text-gray-300 mb-2">Перетащите изображения сюда или кликните для выбора</p>
          <p class="text-gray-500 text-sm">Поддерживаются: JPG, PNG, GIF, WebP (макс. 5MB)</p>
        </div>
      </div>

      <!-- Прогресс загрузки -->
      <div v-if="uploadProgress.length > 0" class="mt-4 space-y-2">
        <div v-for="(file, index) in uploadProgress" :key="index" class="bg-gray-700 rounded p-3">
          <div class="flex justify-between items-center mb-1">
            <span class="text-sm text-white truncate">{{ file.name }}</span>
            <span class="text-xs text-gray-400">{{ file.progress }}%</span>
          </div>
          <div class="w-full bg-gray-600 rounded-full h-2">
            <div
              class="bg-blue-600 h-2 rounded-full transition-all duration-300"
              :style="{width: file.progress + '%'}"
            ></div>
          </div>
        </div>
      </div>
    </div>

    <!-- Список изображений с drag & drop -->
    <div v-if="images.length > 0" class="images-grid">
      <h4 class="text-md font-medium text-white mb-3">
        Текущие изображения (перетащите для изменения порядка)
      </h4>
      
      <draggable
        v-model="images"
        @end="onDragEnd"
        item-key="id"
        class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4"
        ghost-class="opacity-50"
        animation="200"
      >
        <template #item="{element: image}">
          <div
            class="image-item relative group bg-gray-800 rounded-lg overflow-hidden cursor-move"
            :class="{'ring-2 ring-green-500': image.is_main}"
          >
            <!-- Изображение -->
            <img
              :src="image.url"
              :alt="image.filename"
              class="w-full h-32 object-cover"
              @error="handleImageError"
            />
            
            <!-- Метка главного изображения -->
            <div v-if="image.is_main" class="absolute top-2 left-2">
              <span class="bg-green-600 text-white text-xs px-2 py-1 rounded">
                Главное
              </span>
            </div>
            
            <!-- Действия (показываются при наведении) -->
            <div class="absolute inset-0 bg-black bg-opacity-75 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center space-x-2">
              <!-- Сделать главным -->
              <button
                v-if="!image.is_main"
                @click="setMainImage(image)"
                class="bg-green-600 hover:bg-green-700 text-white p-2 rounded transition-colors"
                title="Сделать главным"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path>
                </svg>
              </button>
              
              <!-- Удалить -->
              <button
                @click="deleteImage(image)"
                class="bg-red-600 hover:bg-red-700 text-white p-2 rounded transition-colors"
                title="Удалить"
              >
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
              </button>
            </div>
            
            <!-- Номер порядка -->
            <div class="absolute bottom-2 right-2 bg-gray-900 bg-opacity-75 text-white text-xs px-2 py-1 rounded">
              #{{ images.indexOf(image) + 1 }}
            </div>
          </div>
        </template>
      </draggable>
    </div>
    
    <!-- Пустое состояние -->
    <div v-else class="text-center py-8">
      <p class="text-gray-400">Нет загруженных изображений</p>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue'
import { VueDraggableNext } from 'vue-draggable-next'
import { axios } from '@/main.js'

// Регистрируем draggable компонент
const draggable = VueDraggableNext

// Props
const props = defineProps({
  productId: {
    type: Number,
    required: false,
    default: null
  },
  initialImages: {
    type: Array,
    default: () => []
  }
})

// Emit события
const emit = defineEmits(['imagesUpdated'])

// Reactive данные
const images = ref([...props.initialImages])
const uploadProgress = ref([])
const isUploading = ref(false)

// Watchers
watch(() => props.initialImages, (newImages) => {
  images.value = [...newImages]
})

// Обработка выбора файлов
const handleFileSelect = (event) => {
  const files = Array.from(event.target.files)
  uploadFiles(files)
}

// Обработка drag & drop файлов
const handleDrop = (event) => {
  const files = Array.from(event.dataTransfer.files)
  const imageFiles = files.filter(file => file.type.startsWith('image/'))
  
  if (imageFiles.length > 0) {
    uploadFiles(imageFiles)
  }
}

// Загрузка файлов
const uploadFiles = async (files) => {
  isUploading.value = true
  
  for (const file of files) {
    // Проверка размера
    if (file.size > 5 * 1024 * 1024) {
      alert(`Файл ${file.name} слишком большой. Максимальный размер: 5MB`)
      continue
    }
    
    // Добавляем в прогресс
    const progressItem = {
      name: file.name,
      progress: 0
    }
    uploadProgress.value.push(progressItem)
    
    try {
      const formData = new FormData()
      formData.append('image', file)
      formData.append('type', 'product')
      
      if (props.productId) {
        formData.append('product_id', props.productId)
      }
      
      const response = await axios.post('/admin/images/upload', formData, {
        headers: {
          'Content-Type': 'multipart/form-data'
        },
        onUploadProgress: (progressEvent) => {
          progressItem.progress = Math.round((progressEvent.loaded * 100) / progressEvent.total)
        }
      })
      
      // Добавляем загруженное изображение
      images.value.push(response.data.image)
      
      // Убираем из прогресса
      uploadProgress.value = uploadProgress.value.filter(p => p !== progressItem)
      
      // Уведомляем родителя
      emit('imagesUpdated', images.value)
      
    } catch (error) {
      console.error('Ошибка загрузки:', error)
      alert(`Ошибка загрузки файла ${file.name}`)
      
      // Убираем из прогресса
      uploadProgress.value = uploadProgress.value.filter(p => p !== progressItem)
    }
  }
  
  isUploading.value = false
}

// Удаление изображения
const deleteImage = async (image) => {
  if (!confirm('Удалить это изображение?')) {
    return
  }
  
  try {
    await axios.delete(`/admin/images/${image.id}`)
    
    // Удаляем из списка
    images.value = images.value.filter(img => img.id !== image.id)
    
    // Уведомляем родителя
    emit('imagesUpdated', images.value)
    
  } catch (error) {
    console.error('Ошибка удаления:', error)
    alert('Ошибка удаления изображения')
  }
}

// Установка главного изображения
const setMainImage = async (image) => {
  try {
    await axios.post('/admin/images/set-main', {
      image_id: image.id
    })
    
    // Обновляем флаги
    images.value.forEach(img => {
      img.is_main = img.id === image.id
    })
    
    // Уведомляем родителя
    emit('imagesUpdated', images.value)
    
  } catch (error) {
    console.error('Ошибка установки главного изображения:', error)
    alert('Ошибка установки главного изображения')
  }
}

// Обработка окончания перетаскивания
const onDragEnd = async () => {
  // Обновляем порядок сортировки
  const reorderedImages = images.value.map((image, index) => ({
    id: image.id,
    sort_order: index
  }))
  
  try {
    await axios.post('/admin/images/reorder', {
      images: reorderedImages
    })
    
    // Уведомляем родителя
    emit('imagesUpdated', images.value)
    
  } catch (error) {
    console.error('Ошибка изменения порядка:', error)
    alert('Ошибка изменения порядка изображений')
  }
}

// Обработка ошибки загрузки изображения
const handleImageError = (event) => {
  event.target.src = '/placeholder-image.png' // Заглушка
}
</script>

<style scoped>
/* Стили для мобильной адаптивности */
@media (max-width: 640px) {
  .images-grid .grid {
    grid-template-columns: repeat(2, 1fr) !important;
  }
}

/* Анимация при перетаскивании */
.sortable-ghost {
  opacity: 0.4;
}

.image-item {
  transition: all 0.3s ease;
}

.image-item:hover {
  transform: translateY(-2px);
  box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
}
</style>