<template>
  <div class="quiz-form bg-gray-900/50 rounded-xl p-8 backdrop-blur-sm border border-gray-800">
    <form @submit.prevent="submitQuiz" class="space-y-6">
      <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- День рождения -->
        <div>
          <label class="block text-white font-semibold mb-2">День рождения</label>
          <select 
            v-model="form.day"
            required
            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white focus:border-stuzha-accent focus:outline-none"
          >
            <option value="">Выберите день</option>
            <option v-for="day in 31" :key="day" :value="day">{{ day }}</option>
          </select>
        </div>

        <!-- Месяц рождения -->
        <div>
          <label class="block text-white font-semibold mb-2">Месяц рождения</label>
          <select 
            v-model="form.month"
            required
            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white focus:border-stuzha-accent focus:outline-none"
          >
            <option value="">Выберите месяц</option>
            <option v-for="(month, index) in months" :key="index" :value="index + 1">{{ month }}</option>
          </select>
        </div>

        <!-- Год рождения -->
        <div>
          <label class="block text-white font-semibold mb-2">Год рождения</label>
          <input 
            v-model="form.year"
            type="number"
            :min="1920"
            :max="2024"
            required
            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white focus:border-stuzha-accent focus:outline-none"
            placeholder="1990"
          />
        </div>

        <!-- Час рождения -->
        <div>
          <label class="block text-white font-semibold mb-2">Час рождения</label>
          <select 
            v-model="form.hour"
            required
            class="w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2 text-white focus:border-stuzha-accent focus:outline-none"
          >
            <option value="">Выберите час</option>
            <option v-for="hour in 24" :key="hour - 1" :value="hour - 1">{{ (hour - 1).toString().padStart(2, '0') }}:00</option>
          </select>
        </div>
      </div>

      <div v-if="error" class="text-red-500 text-center">{{ error }}</div>

      <div class="text-center">
        <button
          type="submit"
          :disabled="loading"
          class="bg-stuzha-accent hover:bg-red-700 text-white px-8 py-3 rounded-lg text-lg font-semibold transition-all duration-300 transform hover:scale-105 disabled:opacity-50"
        >
          <span v-if="loading">Вычисляем...</span>
          <span v-else>Подобрать украшения</span>
        </button>
      </div>
    </form>
  </div>
</template>

<script>
import { ref, reactive } from 'vue';
import { axios } from '../main.js';

export default {
  name: 'QuizForm',
  emits: ['result'],
  setup(props, { emit }) {
    const loading = ref(false);
    const error = ref('');
    
    const form = reactive({
      day: '',
      month: '',
      year: '',
      hour: ''
    });

    const months = [
      'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
      'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
    ];

    const submitQuiz = async () => {
      loading.value = true;
      error.value = '';
      
      try {
        const response = await axios.post('/quiz', form);
        emit('result', response.data);
      } catch (err) {
        error.value = err.response?.data?.message || 'Ошибка при подборе украшений';
      } finally {
        loading.value = false;
      }
    };

    return {
      form,
      loading,
      error,
      months,
      submitQuiz
    };
  }
};
</script>