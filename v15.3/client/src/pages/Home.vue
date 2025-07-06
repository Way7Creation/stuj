<template>
  <div class="home">
    <!-- Герой секция -->
    <section class="relative min-h-screen flex items-center justify-center bg-gradient-to-b from-black via-gray-900 to-stuzha-bg">
      <div class="absolute inset-0 bg-black/50"></div>
      <div class="relative z-10 text-center max-w-4xl mx-auto px-4">
        <h1 class="text-5xl md:text-7xl font-bold text-white mb-6 animate-fade-in">
          Стужа
        </h1>
        <p class="text-xl md:text-2xl text-gray-300 mb-8 animate-slide-up">
          Уникальные украшения с натуральными камнями
        </p>
        <p class="text-lg text-gray-400 mb-12 max-w-2xl mx-auto animate-slide-up">
          Подберите идеальное украшение с помощью астрологических расчетов. 
          Каждый камень выбран для гармонии с вашей энергетикой.
        </p>
        
        <div class="flex flex-col sm:flex-row gap-4 justify-center animate-scale-in">
          <router-link
            to="/catalog"
            class="bg-stuzha-accent hover:bg-red-700 text-white px-8 py-4 rounded-lg text-lg font-semibold transition-all duration-300 transform hover:scale-105"
          >
            Смотреть каталог
          </router-link>
          <router-link
            to="/quiz"
            class="border-2 border-stuzha-accent text-stuzha-accent hover:bg-stuzha-accent hover:text-white px-8 py-4 rounded-lg text-lg font-semibold transition-all duration-300 transform hover:scale-105"
          >
            Подобрать украшение
          </router-link>
        </div>
      </div>
      
      <!-- Декоративные элементы -->
      <div class="absolute bottom-10 left-1/2 transform -translate-x-1/2 animate-bounce">
        <svg class="w-6 h-6 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
          <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 14l-7 7m0 0l-7-7m7 7V3"/>
        </svg>
      </div>
    </section>

    <!-- О нас секция -->
    <section class="py-20 bg-stuzha-bg">
      <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-16">
          <h2 class="text-4xl font-bold text-white mb-6">Почему выбирают Стужу?</h2>
          <p class="text-xl text-gray-400 max-w-3xl mx-auto">
            Мы создаем не просто украшения - мы создаем персональные талисманы, 
            которые резонируют с вашей внутренней энергией.
          </p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <!-- Астрология -->
          <div class="text-center bg-gray-900/50 rounded-xl p-8 backdrop-blur-sm border border-gray-800">
            <div class="bg-stuzha-accent/20 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
              <svg class="w-8 h-8 text-stuzha-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-white mb-4">Астрологический подбор</h3>
            <p class="text-gray-400">
              Каждое украшение подбирается на основе вашей даты рождения и астрологических расчетов.
            </p>
          </div>

          <!-- Натуральные камни -->
          <div class="text-center bg-gray-900/50 rounded-xl p-8 backdrop-blur-sm border border-gray-800">
            <div class="bg-stuzha-accent/20 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
              <svg class="w-8 h-8 text-stuzha-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z"/>
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-white mb-4">Натуральные камни</h3>
            <p class="text-gray-400">
              Только подлинные минералы с сертификатами качества. Никаких синтетических материалов.
            </p>
          </div>

          <!-- Уникальный дизайн -->
          <div class="text-center bg-gray-900/50 rounded-xl p-8 backdrop-blur-sm border border-gray-800">
            <div class="bg-stuzha-accent/20 w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-6">
              <svg class="w-8 h-8 text-stuzha-accent" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zM21 5a2 2 0 00-2-2h-4a2 2 0 00-2 2v12a4 4 0 004 4h4a2 2 0 002-2V5z"/>
              </svg>
            </div>
            <h3 class="text-xl font-semibold text-white mb-4">Эффект "Матрёшки"</h3>
            <p class="text-gray-400">
              Интерактивная подача украшений с анимированными слоями для детального изучения.
            </p>
          </div>
        </div>
      </div>
    </section>

    <!-- Популярные товары -->
    <section class="py-20 bg-gray-900/30">
      <div class="max-w-6xl mx-auto px-4">
        <div class="text-center mb-16">
          <h2 class="text-4xl font-bold text-white mb-6">Популярные украшения</h2>
          <p class="text-xl text-gray-400">
            Самые востребованные модели от наших мастеров
          </p>
        </div>

        <!-- Здесь будут популярные товары -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
          <div class="bg-gray-900/50 rounded-xl overflow-hidden border border-gray-800 group hover:border-stuzha-accent/50 transition-all duration-300">
            <div class="aspect-w-1 aspect-h-1 bg-gradient-to-br from-gray-800 to-gray-900 flex items-center justify-center">
              <p class="text-gray-500">Изображение товара</p>
            </div>
            <div class="p-6">
              <h3 class="text-xl font-semibold text-white mb-2">Кольцо с агатом</h3>
              <p class="text-gray-400 mb-4">Элегантное кольцо с натуральным агатом</p>
              <div class="flex justify-between items-center">
                <span class="text-2xl font-bold text-stuzha-accent">2,500 ₽</span>
                <button class="bg-stuzha-accent hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors">
                  Подробнее
                </button>
              </div>
            </div>
          </div>

          <div class="bg-gray-900/50 rounded-xl overflow-hidden border border-gray-800 group hover:border-stuzha-accent/50 transition-all duration-300">
            <div class="aspect-w-1 aspect-h-1 bg-gradient-to-br from-gray-800 to-gray-900 flex items-center justify-center">
              <p class="text-gray-500">Изображение товара</p>
            </div>
            <div class="p-6">
              <h3 class="text-xl font-semibold text-white mb-2">Серьги с турмалином</h3>
              <p class="text-gray-400 mb-4">Изысканные серьги с зеленым турмалином</p>
              <div class="flex justify-between items-center">
                <span class="text-2xl font-bold text-stuzha-accent">3,200 ₽</span>
                <button class="bg-stuzha-accent hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors">
                  Подробнее
                </button>
              </div>
            </div>
          </div>

          <div class="bg-gray-900/50 rounded-xl overflow-hidden border border-gray-800 group hover:border-stuzha-accent/50 transition-all duration-300">
            <div class="aspect-w-1 aspect-h-1 bg-gradient-to-br from-gray-800 to-gray-900 flex items-center justify-center">
              <p class="text-gray-500">Изображение товара</p>
            </div>
            <div class="p-6">
              <h3 class="text-xl font-semibold text-white mb-2">Подвеска с аметистом</h3>
              <p class="text-gray-400 mb-4">Мистическая подвеска с фиолетовым аметистом</p>
              <div class="flex justify-between items-center">
                <span class="text-2xl font-bold text-stuzha-accent">1,800 ₽</span>
                <button class="bg-stuzha-accent hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors">
                  Подробнее
                </button>
              </div>
            </div>
          </div>
        </div>

        <div class="text-center mt-12">
          <router-link
            to="/catalog"
            class="inline-block bg-stuzha-accent hover:bg-red-700 text-white px-8 py-3 rounded-lg text-lg font-semibold transition-all duration-300 transform hover:scale-105"
          >
            Весь каталог
          </router-link>
        </div>
      </div>
    </section>

    <!-- Квиз секция -->
    <section class="py-20 bg-stuzha-bg">
      <div class="max-w-4xl mx-auto px-4 text-center">
        <h2 class="text-4xl font-bold text-white mb-6">Не знаете, что выбрать?</h2>
        <p class="text-xl text-gray-400 mb-8">
          Пройдите наш астрологический квиз и получите персональные рекомендации
        </p>
        <router-link
          to="/quiz"
          class="inline-block bg-gradient-to-r from-stuzha-accent to-red-600 hover:from-red-600 hover:to-stuzha-accent text-white px-10 py-4 rounded-lg text-xl font-semibold transition-all duration-300 transform hover:scale-105"
        >
          Пройти квиз
        </router-link>
      </div>
    </section>
  </div>
</template>

<script>
import { onMounted } from 'vue';

export default {
  name: 'Home',
  setup() {
    onMounted(() => {
      // Можно добавить загрузку популярных товаров
      console.log('Home page mounted');
    });

    return {};
  }
};
</script>

<style scoped>
/* Дополнительные стили для главной страницы */
.aspect-w-1 {
  position: relative;
  width: 100%;
  padding-bottom: 100%;
}

.aspect-h-1 {
  position: absolute;
  height: 100%;
  width: 100%;
  top: 0;
  right: 0;
  bottom: 0;
  left: 0;
}
</style>