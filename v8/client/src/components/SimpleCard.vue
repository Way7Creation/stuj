<template>
  <div class="simple-card bg-gray-900 rounded-lg overflow-hidden">
    <div class="relative">
      <!-- Главное изображение -->
      <div class="aspect-w-1 aspect-h-1 bg-gray-800">
        <img 
          :src="currentImage"
          :alt="`${product.name} - изображение ${currentImageIndex + 1}`"
          class="w-full h-full object-cover"
          @error="handleImageError"
        />
      </div>
      
      <!-- Навигация -->
      <div v-if="product.gallery_images?.length > 1" class="absolute inset-0 flex items-center justify-between p-4">
        <button 
          @click="previousImage"
          class="bg-black/50 text-white p-2 rounded-full hover:bg-black/70 transition-colors"
          :disabled="currentImageIndex === 0"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        
        <button 
          @click="nextImage"
          class="bg-black/50 text-white p-2 rounded-full hover:bg-black/70 transition-colors"
          :disabled="currentImageIndex === product.gallery_images.length - 1"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
        </button>
      </div>

      <!-- Индикаторы -->
      <div v-if="product.gallery_images?.length > 1" class="absolute bottom-4 left-1/2 transform -translate-x-1/2">
        <div class="flex space-x-2">
          <button
            v-for="(image, index) in product.gallery_images"
            :key="index"
            @click="currentImageIndex = index"
            class="w-2 h-2 rounded-full transition-colors"
            :class="currentImageIndex === index ? 'bg-stuzha-accent' : 'bg-white/50'"
          />
        </div>
      </div>
    </div>

    <!-- Миниатюры -->
    <div v-if="product.gallery_images?.length > 1" class="p-4">
      <div class="grid grid-cols-4 gap-2">
        <button
          v-for="(image, index) in product.gallery_images.slice(0, 4)"
          :key="index"
          @click="currentImageIndex = index"
          class="aspect-w-1 aspect-h-1 bg-gray-800 rounded overflow-hidden border-2 transition-colors"
          :class="currentImageIndex === index ? 'border-stuzha-accent' : 'border-transparent'"
        >
          <img 
            :src="image"
            :alt="`${product.name} - миниатюра ${index + 1}`"
            class="w-full h-full object-cover"
            @error="handleImageError"
          />
        </button>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue';

export default {
  name: 'SimpleCard',
  props: {
    product: {
      type: Object,
      required: true
    }
  },
  setup(props) {
    const currentImageIndex = ref(0);

    const currentImage = computed(() => {
      return props.product.gallery_images?.[currentImageIndex.value] || '/images/placeholder.jpg';
    });

    const previousImage = () => {
      if (currentImageIndex.value > 0) {
        currentImageIndex.value--;
      }
    };

    const nextImage = () => {
      if (currentImageIndex.value < props.product.gallery_images.length - 1) {
        currentImageIndex.value++;
      }
    };

    const handleImageError = (event) => {
      event.target.src = '/images/placeholder.jpg';
    };

    return {
      currentImageIndex,
      currentImage,
      previousImage,
      nextImage,
      handleImageError
    };
  }
};
</script>