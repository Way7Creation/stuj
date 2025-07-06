<template>
  <div class="image-picker">
    <!-- Выбранное изображение -->
    <div v-if="modelValue" class="mb-3">
      <div class="relative inline-block">
        <img 
          :src="modelValue" 
          class="w-32 h-32 object-cover rounded border-2 border-gray-600"
        >
        <button 
          @click="removeImage" 
          type="button"
          class="absolute -top-2 -right-2 w-6 h-6 bg-red-600 text-white rounded-full text-xs hover:bg-red-700"
        >
          ×
        </button>
      </div>
    </div>
    
    <!-- Кнопка выбора -->
    <button 
      @click="showLibrary = true" 
      type="button"
      class="px-4 py-2 bg-blue-600 text-white rounded hover:bg-blue-700"
    >
      {{ modelValue ? 'Изменить изображение' : placeholder || 'Выбрать изображение' }}
    </button>
    
    <!-- Модальное окно библиотеки -->
    <div 
      v-if="showLibrary" 
      class="fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4"
      @click.self="showLibrary = false"
    >
      <div class="bg-gray-800 rounded-lg p-6 max-w-4xl w-full max-h-[90vh] overflow-hidden flex flex-col">
        <h3 class="text-xl text-white mb-4">Выбор изображения</h3>
        
        <!-- Загрузка нового файла -->
        <div class="mb-4 p-4 bg-gray-700 rounded">
          <label class="block text-gray-400 mb-2">Загрузить новое изображение</label>
          <input 
            type="file" 
            @change="uploadImage" 
            accept="image/*"
            class="text-white"
            :disabled="uploading"
          >
          <p v-if="uploading" class="text-blue-400 mt-2">Загрузка...</p>
        </div>
        
        <!-- Существующие изображения -->
        <div class="flex-1 overflow-y-auto">
          <p class="text-gray-400 mb-2">Или выберите из загруженных:</p>
          <div class="grid grid-cols-4 gap-3">
            <div 
              v-for="image in library" 
              :key="image"
              @click="selectImage(image)"
              class="cursor-pointer group relative"
            >
              <img 
                :src="image" 
                class="w-full h-32 object-cover rounded group-hover:opacity-75 transition"
              >
              <div class="absolute inset-0 flex items-center justify-center opacity-0 group-hover:opacity-100 transition">
                <span class="bg-black bg-opacity-75 text-white px-2 py-1 rounded text-sm">
                  Выбрать
                </span>
              </div>
            </div>
          </div>
          
          <p v-if="library.length === 0" class="text-gray-500 text-center py-8">
            Нет загруженных изображений
          </p>
        </div>
        
        <!-- Кнопка закрытия -->
        <div class="mt-4 pt-4 border-t border-gray-700">
          <button 
            @click="showLibrary = false" 
            class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700"
          >
            Закрыть
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import axios from 'axios'

const props = defineProps({
  modelValue: String,
  placeholder: String
})

const emit = defineEmits(['update:modelValue'])

const showLibrary = ref(false)
const library = ref([])
const uploading = ref(false)

// Загрузка библиотеки изображений
const loadLibrary = async () => {
  try {
    const response = await axios.get('/api/admin/images')
    library.value = response.data.data || []
  } catch (error) {
    console.error('Ошибка загрузки библиотеки:', error)
  }
}

// Выбор изображения
const selectImage = (image) => {
  emit('update:modelValue', image)
  showLibrary.value = false
}

// Удаление изображения
const removeImage = () => {
  emit('update:modelValue', '')
}

// Загрузка нового изображения
const uploadImage = async (event) => {
  const file = event.target.files[0]
  if (!file) return
  
  uploading.value = true
  const formData = new FormData()
  formData.append('image', file)
  
  try {
    const response = await axios.post('/api/admin/images/upload', formData, {
      headers: {
        'Content-Type': 'multipart/form-data'
      }
    })
    
    const newImagePath = response.data.path
    library.value.unshift(newImagePath)
    selectImage(newImagePath)
  } catch (error) {
    console.error('Ошибка загрузки:', error)
    alert('Ошибка при загрузке изображения')
  } finally {
    uploading.value = false
    event.target.value = '' // Сброс input
  }
}

// При открытии модалки загружаем библиотеку
onMounted(() => {
  if (showLibrary.value) {
    loadLibrary()
  }
})

// Следим за открытием модалки
import { watch } from 'vue'
watch(showLibrary, (newVal) => {
  if (newVal) {
    loadLibrary()
  }
})
</script>

<style scoped>
.image-picker {
  display: inline-block;
}
</style>