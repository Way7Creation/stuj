<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
    <div class="bg-gray-800 rounded-lg max-w-md w-full">
      <div class="p-6">
        <h3 class="text-xl font-semibold mb-6 text-white">
          Добавить {{ getEntityName(type) }}
        </h3>

        <form @submit.prevent="save" class="space-y-4">
          <!-- Название -->
          <div>
            <label class="block text-sm font-medium mb-2 text-white">
              Название {{ getEntityName(type).toLowerCase() }} *
            </label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
              :placeholder="`Введите название ${getEntityName(type).toLowerCase()}`"
              @keyup.enter="save"
            />
          </div>

          <!-- Slug для категорий -->
          <div v-if="type === 'category'">
            <label class="block text-sm font-medium mb-2 text-white">
              URL-адрес (slug) *
            </label>
            <input
              v-model="form.slug"
              type="text"
              required
              class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-3 text-white placeholder-gray-400 focus:border-blue-500 focus:outline-none"
              placeholder="url-slug"
            />
            <p class="text-gray-400 text-xs mt-1">
              Используется в URL. Только латиница, цифры и дефисы.
            </p>
          </div>

          <!-- Дополнительная информация -->
          <div v-if="type === 'theme'" class="text-gray-400 text-sm">
            <p>Темы используются для стилистической группировки товаров (например: Минимализм, Готика, Винтаж).</p>
          </div>
          
          <div v-if="type === 'category'" class="text-gray-400 text-sm">
            <p>Категории определяют тип украшения (например: Кольца, Браслеты, Серьги).</p>
          </div>
          
          <div v-if="type === 'attribute'" class="text-gray-400 text-sm">
            <p>Атрибуты описывают материалы и камни (например: агат, серебро, золото).</p>
          </div>

          <!-- Ошибки -->
          <div v-if="error" class="text-red-400 text-sm">
            {{ error }}
          </div>

          <!-- Кнопки -->
          <div class="flex justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 text-gray-400 hover:text-white transition-colors"
              :disabled="loading"
            >
              Отмена
            </button>
            <button
              type="submit"
              :disabled="!isValid || loading"
              class="bg-blue-600 hover:bg-blue-700 disabled:bg-gray-600 text-white px-6 py-2 rounded-lg font-medium transition-colors"
            >
              {{ loading ? 'Создание...' : 'Создать' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, watch } from 'vue';
import { axios } from '../../main.js';

export default {
  name: 'QuickAddModal',
  props: {
    type: {
      type: String,
      required: true,
      validator: (value) => ['theme', 'category', 'attribute'].includes(value)
    }
  },
  emits: ['close', 'added'],
  setup(props, { emit }) {
    const loading = ref(false);
    const error = ref('');
    
    const form = reactive({
      name: '',
      slug: ''
    });
    
    // Валидация
    const isValid = computed(() => {
      if (!form.name) return false;
      if (props.type === 'category' && !form.slug) return false;
      return true;
    });
    
    // Автогенерация slug для категорий
    watch(() => form.name, (newName) => {
      if (props.type === 'category' && newName) {
        form.slug = newName
          .toLowerCase()
          .replace(/[^a-z0-9\s-]/g, '')
          .replace(/\s+/g, '-')
          .replace(/-+/g, '-')
          .trim('-');
      }
    });
    
    // Сохранение
    const save = async () => {
      if (!isValid.value) return;
      
      loading.value = true;
      error.value = '';
      
      try {
        const endpoint = `/admin/${props.type}s`;
        const data = props.type === 'category' 
          ? { name: form.name, slug: form.slug }
          : { name: form.name };
        
        const response = await axios.post(endpoint, data);
        
        // Получаем созданный объект из ответа
        const createdItem = response.data[props.type] || response.data.data || {
          id: Date.now(), // fallback ID если не вернулся
          name: form.name,
          slug: form.slug
        };
        
        emit('added', createdItem, props.type);
        
      } catch (err) {
        console.error('Ошибка создания:', err);
        error.value = err.response?.data?.message || 
                     err.response?.data?.errors?.name?.[0] ||
                     'Ошибка создания';
      } finally {
        loading.value = false;
      }
    };
    
    // Утилиты
    const getEntityName = (type) => {
      const names = {
        theme: 'тему',
        category: 'категорию',
        attribute: 'атрибут'
      };
      return names[type] || type;
    };
    
    return {
      loading,
      error,
      form,
      isValid,
      save,
      getEntityName
    };
  }
};
</script>