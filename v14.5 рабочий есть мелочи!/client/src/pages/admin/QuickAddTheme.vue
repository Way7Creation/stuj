<template>
  <div class="fixed inset-0 bg-black bg-opacity-75 z-50 flex items-center justify-center p-4">
    <div class="bg-gray-800 rounded-lg p-6 max-w-md w-full">
      <h3 class="text-xl text-white mb-4">Добавить новую тему</h3>
      
      <form @submit.prevent="save">
        <div class="mb-4">
          <label class="block text-gray-400 mb-2">Название темы *</label>
          <input 
            v-model="theme.name" 
            type="text" 
            class="w-full px-4 py-2 bg-gray-700 text-white rounded border border-gray-600 focus:border-blue-500 focus:outline-none"
            required
            autofocus
          >
        </div>
        
        <div class="flex gap-3">
          <button 
            type="submit"
            :disabled="loading"
            class="px-4 py-2 bg-green-600 text-white rounded hover:bg-green-700 disabled:opacity-50"
          >
            {{ loading ? 'Сохранение...' : 'Добавить' }}
          </button>
          <button 
            @click="$emit('close')"
            type="button"
            class="px-4 py-2 bg-gray-600 text-white rounded hover:bg-gray-700"
          >
            Отмена
          </button>
        </div>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref } from 'vue'
import axios from 'axios'

const emit = defineEmits(['close', 'added'])

const theme = ref({
  name: ''
})

const loading = ref(false)

const save = async () => {
  loading.value = true
  
  try {
    const response = await axios.post('/api/admin/themes', theme.value)
    emit('added', response.data.data)
  } catch (error) {
    console.error('Ошибка добавления темы:', error)
    alert('Ошибка: ' + (error.response?.data?.message || error.message))
  } finally {
    loading.value = false
  }
}
</script>