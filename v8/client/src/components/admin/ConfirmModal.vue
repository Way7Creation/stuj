<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
    <div class="bg-gray-800 rounded-lg max-w-md w-full p-6">
      <h3 class="text-xl font-semibold mb-4">Подтверждение удаления</h3>
      <p class="text-gray-300 mb-6">
        Вы уверены, что хотите удалить "{{ item.item.name }}"?
      </p>
      <div class="flex justify-end space-x-2">
        <button
          @click="$emit('cancel')"
          class="px-4 py-2 bg-gray-600 hover:bg-gray-700 rounded transition-colors"
        >
          Отмена
        </button>
        <button
          @click="$emit('confirm')"
          :disabled="loading"
          class="px-4 py-2 bg-red-600 hover:bg-red-700 rounded transition-colors disabled:opacity-50"
        >
          {{ loading ? 'Удаление...' : 'Удалить' }}
        </button>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ConfirmModal',
  props: {
    item: {
      type: Object,
      required: true
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  emits: ['confirm', 'cancel']
};
</script>