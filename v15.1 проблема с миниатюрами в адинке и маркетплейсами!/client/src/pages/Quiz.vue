<template>
  <div class="quiz min-h-screen bg-stuzha-bg py-20">
    <div class="max-w-4xl mx-auto px-4">
      <div class="text-center mb-12">
        <h1 class="text-4xl md:text-5xl font-bold text-white mb-6">Астрологический подбор</h1>
        <p class="text-xl text-gray-400 max-w-3xl mx-auto">
          Введите данные вашего рождения, и мы подберем идеальные камни для вас
        </p>
      </div>

      <QuizForm @result="onQuizResult" />

      <!-- Результаты -->
      <div v-if="result" class="mt-16">
        <div class="bg-gray-900/50 rounded-xl p-8 backdrop-blur-sm border border-gray-800 mb-8">
          <h2 class="text-2xl font-bold text-white mb-4">Ваши рекомендуемые камни:</h2>
          <div class="flex flex-wrap gap-3 mb-6">
            <span
              v-for="stone in result.recommended_stones"
              :key="stone"
              class="bg-stuzha-accent text-white px-4 py-2 rounded-full font-semibold"
            >
              {{ stone }}
            </span>
          </div>
        </div>

        <div v-if="result.products?.length > 0">
          <h3 class="text-2xl font-bold text-white mb-8 text-center">Подходящие украшения:</h3>
          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <div
              v-for="product in result.products"
              :key="product.id"
              class="bg-gray-900/50 rounded-xl overflow-hidden border border-gray-800 hover:border-stuzha-accent/50 transition-all duration-300"
            >
              <div class="aspect-w-1 aspect-h-1 bg-gradient-to-br from-gray-800 to-gray-900">
                <img
                  v-if="product.gallery_images?.length > 0"
                  :src="product.gallery_images[0]"
                  :alt="product.name"
                  class="w-full h-full object-cover"
                >
                <div v-else class="flex items-center justify-center h-full">
                  <p class="text-gray-500">Изображение отсутствует</p>
                </div>
              </div>
              
              <div class="p-4">
                <h4 class="text-lg font-semibold text-white mb-2">{{ product.name }}</h4>
                <div class="flex justify-between items-center">
                  <span class="text-xl font-bold text-stuzha-accent">{{ formatPrice(product.price) }} ₽</span>
                  <router-link
                    :to="`/product/${product.slug}`"
                    class="bg-stuzha-accent hover:bg-red-700 text-white px-4 py-2 rounded-lg text-sm font-semibold transition-colors"
                  >
                    Подробнее
                  </router-link>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref } from 'vue';
import QuizForm from '../components/QuizForm.vue';

export default {
  name: 'Quiz',
  components: {
    QuizForm
  },
  setup() {
    const result = ref(null);

    const formatPrice = (price) => {
      return new Intl.NumberFormat('ru-RU').format(price);
    };

    const onQuizResult = (quizResult) => {
      result.value = quizResult;
    };

    return {
      result,
      formatPrice,
      onQuizResult
    };
  }
};
</script>