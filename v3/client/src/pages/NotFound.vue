<template>
  <div class="min-h-screen flex items-center justify-center px-4">
    <div class="text-center">
      <h1 class="text-9xl font-bold text-stuzha-accent mb-4">404</h1>
      <h2 class="text-3xl font-semibold mb-4">Страница не найдена</h2>
      <p class="text-gray-400 mb-8">
        Похоже, вы заблудились в нашей ювелирной мастерской
      </p>
      <router-link 
        to="/" 
        class="inline-block bg-stuzha-accent text-white px-6 py-3 rounded-lg hover:bg-red-600 transition-colors duration-300"
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