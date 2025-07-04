<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
    <div class="bg-gray-800 rounded-lg max-w-md w-full">
      <div class="p-6">
        <h3 class="text-xl font-semibold mb-6">
          {{ mode === 'create' ? 'Добавить' : 'Редактировать' }}
          {{ typeTitle }}
        </h3>

        <div class="space-y-4">
          <!-- Название -->
          <div>
            <label class="block text-sm font-medium mb-1">Название *</label>
            <input
              v-model="localForm.name"
              type="text"
              required
              class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white focus:border-blue-500 focus:outline-none"
              @keyup.enter="save"
            />
          </div>

          <!-- Слаг (только для категорий) -->
          <div v-if="type === 'category'">
            <label class="block text-sm font-medium mb-1">Слаг (URL)</label>
            <input
              v-model="localForm.slug"
              type="text"
              placeholder="Автоматически из названия"
              class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white focus:border-blue-500 focus:outline-none"
            />
          </div>

          <!-- Кнопки -->
          <div class="flex justify-end space-x-2 pt-4">
            <button
              @click="$emit('close')"
              type="button"
              class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded transition-colors"
            >
              Отмена
            </button>
            <button
              @click="save"
              :disabled="loading || !localForm.name"
              class="px-4 py-2 bg-green-600 hover:bg-green-700 rounded transition-colors disabled:opacity-50"
            >
              {{ loading ? 'Сохранение...' : 'Сохранить' }}
            </button>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, watch } from 'vue';

export default {
  name: 'SimpleModal',
  props: {
    mode: {
      type: String,
      required: true,
      validator: (value) => ['create', 'edit'].includes(value)
    },
    type: {
      type: String,
      required: true,
      validator: (value) => ['category', 'theme', 'attribute'].includes(value)
    },
    form: {
      type: Object,
      required: true
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  emits: ['save', 'close'],
  setup(props, { emit }) {
    const localForm = reactive({
      id: props.form.id,
      name: props.form.name,
      slug: props.form.slug
    });

    // Обновляем локальную форму при изменении пропсов
    watch(() => props.form, (newForm) => {
      Object.assign(localForm, newForm);
    }, { deep: true });

    const typeTitle = computed(() => {
      const titles = {
        category: 'категорию',
        theme: 'тему',
        attribute: 'атрибут'
      };
      return titles[props.type] || props.type;
    });

    const save = () => {
      if (!localForm.name.trim()) return;
      emit('save', { ...localForm });
    };

    return {
      localForm,
      typeTitle,
      save
    };
  }
};
</script>