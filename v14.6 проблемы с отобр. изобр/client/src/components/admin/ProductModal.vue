<template>
  <div class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
    <div class="bg-gray-800 rounded-lg max-w-2xl w-full max-h-[90vh] overflow-y-auto">
      <div class="p-6">
        <h3 class="text-xl font-semibold mb-6">
          {{ mode === 'create' ? 'Добавить товар' : 'Редактировать товар' }}
        </h3>

        <div class="space-y-4">
          <!-- Название -->
          <div>
            <label class="block text-sm font-medium mb-1">Название *</label>
            <input
              v-model="productForm.name"
              type="text"
              required
              class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white"
            />
          </div>

          <!-- Описание -->
          <div>
            <label class="block text-sm font-medium mb-1">Описание *</label>
            <textarea
              v-model="productForm.description"
              required
              rows="3"
              class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white"
            />
          </div>

          <!-- Цена -->
          <div>
            <label class="block text-sm font-medium mb-1">Цена *</label>
            <input
              v-model="productForm.price"
              type="number"
              required
              min="0"
              step="0.01"
              class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white"
            />
          </div>

          <!-- Тема -->
          <div>
            <label class="block text-sm font-medium mb-1">Тема *</label>
            <select
              v-model="productForm.theme_id"
              required
              class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white"
            >
              <option value="">Выберите тему</option>
              <option v-for="theme in themes" :key="theme.id" :value="theme.id">
                {{ theme.name }}
              </option>
            </select>
          </div>

          <!-- Эффект матрёшки -->
          <div>
            <label class="flex items-center">
              <input
                v-model="productForm.use_matryoshka"
                type="checkbox"
                class="mr-2"
              />
              <span class="text-sm font-medium">Использовать эффект матрёшки</span>
            </label>
          </div>

          <!-- Слои матрёшки -->
          <div v-if="productForm.use_matryoshka" class="space-y-2">
            <div>
              <label class="block text-sm font-medium mb-1">Внешний слой (URL)</label>
              <input
                v-model="productForm.image_layers.outer"
                type="text"
                class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white"
              />
            </div>
            <div>
              <label class="block text-sm font-medium mb-1">Внутренний слой (URL)</label>
              <input
                v-model="productForm.image_layers.inner"
                type="text"
                class="w-full bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white"
              />
            </div>
          </div>

          <!-- Галерея изображений -->
          <div>
            <label class="block text-sm font-medium mb-1">Галерея изображений</label>
            <div v-for="(image, index) in productForm.gallery_images" :key="index" class="flex mb-2">
              <input
                v-model="productForm.gallery_images[index]"
                type="text"
                placeholder="URL изображения"
                class="flex-1 bg-gray-700 border border-gray-600 rounded px-3 py-2 text-white"
              />
              <button
                @click="$emit('remove-gallery-image', index)"
                type="button"
                class="ml-2 px-3 py-2 bg-red-600 hover:bg-red-700 rounded transition-colors"
              >
                ✕
              </button>
            </div>
            <button
              @click="$emit('add-gallery-image')"
              type="button"
              class="text-sm text-blue-400 hover:text-blue-300"
            >
              + Добавить изображение
            </button>
          </div>

          <!-- Категории -->
          <div>
            <label class="block text-sm font-medium mb-1">Категории</label>
            <div class="space-y-1 max-h-32 overflow-y-auto bg-gray-700 rounded p-2">
              <label v-for="category in categories" :key="category.id" class="flex items-center">
                <input
                  v-model="productForm.category_ids"
                  :value="category.id"
                  type="checkbox"
                  class="mr-2"
                />
                <span class="text-sm">{{ category.name }}</span>
              </label>
            </div>
          </div>

          <!-- Атрибуты -->
          <div>
            <label class="block text-sm font-medium mb-1">Атрибуты</label>
            <div class="space-y-1 max-h-32 overflow-y-auto bg-gray-700 rounded p-2">
              <label v-for="attribute in attributes" :key="attribute.id" class="flex items-center">
                <input
                  v-model="productForm.attribute_ids"
                  :value="attribute.id"
                  type="checkbox"
                  class="mr-2"
                />
                <span class="text-sm">
                  {{ attribute.name }}
                  <span v-if="attribute.is_stone" class="text-xs text-gray-400 ml-1">(камень)</span>
                </span>
              </label>
            </div>
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
              @click="$emit('save')"
              :disabled="loading || !isValid"
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
import { computed } from 'vue';

export default {
  name: 'ProductModal',
  props: {
    mode: {
      type: String,
      required: true,
      validator: (value) => ['create', 'edit'].includes(value)
    },
    productForm: {
      type: Object,
      required: true
    },
    themes: {
      type: Array,
      required: true
    },
    categories: {
      type: Array,
      required: true
    },
    attributes: {
      type: Array,
      required: true
    },
    loading: {
      type: Boolean,
      default: false
    }
  },
  emits: ['save', 'close', 'add-gallery-image', 'remove-gallery-image'],
  setup(props) {
    const isValid = computed(() => {
      return props.productForm.name && 
             props.productForm.description && 
             props.productForm.price && 
             props.productForm.theme_id;
    });

    return {
      isValid
    };
  }
};
</script>