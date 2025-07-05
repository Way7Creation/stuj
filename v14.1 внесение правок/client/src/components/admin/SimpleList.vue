<template>
  <div class="space-y-4">
    <!-- Заголовок и кнопка добавления -->
    <div class="flex justify-between items-center">
      <h3 class="text-xl font-semibold text-white">{{ title }}</h3>
      <button
        @click="$emit('create')"
        class="bg-stuzha-accent hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors"
      >
        + Добавить {{ title.toLowerCase() }}
      </button>
    </div>

    <!-- Поиск -->
    <div class="mb-4">
      <input
        v-model="searchQuery"
        type="text"
        :placeholder="`Поиск ${title.toLowerCase()}...`"
        class="w-full max-w-md bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-400 focus:border-stuzha-accent focus:outline-none"
      />
    </div>

    <!-- Список -->
    <div v-if="loading" class="text-center py-8">
      <div class="text-gray-400">Загрузка...</div>
    </div>

    <div v-else-if="filteredItems.length === 0" class="text-center py-8">
      <div class="text-gray-400">{{ searchQuery ? 'Ничего не найдено' : `Нет ${title.toLowerCase()}` }}</div>
    </div>

    <div v-else class="space-y-2">
      <div
        v-for="item in filteredItems"
        :key="item.id"
        class="bg-gray-800 rounded-lg p-4 flex justify-between items-center hover:bg-gray-700 transition-colors"
      >
        <div>
          <h4 class="text-white font-medium">{{ item.name }}</h4>
          <p v-if="item.slug" class="text-gray-400 text-sm">Slug: {{ item.slug }}</p>
        </div>
        
        <div class="flex space-x-2">
          <button
            @click="$emit('edit', item)"
            class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm transition-colors"
          >
            Редактировать
          </button>
          <button
            @click="$emit('delete', item)"
            class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm transition-colors"
          >
            Удалить
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, computed } from 'vue';

export default {
  name: 'SimpleList',
  props: {
    title: {
      type: String,
      required: true
    },
    items: {
      type: Array,
      required: true
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  emits: ['create', 'edit', 'delete'],
  setup(props) {
    const searchQuery = ref('');

    const filteredItems = computed(() => {
      if (!searchQuery.value) {
        return props.items;
      }
      
      const query = searchQuery.value.toLowerCase();
      return props.items.filter(item => 
        item.name.toLowerCase().includes(query) ||
        (item.slug && item.slug.toLowerCase().includes(query))
      );
    });

    return {
      searchQuery,
      filteredItems
    };
  }
};
</script>