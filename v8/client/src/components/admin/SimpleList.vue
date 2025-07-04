<template>
  <div>
    <div v-if="loading" class="text-center py-20">
      <div class="animate-spin rounded-full h-12 w-12 border-b-2 border-white mx-auto"></div>
    </div>
    <div v-else class="bg-gray-800 rounded-lg overflow-hidden">
      <table class="min-w-full divide-y divide-gray-700">
        <thead class="bg-gray-900">
          <tr>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
              Название
            </th>
            <th class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
              Товаров
            </th>
            <th class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">
              Действия
            </th>
          </tr>
        </thead>
        <tbody class="divide-y divide-gray-700">
          <tr v-for="item in items" :key="item.id" class="hover:bg-gray-700 transition-colors">
            <td class="px-6 py-4 whitespace-nowrap">
              {{ item.name }}
              <span v-if="showStone && item.is_stone" class="text-xs text-gray-400 ml-2">(камень)</span>
            </td>
            <td class="px-6 py-4 whitespace-nowrap">
              {{ item.products_count || 0 }}
            </td>
            <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
              <button @click="$emit('edit', item)" class="text-indigo-400 hover:text-indigo-300 mr-4">
                Изменить
              </button>
              <button @click="$emit('delete', item)" class="text-red-400 hover:text-red-300">
                Удалить
              </button>
            </td>
          </tr>
        </tbody>
      </table>
    </div>
  </div>
</template>

<script>
export default {
  name: 'SimpleList',
  props: {
    items: { type: Array, required: true },
    loading: { type: Boolean, default: false },
    showStone: { type: Boolean, default: false }
  }
};
</script>