<template>
  <div class="admin-login min-h-screen bg-stuzha-bg flex items-center justify-center py-12 px-4">
    <div class="max-w-md w-full space-y-8">
      <div class="text-center">
        <h2 class="text-3xl font-bold text-white">Вход в админ-панель</h2>
        <p class="mt-2 text-sm text-gray-400">Управление контентом Стужа</p>
      </div>
      
      <form @submit.prevent="login" class="mt-8 space-y-6">
        <div class="space-y-4">
          <div>
            <label class="sr-only">Email</label>
            <input
              v-model="form.email"
              type="email"
              required
              class="relative block w-full px-3 py-2 border border-gray-700 placeholder-gray-400 text-white bg-gray-800 rounded-lg focus:outline-none focus:ring-stuzha-accent focus:border-stuzha-accent focus:z-10"
              placeholder="Email"
            >
          </div>
          <div>
            <label class="sr-only">Пароль</label>
            <input
              v-model="form.password"
              type="password"
              required
              class="relative block w-full px-3 py-2 border border-gray-700 placeholder-gray-400 text-white bg-gray-800 rounded-lg focus:outline-none focus:ring-stuzha-accent focus:border-stuzha-accent focus:z-10"
              placeholder="Пароль"
            >
          </div>
        </div>

        <div v-if="error" class="text-red-500 text-sm text-center">{{ error }}</div>

        <div>
          <button
            type="submit"
            :disabled="loading"
            class="group relative w-full flex justify-center py-2 px-4 border border-transparent text-sm font-medium rounded-md text-white bg-stuzha-accent hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-stuzha-accent disabled:opacity-50"
          >
            <span v-if="loading">Загрузка...</span>
            <span v-else>Войти</span>
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script>
import { ref, reactive } from 'vue';
import { useRouter } from 'vue-router';
import { axios } from '../main.js';

export default {
  name: 'AdminLogin',
  setup() {
    const router = useRouter();
    const loading = ref(false);
    const error = ref('');
    
    const form = reactive({
      email: '',
      password: ''
    });

    const login = async () => {
      loading.value = true;
      error.value = '';
      
      try {
        const response = await axios.post('/admin/login', form);
        localStorage.setItem('auth_token', response.data.token);
        axios.defaults.headers.common['Authorization'] = `Bearer ${response.data.token}`;
        router.push('/admin');
      } catch (err) {
        error.value = err.response?.data?.message || 'Ошибка входа';
      } finally {
        loading.value = false;
      }
    };

    return {
      form,
      loading,
      error,
      login
    };
  }
};
</script>