<template>
  <div class="image-upload">
    <!-- Drag & Drop зона -->
    <div
      @click="openFileDialog"
      @dragover.prevent
      @drop.prevent="handleDrop"
      class="border-2 border-dashed border-gray-600 rounded-lg p-6 text-center cursor-pointer hover:border-gray-500 transition-colors"
      :class="{ 'border-blue-500 bg-blue-50/10': dragOver }"
    >
      <input
        ref="fileInput"
        type="file"
        accept="image/*"
        multiple
        @change="handleFileSelect"
        class="hidden"
      />
      
      <div v-if="uploading" class="py-4">
        <div class="animate-spin rounded-full h-8 w-8 border-b-2 border-blue-500 mx-auto mb-2"></div>
        <p class="text-gray-400">Загружаем изображения...</p>
      </div>
      
      <div v-else>
        <svg class="w-12 h-12 text-gray-400 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
        </svg>
        <p class="text-gray-300 font-medium">Нажмите или перетащите изображения</p>
        <p class="text-gray-400 text-sm mt-1">PNG, JPG, GIF, WebP до 5MB</p>
      </div>
    </div>

    <!-- Галерея загруженных изображений -->
    <div v-if="images.length > 0" class="mt-6">
      <h4 class="text-white font-medium mb-3">Загруженные изображения:</h4>
      <div class="grid grid-cols-3 md:grid-cols-6 gap-4">
        <div
          v-for="image in images"
          :key="image.url"
          class="relative group cursor-pointer"
          @click="selectImage(image.url)"
        >
          <img
            :src="image.url"
            :alt="image.filename"
            class="w-full h-20 object-cover rounded-lg border-2 border-transparent hover:border-blue-500 transition-colors"
            :class="{ 'border-blue-500 ring-2 ring-blue-500': selectedUrls.includes(image.url) }"
          />
          <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity rounded-lg flex items-center justify-center">
            <button
              @click.stop="copyUrl(image.url)"
              class="bg-white text-black px-2 py-1 rounded text-xs"
            >
              Копировать URL
            </button>
          </div>
          <div v-if="selectedUrls.includes(image.url)" class="absolute top-1 right-1">
            <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20">
              <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z"/>
            </svg>
          </div>
        </div>
      </div>
    </div>

    <!-- Выбранные изображения -->
    <div v-if="selectedUrls.length > 0" class="mt-4 p-4 bg-gray-800 rounded-lg">
      <h5 class="text-white font-medium mb-2">Выбрано изображений: {{ selectedUrls.length }}</h5>
      <div class="flex flex-wrap gap-2">
        <span
          v-for="url in selectedUrls"
          :key="url"
          class="bg-blue-600 text-white px-3 py-1 rounded-full text-sm flex items-center"
        >
          {{ getFilename(url) }}
          <button @click="removeSelection(url)" class="ml-2 text-blue-200 hover:text-white">×</button>
        </span>
      </div>
      <div class="mt-3 flex space-x-2">
        <button
          @click="$emit('selected', selectedUrls)"
          class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded text-sm"
        >
          Использовать выбранные
        </button>
        <button
          @click="selectedUrls = []"
          class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded text-sm"
        >
          Очистить выбор
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { axios } from '../../main.js';

export default {
  name: 'ImageUpload',
  emits: ['selected', 'uploaded'],
  setup(props, { emit }) {
    const fileInput = ref(null);
    const images = ref([]);
    const selectedUrls = ref([]);
    const uploading = ref(false);
    const dragOver = ref(false);

    const loadImages = async () => {
      try {
        const response = await axios.get('/admin/images');
        images.value = response.data.data || response.data || [];
      } catch (error) {
        console.error('Ошибка загрузки изображений:', error);
      }
    };

    const openFileDialog = () => {
      if (!uploading.value) {
        fileInput.value.click();
      }
    };

    const handleFileSelect = (event) => {
      const files = Array.from(event.target.files);
      uploadFiles(files);
    };

    const handleDrop = (event) => {
      dragOver.value = false;
      const files = Array.from(event.dataTransfer.files);
      uploadFiles(files);
    };

    const uploadFiles = async (files) => {
      if (files.length === 0) return;
      
      uploading.value = true;
      const uploadedUrls = [];
      
      for (const file of files) {
        // ИСПРАВЛЕНО: Проверка типа файла
        if (!file.type.startsWith('image/')) {
          alert(`Файл ${file.name} не является изображением`);
          continue;
        }
        
        // ИСПРАВЛЕНО: Проверка размера
        if (file.size > 5 * 1024 * 1024) {
          alert(`Файл ${file.name} превышает 5MB`);
          continue;
        }
        
        try {
          const formData = new FormData();
          formData.append('image', file);
    
          const response = await axios.post('/admin/images/upload', formData, {
            headers: {
              'Content-Type': 'multipart/form-data'
            },
            timeout: 30000 // 30 секунд таймаут
          });
    
          uploadedUrls.push(response.data.url);
        } catch (error) {
          console.error('Ошибка загрузки файла:', error);
          const errorMessage = error.response?.data?.message || 'Неизвестная ошибка';
          alert(`Ошибка загрузки ${file.name}: ${errorMessage}`);
        }
      }
    
      uploading.value = false;
      
      if (uploadedUrls.length > 0) {
        await loadImages();
        emit('uploaded', uploadedUrls);
      }
    };

    const selectImage = (url) => {
      const index = selectedUrls.value.indexOf(url);
      if (index > -1) {
        selectedUrls.value.splice(index, 1);
      } else {
        selectedUrls.value.push(url);
      }
    };

    const removeSelection = (url) => {
      const index = selectedUrls.value.indexOf(url);
      if (index > -1) {
        selectedUrls.value.splice(index, 1);
      }
    };

    const copyUrl = async (url) => {
      try {
        await navigator.clipboard.writeText(url);
        alert('URL скопирован в буфер обмена');
      } catch (error) {
        console.error('Ошибка копирования:', error);
      }
    };

    const getFilename = (url) => {
      return url.split('/').pop();
    };

    onMounted(() => {
      loadImages();
    });

    return {
      fileInput,
      images,
      selectedUrls,
      uploading,
      dragOver,
      openFileDialog,
      handleFileSelect,
      handleDrop,
      selectImage,
      removeSelection,
      copyUrl,
      getFilename
    };
  }
};
</script>