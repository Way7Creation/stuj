<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
    <div class="bg-gray-800 rounded-lg max-w-md w-full">
      <div class="p-6">
        <div class="flex items-center mb-4">
          <!-- Иконка предупреждения -->
          <div class="flex-shrink-0 w-10 h-10 bg-red-100 rounded-full flex items-center justify-center mr-4">
            <svg class="w-6 h-6 text-red-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.732 16c-.77.833.192 2.5 1.732 2.5z" />
            </svg>
          </div>
          
          <div>
            <h3 class="text-lg font-semibold text-white">Подтвердите удаление</h3>
          </div>
        </div>

        <div class="mb-6">
          <p class="text-gray-300">
            Вы действительно хотите удалить 
            <span class="font-semibold text-white">{{ itemName }}</span>?
          </p>
          <p class="text-gray-400 text-sm mt-2">
            Это действие нельзя отменить.
          </p>
        </div>

        <!-- Дополнительное предупреждение для товаров -->
        <div v-if="entityType === 'product'" class="mb-6 p-3 bg-yellow-900/30 border border-yellow-600 rounded-lg">
          <p class="text-yellow-200 text-sm">
            <strong>Внимание:</strong> При удалении товара также будут удалены все связанные изображения и связи с категориями.
          </p>
        </div>

        <!-- Кнопки -->
        <div class="flex justify-end space-x-3">
          <button
            type="button"
            @click="$emit('cancel')"
            class="px-4 py-2 text-gray-400 hover:text-white transition-colors"
            :disabled="loading"
          >
            Отмена
          </button>
          <button
            type="button"
            @click="$emit('confirm')"
            :disabled="loading"
            class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg font-medium transition-colors disabled:opacity-50 flex items-center"
          >
            <svg v-if="loading" class="animate-spin -ml-1 mr-2 h-4 w-4 text-white" fill="none" viewBox="0 0 24 24">
              <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
              <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
            </svg>
            {{ loading ? 'Удаление...' : 'Удалить' }}
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script>
export default {
  name: 'ConfirmModal',
  props: {
    itemName: {
      type: String,
      required: true
    },
    entityType: {
      type: String,
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