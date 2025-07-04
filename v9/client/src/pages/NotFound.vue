<template>
  <div class="not-found min-h-screen bg-stuzha-bg flex items-center justify-center">
    <div class="text-center">
      <div class="text-9xl font-bold text-stuzha-accent mb-4">404</div>
      <h1 class="text-4xl font-bold text-white mb-4">Страница не найдена</h1>
      <p class="text-xl text-gray-400 mb-8">
        Возможно, страница была перемещена или удалена
      </p>
      <router-link
        to="/"
        class="bg-stuzha-accent hover:bg-red-700 text-white px-8 py-3 rounded-lg text-lg font-semibold transition-all duration-300 transform hover:scale-105 inline-block"
      >
        Вернуться на главную
      </router-link>
    </div>
  </div>
</template>

<script>
export default {
  name: 'NotFound'
};
</script>