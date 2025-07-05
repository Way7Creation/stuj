<template>
  <div 
    class="matryoshka-container relative w-full h-96 overflow-hidden rounded-lg bg-gray-900 cursor-pointer"
    @scroll.passive="onScroll"
    @wheel.prevent="onWheel"
  >
    <!-- Внешний слой -->
    <img 
      v-if="product.image_layers?.outer"
      :src="product.image_layers.outer"
      :alt="`${product.name} - внешний слой`"
      class="outer layer absolute inset-0 w-full h-full object-cover transition-transform duration-500"
      ref="outerLayer"
      @error="handleImageError"
    />
    
    <!-- Внутренний слой -->
    <img 
      v-if="product.image_layers?.inner"
      :src="product.image_layers.inner"
      :alt="`${product.name} - внутренний слой`"
      class="inner layer absolute inset-0 w-full h-full object-cover transition-transform duration-500"
      ref="innerLayer"
      @error="handleImageError"
    />

    <!-- Контент внутри (галерея) -->
    <div 
      class="inside-content absolute bottom-0 left-0 right-0 bg-black/80 p-4 transform translate-y-full transition-transform duration-500"
      ref="content"
      :class="{ 'translate-y-0': isOpen }"
    >
      <div v-if="product.gallery_images?.length > 0" class="flex items-center justify-between">
        <button 
          @click="previousImage"
          class="text-white hover:text-stuzha-accent transition-colors p-2"
          :disabled="currentImageIndex === 0"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
          </svg>
        </button>
        
        <div class="flex-1 text-center">
          <img 
            :src="currentImage"
            :alt="`${product.name} - изображение ${currentImageIndex + 1}`"
            class="gallery-img max-h-32 max-w-full object-contain mx-auto"
            @error="handleImageError"
          />
          <p class="text-white text-sm mt-2">
            {{ currentImageIndex + 1 }} / {{ product.gallery_images.length }}
          </p>
        </div>
        
        <button 
          @click="nextImage"
          class="text-white hover:text-stuzha-accent transition-colors p-2"
          :disabled="currentImageIndex === product.gallery_images.length - 1"
        >
          <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
          </svg>
        </button>
      </div>
      
      <div v-else class="text-center text-gray-400">
        <p>Дополнительные изображения отсутствуют</p>
      </div>
    </div>

    <!-- Индикатор прокрутки -->
    <div class="absolute top-4 right-4 text-white text-sm bg-black/50 px-2 py-1 rounded">
      {{ Math.round(scrollProgress) }}%
    </div>
  </div>
</template>

<script>
import { ref, computed, onMounted, onUnmounted } from 'vue';

export default {
  name: 'MatryoshkaCard',
  props: {
    product: {
      type: Object,
      required: true
    }
  },
  setup(props) {
    const outerLayer = ref(null);
    const innerLayer = ref(null);
    const content = ref(null);
    const scrollProgress = ref(0);
    const currentImageIndex = ref(0);
    const isOpen = ref(false);

    const currentImage = computed(() => {
      return props.product.gallery_images?.[currentImageIndex.value] || '';
    });

    const onWheel = (event) => {
      event.preventDefault();
      const delta = event.deltaY;
      const maxScroll = 100;
      
      scrollProgress.value += delta * 0.1;
      scrollProgress.value = Math.max(0, Math.min(maxScroll, scrollProgress.value));
      
      updateLayers();
    };

    const onScroll = (event) => {
      const scrollTop = event.target.scrollTop;
      const scrollHeight = event.target.scrollHeight - event.target.clientHeight;
      
      if (scrollHeight > 0) {
        scrollProgress.value = (scrollTop / scrollHeight) * 100;
        updateLayers();
      }
    };

    const updateLayers = () => {
      const progress = scrollProgress.value / 100;
      
      if (outerLayer.value) {
        // Внешний слой поворачивается на -90 градусов
        outerLayer.value.style.transform = `rotate(${-90 * progress}deg) scale(${1 - progress * 0.3})`;
      }
      
      if (innerLayer.value) {
        // Внутренний слой поворачивается на -45 градусов
        innerLayer.value.style.transform = `rotate(${-45 * progress}deg) scale(${1 - progress * 0.1})`;
      }
      
      // Показываем контент когда прокрутка больше 50%
      isOpen.value = progress > 0.5;
    };

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

    onMounted(() => {
      updateLayers();
    });

    return {
      outerLayer,
      innerLayer,
      content,
      scrollProgress,
      currentImageIndex,
      currentImage,
      isOpen,
      onWheel,
      onScroll,
      previousImage,
      nextImage,
      handleImageError
    };
  }
};
</script>