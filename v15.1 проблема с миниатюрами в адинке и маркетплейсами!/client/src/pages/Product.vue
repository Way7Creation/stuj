<template>
  <div class="product min-h-screen bg-stuzha-bg py-20">
    <div class="max-w-6xl mx-auto px-4">
      <div v-if="loading" class="flex justify-center items-center py-20">
        <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-stuzha-accent"></div>
      </div>

      <div v-else-if="product" class="grid grid-cols-1 lg:grid-cols-2 gap-12">
        <!-- Изображения -->
        <div>
          <MatryoshkaCard v-if="product.use_matryoshka" :product="product" />
          <SimpleCard v-else :product="product" />
        </div>

        <!-- Информация -->
        <div>
          <h1 class="text-4xl font-bold text-white mb-4">{{ product.name }}</h1>
          <div class="text-3xl font-bold text-stuzha-accent mb-6">{{ formatPrice(product.price) }} ₽</div>
          
          <div class="prose prose-invert mb-8">
            <p class="text-gray-300 text-lg leading-relaxed">{{ product.description }}</p>
          </div>

          <div class="mb-8">
            <h3 class="text-xl font-semibold text-white mb-4">Характеристики</h3>
            <div class="space-y-2">
              <div v-if="product.theme" class="flex items-center">
                <span class="text-gray-400 w-20">Тема:</span>
                <span class="text-white">{{ product.theme }}</span>
              </div>
              <div v-if="product.attributes?.length > 0" class="flex items-start">
                <span class="text-gray-400 w-20">Камни:</span>
                <div class="flex flex-wrap gap-2">
                  <span
                    v-for="attr in product.attributes"
                    :key="attr"
                    class="bg-stuzha-accent/20 text-stuzha-accent px-3 py-1 rounded-full text-sm"
                  >
                    {{ attr }}
                  </span>
                </div>
              </div>
            </div>
          </div>

          <div class="mb-8">
            <a
              :href="`https://t.me/stuzha_bot?start=order_${product.id}`"
              target="_blank"
              class="bg-stuzha-accent hover:bg-red-700 text-white px-8 py-4 rounded-lg text-lg font-semibold transition-all duration-300 transform hover:scale-105 inline-block"
            >
              Заказать в Telegram
            </a>
          </div>
        </div>
      </div>

      <div v-else class="text-center py-20">
        <h2 class="text-2xl font-bold text-white mb-4">Товар не найден</h2>
        <router-link to="/catalog" class="text-stuzha-accent hover:underline">
          Вернуться в каталог
        </router-link>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue';
import { useRoute } from 'vue-router';
import { axios } from '../main.js';
import MatryoshkaCard from '../components/MatryoshkaCard.vue';
import SimpleCard from '../components/SimpleCard.vue';

export default {
  name: 'Product',
  components: {
    MatryoshkaCard,
    SimpleCard
  },
  setup() {
    const route = useRoute();
    const loading = ref(false);
    const product = ref(null);

    const formatPrice = (price) => {
      return new Intl.NumberFormat('ru-RU').format(price);
    };

    const loadProduct = async () => {
      loading.value = true;
      try {
        const response = await axios.get(`/product/${route.params.slug}`);
        product.value = response.data;
      } catch (error) {
        console.error('Ошибка загрузки товара:', error);
        product.value = null;
      } finally {
        loading.value = false;
      }
    };

    onMounted(() => {
      loadProduct();
    });

    return {
      loading,
      product,
      formatPrice
    };
  }
};
</script>