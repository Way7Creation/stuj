<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
    <div class="bg-gray-800 rounded-lg max-w-md w-full">
      <div class="p-6">
        <h3 class="text-xl font-semibold mb-6 text-white">
          {{ mode === 'create' ? `Добавить ${entityType}` : `Редактировать ${entityType}` }}
        </h3>

        <form @submit.prevent="$emit('save')" class="space-y-4">
          <!-- Название -->
          <div>
            <label class="block text-sm font-medium mb-1 text-white">Название *</label>
            <input
              v-model="form.name"
              type="text"
              required
              class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white focus:border-stuzha-accent focus:outline-none"
              :placeholder="`Введите название ${entityType.toLowerCase()}`"
            />
          </div>

          <!-- Slug (только для категорий) -->
          <div v-if="showSlug">
            <label class="block text-sm font-medium mb-1 text-white">Slug *</label>
            <input
              v-model="form.slug"
              type="text"
              required
              class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white focus:border-stuzha-accent focus:outline-none"
              placeholder="url-slug"
            />
            <p class="text-gray-400 text-xs mt-1">Используется в URL. Только латиница, цифры и дефисы.</p>
          </div>

          <!-- Кнопки -->
          <div class="flex justify-end space-x-3 pt-4">
            <button
              type="button"
              @click="$emit('close')"
              class="px-4 py-2 text-gray-400 hover:text-white transition-colors"
            >
              Отмена
            </button>
            <button
              type="submit"
              :disabled="!isValid || loading"
              class="bg-stuzha-accent hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors disabled:opacity-50"
            >
              {{ loading ? 'Сохранение...' : 'Сохранить' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { computed, watch } from 'vue';

export default {
  name: 'SimpleModal',
  props: {
    mode: {
      type: String,
      required: true,
      validator: (value) => ['create', 'edit'].includes(value)
    },
    entityType: {
      type: String,
      required: true
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
  setup(props) {
    const showSlug = computed(() => {
      return props.entityType.toLowerCase() === 'категорию';
    });

    const isValid = computed(() => {
      if (!props.form.name) return false;
      if (showSlug.value && !props.form.slug) return false;
      return true;
    });

    // Автоматическая генерация slug из названия для категорий
    watch(() => props.form.name, (newName) => {
      if (showSlug.value && props.mode === 'create' && newName) {
        const transliterated = newName
          .toLowerCase()
          .replace(/а/g, 'a').replace(/б/g, 'b').replace(/в/g, 'v')
          .replace(/г/g, 'g').replace(/д/g, 'd').replace(/е/g, 'e')
          .replace(/ё/g, 'yo').replace(/ж/g, 'zh').replace(/з/g, 'z')
          .replace(/и/g, 'i').replace(/й/g, 'y').replace(/к/g, 'k')
          .replace(/л/g, 'l').replace(/м/g, 'm').replace(/н/g, 'n')
          .replace(/о/g, 'o').replace(/п/g, 'p').replace(/р/g, 'r')
          .replace(/с/g, 's').replace(/т/g, 't').replace(/у/g, 'u')
          .replace(/ф/g, 'f').replace(/х/g, 'kh').replace(/ц/g, 'ts')
          .replace(/ч/g, 'ch').replace(/ш/g, 'sh').replace(/щ/g, 'sch')
          .replace(/ъ/g, '').replace(/ы/g, 'y').replace(/ь/g, '')
          .replace(/э/g, 'e').replace(/ю/g, 'yu').replace(/я/g, 'ya');
        
        props.form.slug = transliterated
          .replace(/[^a-z0-9\s-]/g, '')
          .replace(/\s+/g, '-')
          .replace(/-+/g, '-')
          .replace(/^-+|-+$/g, '');
      }
    }, { immediate: false });

    return {
      showSlug,
      isValid
    };
  }
};
</script>