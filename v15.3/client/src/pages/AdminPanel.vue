<template>
  <div class="admin-panel min-h-screen bg-gray-900 p-6">
    <div class="max-w-7xl mx-auto">
      <!-- Заголовок с статистикой -->
      <div class="flex justify-between items-center mb-8">
        <div>
          <h1 class="text-3xl font-bold text-white">Админ-панель "Стужа"</h1>
          <p class="text-gray-400 mt-1">Управление интернет-магазином украшений</p>
        </div>
        
        <!-- Статистика -->
        <div class="hidden lg:flex space-x-4">
          <div class="bg-gray-800 rounded-lg p-4 text-center" v-if="stats">
            <div class="text-2xl font-bold text-blue-400">{{ stats.products?.total || 0 }}</div>
            <div class="text-gray-400 text-sm">Товаров</div>
          </div>
          <div class="bg-gray-800 rounded-lg p-4 text-center" v-if="stats">
            <div class="text-2xl font-bold text-green-400">{{ stats.categories || 0 }}</div>
            <div class="text-gray-400 text-sm">Категорий</div>
          </div>
          <div class="bg-gray-800 rounded-lg p-4 text-center" v-if="stats">
            <div class="text-2xl font-bold text-purple-400">{{ stats.products?.matryoshka || 0 }}</div>
            <div class="text-gray-400 text-sm">Матрёшек</div>
          </div>
        </div>
        
        <button @click="logout" class="bg-red-600 hover:bg-red-700 text-white px-4 py-2 rounded-lg transition-colors">
          Выйти
        </button>
      </div>

      <!-- Навигация -->
      <div class="bg-gray-800 rounded-lg p-2 mb-8">
        <nav class="flex flex-wrap space-x-1">
          <button
            v-for="tab in tabs"
            :key="tab.id"
            @click="activeTab = tab.id"
            class="px-4 py-2 rounded-lg text-sm font-medium transition-colors"
            :class="activeTab === tab.id ? 'bg-blue-600 text-white' : 'text-gray-400 hover:bg-gray-700 hover:text-white'"
          >
            {{ tab.name }}
          </button>
        </nav>
      </div>

      <!-- Контент -->
      <div class="bg-gray-800 rounded-lg p-6">
        
        <!-- Товары -->
        <div v-if="activeTab === 'products'">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-white">Управление товарами</h2>
            <div class="flex space-x-2 action-buttons">
              <router-link 
                to="/admin/products/create" 
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors button"
              >
                + Создать товар
              </router-link>
              <button 
                @click="loadProducts"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
                :disabled="loading"
              >
                {{ loading ? 'Загрузка...' : 'Обновить' }}
              </button>
            </div>
          </div>
          
          <!-- Фильтры -->
          <div class="bg-gray-700 rounded-lg p-4 mb-6">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-4">
              <div>
                <label class="block text-sm font-medium text-white mb-2">Поиск</label>
                <input
                  v-model="productSearch"
                  type="text"
                  placeholder="Название товара..."
                  class="w-full bg-gray-600 border border-gray-500 rounded-lg px-3 py-2 text-white"
                  @input="debouncedSearch"
                >
              </div>
              <div>
                <label class="block text-sm font-medium text-white mb-2">Тема</label>
                <select 
                  v-model="productThemeFilter"
                  class="w-full bg-gray-600 border border-gray-500 rounded-lg px-3 py-2 text-white"
                  @change="loadProducts"
                >
                  <option value="">Все темы</option>
                  <option v-for="theme in themes" :key="theme.id" :value="theme.id">
                    {{ theme.name }}
                  </option>
                </select>
              </div>
              <div>
                <label class="block text-sm font-medium text-white mb-2">Сортировка</label>
                <select 
                  v-model="productSort"
                  class="w-full bg-gray-600 border border-gray-500 rounded-lg px-3 py-2 text-white"
                  @change="loadProducts"
                >
                  <option value="created_at">По дате создания</option>
                  <option value="name">По названию</option>
                  <option value="price">По цене</option>
                </select>
              </div>
              <div class="flex items-end">
                <button 
                  @click="clearFilters"
                  class="bg-gray-600 hover:bg-gray-500 text-white px-4 py-2 rounded-lg transition-colors w-full"
                >
                  Сбросить
                </button>
              </div>
            </div>
          </div>
          
          <!-- Список товаров -->
          <div class="bg-gray-700 rounded-lg overflow-hidden table-responsive">
            <table class="w-full product-table">
              <thead class="bg-gray-600">
                <tr>
                  <th class="px-4 py-3 text-left text-white font-medium">Товар</th>
                  <th class="px-4 py-3 text-left text-white font-medium">Цена</th>
                  <th class="px-4 py-3 text-left text-white font-medium">Тема</th>
                  <th class="px-4 py-3 text-left text-white font-medium">Тип</th>
                  <th class="px-4 py-3 text-left text-white font-medium">Дата</th>
                  <th class="px-4 py-3 text-center text-white font-medium">Действия</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="product in products.data" :key="product.id" class="border-t border-gray-600 hover:bg-gray-600">
                  <td class="px-4 py-3">
                    <div class="flex items-center space-x-3">
                      <div class="w-12 h-12 bg-gray-500 rounded-lg flex items-center justify-center">
                        <img 
                          v-if="product.main_image"
                          :src="`/storage/images/products/${product.main_image}`"
                          class="w-full h-full object-cover rounded-lg"
                          @error="$event.target.style.display='none'"
                        >
                        <span v-else class="text-gray-400 text-xs">Нет фото</span>
                      </div>
                      <div>
                        <div class="text-white font-medium">{{ product.name }}</div>
                        <div class="text-gray-400 text-sm">{{ product.slug }}</div>
                      </div>
                    </div>
                  </td>
                  <td class="px-4 py-3 text-white">{{ formatPrice(product.price) }}</td>
                  <td class="px-4 py-3 text-gray-300">{{ product.theme?.name || 'Без темы' }}</td>
                  <td class="px-4 py-3">
                    <span 
                      class="px-2 py-1 rounded-full text-xs font-medium"
                      :class="product.use_matryoshka ? 'bg-purple-600 text-purple-100' : 'bg-blue-600 text-blue-100'"
                    >
                      {{ product.use_matryoshka ? 'Матрёшка' : 'Обычный' }}
                    </span>
                  </td>
                  <td class="px-4 py-3 text-gray-300">{{ formatDate(product.created_at) }}</td>
                  <td class="px-4 py-3">
                    <div class="flex justify-center space-x-2 action-buttons">
                      <router-link 
                        :to="`/admin/products/edit/${product.id}`"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm transition-colors button"
                      >
                        Редактировать
                      </router-link>
                      <button 
                        @click="deleteProduct(product.id)"
                        class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm transition-colors"
                      >
                        Удалить
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
            
            <!-- Пагинация -->
            <div v-if="products.last_page > 1" class="bg-gray-600 px-4 py-3 flex justify-between items-center">
              <div class="text-gray-300 text-sm">
                Показано {{ products.from }}-{{ products.to }} из {{ products.total }}
              </div>
              <div class="flex space-x-2">
                <button 
                  v-for="page in paginationPages" 
                  :key="page"
                  @click="loadProducts(page)"
                  class="px-3 py-1 rounded text-sm transition-colors"
                  :class="page === products.current_page ? 'bg-blue-600 text-white' : 'bg-gray-500 text-gray-300 hover:bg-gray-400'"
                >
                  {{ page }}
                </button>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Категории -->
        <div v-if="activeTab === 'categories'">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-white">Управление категориями</h2>
            <button 
              @click="createCategory"
              class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors"
            >
              + Создать категорию
            </button>
          </div>
          
          <div class="bg-gray-700 rounded-lg overflow-hidden table-responsive">
            <table class="w-full category-table">
              <thead class="bg-gray-600">
                <tr>
                  <th class="px-4 py-3 text-left text-white font-medium">Название</th>
                  <th class="px-4 py-3 text-left text-white font-medium">Родитель</th>
                  <th class="px-4 py-3 text-left text-white font-medium">Товаров</th>
                  <th class="px-4 py-3 text-left text-white font-medium">Порядок</th>
                  <th class="px-4 py-3 text-center text-white font-medium">Действия</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="category in categories" :key="category.id" class="border-t border-gray-600 hover:bg-gray-600">
                  <td class="px-4 py-3">
                    <div class="text-white font-medium">{{ category.name }}</div>
                    <div class="text-gray-400 text-sm">{{ category.slug }}</div>
                  </td>
                  <td class="px-4 py-3 text-gray-300">
                    {{ category.parent?.name || 'Корневая' }}
                  </td>
                  <td class="px-4 py-3 text-gray-300">{{ category.products_count || 0 }}</td>
                  <td class="px-4 py-3 text-gray-300">{{ category.sort_order || 0 }}</td>
                  <td class="px-4 py-3">
                    <div class="flex justify-center space-x-2 action-buttons">
                      <button 
                        @click="editCategory(category)"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm transition-colors"
                      >
                        Редактировать
                      </button>
                      <button 
                        @click="deleteCategory(category.id)"
                        class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm transition-colors"
                      >
                        Удалить
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        
        <!-- Темы -->
        <div v-if="activeTab === 'themes'">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-white">Управление темами</h2>
            <button 
              @click="createTheme"
              class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors"
            >
              + Создать тему
            </button>
          </div>
          
          <div class="bg-gray-700 rounded-lg overflow-hidden table-responsive">
            <table class="w-full">
              <thead class="bg-gray-600">
                <tr>
                  <th class="px-4 py-3 text-left text-white font-medium">Название</th>
                  <th class="px-4 py-3 text-left text-white font-medium">Товаров</th>
                  <th class="px-4 py-3 text-left text-white font-medium">Дата создания</th>
                  <th class="px-4 py-3 text-center text-white font-medium">Действия</th>
                </tr>
              </thead>
              <tbody>
                <tr v-for="theme in themes" :key="theme.id" class="border-t border-gray-600 hover:bg-gray-600">
                  <td class="px-4 py-3 text-white font-medium">{{ theme.name }}</td>
                  <td class="px-4 py-3 text-gray-300">{{ theme.products_count || 0 }}</td>
                  <td class="px-4 py-3 text-gray-300">{{ formatDate(theme.created_at) }}</td>
                  <td class="px-4 py-3">
                    <div class="flex justify-center space-x-2 action-buttons">
                      <button 
                        @click="editTheme(theme)"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm transition-colors"
                      >
                        Редактировать
                      </button>
                      <button 
                        @click="deleteTheme(theme.id)"
                        class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm transition-colors"
                      >
                        Удалить
                      </button>
                    </div>
                  </td>
                </tr>
              </tbody>
            </table>
          </div>
        </div>
        
        <!-- Атрибуты -->
        <div v-if="activeTab === 'attributes'">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-white">Управление атрибутами</h2>
            <button 
              @click="createAttribute"
              class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors"
            >
              + Создать атрибут
            </button>
          </div>
          
          <div class="form-grid">
            <!-- Список атрибутов -->
            <div class="bg-gray-700 rounded-lg overflow-hidden">
              <div class="bg-gray-600 px-4 py-3">
                <h3 class="text-white font-medium">Атрибуты</h3>
              </div>
              <div class="max-h-96 overflow-y-auto">
                <div 
                  v-for="attribute in attributes" 
                  :key="attribute.id"
                  class="px-4 py-3 border-b border-gray-600 hover:bg-gray-600 cursor-pointer"
                  :class="selectedAttribute?.id === attribute.id ? 'bg-gray-600' : ''"
                  @click="selectAttribute(attribute)"
                >
                  <div class="flex justify-between items-center">
                    <div>
                      <div class="text-white font-medium">{{ attribute.name }}</div>
                      <div class="text-gray-400 text-sm">
                        {{ attribute.values_count || 0 }} значений, {{ attribute.products_count || 0 }} товаров
                        <span v-if="attribute.is_stone" class="ml-2 px-2 py-1 bg-purple-600 text-purple-100 rounded-full text-xs">
                          Камень
                        </span>
                      </div>
                    </div>
                    <div class="flex space-x-2">
                      <button 
                        @click.stop="editAttribute(attribute)"
                        class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs transition-colors"
                      >
                        Редактировать
                      </button>
                      <button 
                        @click.stop="deleteAttribute(attribute.id)"
                        class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-xs transition-colors"
                      >
                        Удалить
                      </button>
                    </div>
                  </div>
                </div>
              </div>
            </div>
            
            <!-- Значения выбранного атрибута -->
            <div class="bg-gray-700 rounded-lg overflow-hidden">
              <div class="bg-gray-600 px-4 py-3 flex justify-between items-center">
                <h3 class="text-white font-medium">
                  {{ selectedAttribute ? `Значения: ${selectedAttribute.name}` : 'Выберите атрибут' }}
                </h3>
                <button 
                  v-if="selectedAttribute"
                  @click="createAttributeValue"
                  class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm transition-colors"
                >
                  + Добавить
                </button>
              </div>
              <div class="max-h-96 overflow-y-auto">
                <div v-if="selectedAttribute && attributeValues.length > 0">
                  <div 
                    v-for="value in attributeValues" 
                    :key="value.id"
                    class="px-4 py-3 border-b border-gray-600 hover:bg-gray-600"
                  >
                    <div class="flex justify-between items-center">
                      <div>
                        <div class="text-white font-medium">{{ value.value }}</div>
                        <div class="text-gray-400 text-sm">
                          {{ value.products_count || 0 }} товаров
                          <span v-if="!value.is_active" class="ml-2 px-2 py-1 bg-gray-500 text-gray-200 rounded-full text-xs">
                            Неактивен
                          </span>
                        </div>
                      </div>
                      <div class="flex space-x-2">
                        <button 
                          @click="editAttributeValue(value)"
                          class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs transition-colors"
                        >
                          Редактировать
                        </button>
                        <button 
                          @click="deleteAttributeValue(value.id)"
                          class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-xs transition-colors"
                        >
                          Удалить
                        </button>
                      </div>
                    </div>
                  </div>
                </div>
                <div v-else-if="selectedAttribute" class="px-4 py-8 text-center text-gray-400">
                  У этого атрибута пока нет значений
                </div>
                <div v-else class="px-4 py-8 text-center text-gray-400">
                  Выберите атрибут для просмотра его значений
                </div>
              </div>
            </div>
          </div>
        </div>
        
        <!-- Изображения -->
        <div v-if="activeTab === 'images'">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-white">Управление изображениями</h2>
            <div class="flex space-x-2 action-buttons">
              <input 
                ref="imageUpload"
                type="file" 
                multiple 
                accept="image/*"
                @change="uploadImages"
                class="hidden"
              >
              <button 
                @click="$refs.imageUpload.click()"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors clickable"
              >
                + Загрузить изображения
              </button>
              <button 
                @click="loadImages"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
                :disabled="loading"
              >
                {{ loading ? 'Загрузка...' : 'Обновить' }}
              </button>
            </div>
          </div>
          
          <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <div 
              v-for="image in images" 
              :key="image.filename"
              class="relative group bg-gray-700 rounded-lg overflow-hidden"
            >
              <img 
                :src="image.path"
                class="w-full h-32 object-cover"
                @error="$event.target.style.display='none'"
              >
              <div class="absolute inset-0 bg-black bg-opacity-0 group-hover:bg-opacity-50 transition-opacity flex items-center justify-center">
                <button 
                  @click="deleteImage(image.filename)"
                  class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-sm transition-colors opacity-0 group-hover:opacity-100"
                >
                  Удалить
                </button>
              </div>
              <div class="absolute bottom-0 left-0 right-0 bg-black bg-opacity-75 text-white p-2">
                <div class="text-xs truncate">{{ image.filename }}</div>
              </div>
            </div>
          </div>
          
          <div v-if="images.length === 0" class="text-center py-12 text-gray-400">
            Нет загруженных изображений
          </div>
        </div>
        
        <!-- Управление маркетплейсами -->
        <div v-show="activeTab === 'marketplace'" class="marketplace-section">
          <MarketplaceAdmin />
        </div>
        
        <!-- Квиз -->
        <div v-show="activeTab === 'quiz'" class="quiz-section">
          <QuizAdmin />
        </div>
        
      </div>
    </div>

    <!-- Модальное окно категории -->
    <div v-if="showCategoryModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-gray-800 rounded-lg p-6 max-w-md w-full">
        <h3 class="text-xl text-white mb-4">
          {{ categoryForm.id ? 'Редактировать категорию' : 'Создать категорию' }}
        </h3>
        
        <form @submit.prevent="saveCategoryForm">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-white mb-2">Название</label>
              <input
                v-model="categoryForm.name"
                type="text"
                required
                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white"
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-white mb-2">Родительская категория</label>
              <select 
                v-model="categoryForm.parent_id"
                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white"
              >
                <option value="">Корневая категория</option>
                <option v-for="category in categories" :key="category.id" :value="category.id">
                  {{ category.name }}
                </option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-white mb-2">Порядок сортировки</label>
              <input
                v-model.number="categoryForm.sort_order"
                type="number"
                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white"
              >
            </div>
          </div>
          
          <div class="flex justify-end space-x-3 mt-6">
            <button 
              type="button"
              @click="showCategoryModal = false"
              class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors"
            >
              Отмена
            </button>
            <button 
              type="submit"
              class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
              :disabled="saving"
            >
              {{ saving ? 'Сохранение...' : 'Сохранить' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Модальное окно темы -->
    <div v-if="showThemeModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-gray-800 rounded-lg p-6 max-w-md w-full">
        <h3 class="text-xl text-white mb-4">
          {{ themeForm.id ? 'Редактировать тему' : 'Создать тему' }}
        </h3>
        
        <form @submit.prevent="saveThemeForm">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-white mb-2">Название</label>
              <input
                v-model="themeForm.name"
                type="text"
                required
                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white"
              >
            </div>
          </div>
          
          <div class="flex justify-end space-x-3 mt-6">
            <button 
              type="button"
              @click="showThemeModal = false"
              class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors"
            >
              Отмена
            </button>
            <button 
              type="submit"
              class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
              :disabled="saving"
            >
              {{ saving ? 'Сохранение...' : 'Сохранить' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Модальное окно атрибута -->
    <div v-if="showAttributeModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-gray-800 rounded-lg p-6 max-w-md w-full">
        <h3 class="text-xl text-white mb-4">
          {{ attributeForm.id ? 'Редактировать атрибут' : 'Создать атрибут' }}
        </h3>
        
        <form @submit.prevent="saveAttributeForm">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-white mb-2">Название</label>
              <input
                v-model="attributeForm.name"
                type="text"
                required
                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white"
              >
            </div>
          </div>
          
          <div class="flex justify-end space-x-3 mt-6">
            <button 
              type="button"
              @click="showAttributeModal = false"
              class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors"
            >
              Отмена
            </button>
            <button 
              type="submit"
              class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
              :disabled="saving"
            >
              {{ saving ? 'Сохранение...' : 'Сохранить' }}
            </button>
          </div>
        </form>
      </div>
    </div>

    <!-- Модальное окно значения атрибута -->
    <div v-if="showAttributeValueModal" class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50">
      <div class="bg-gray-800 rounded-lg p-6 max-w-md w-full">
        <h3 class="text-xl text-white mb-4">
          {{ attributeValueForm.id ? 'Редактировать значение' : 'Создать значение' }}
        </h3>
        
        <form @submit.prevent="saveAttributeValueForm">
          <div class="space-y-4">
            <div>
              <label class="block text-sm font-medium text-white mb-2">Значение</label>
              <input
                v-model="attributeValueForm.value"
                type="text"
                required
                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white"
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-white mb-2">Порядок сортировки</label>
              <input
                v-model.number="attributeValueForm.sort_order"
                type="number"
                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white"
              >
            </div>
            
            <div class="flex items-center">
              <input
                v-model="attributeValueForm.is_active"
                type="checkbox"
                id="is_active"
                class="mr-2"
              >
              <label for="is_active" class="text-white">Активно</label>
            </div>
          </div>
          
          <div class="flex justify-end space-x-3 mt-6">
            <button 
              type="button"
              @click="showAttributeValueModal = false"
              class="bg-gray-600 hover:bg-gray-700 text-white px-4 py-2 rounded-lg transition-colors"
            >
              Отмена
            </button>
            <button 
              type="submit"
              class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
              :disabled="saving"
            >
              {{ saving ? 'Сохранение...' : 'Сохранить' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, reactive, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import { axios } from '../main.js';
import MarketplaceManager from '../components/MarketplaceManager.vue'
import ProductImageManager from '../components/ProductImageManager.vue'
import MarketplaceAdmin from '../components/admin/MarketplaceAdmin.vue';
import QuizAdmin from '../components/admin/QuizAdmin.vue';

export default {
  name: 'AdminPanel',
  components: {
    MarketplaceManager,
    ProductImageManager,
    MarketplaceAdmin,
    QuizAdmin 
  },
  setup() {
    const router = useRouter();
    
    // Состояние приложения
    const activeTab = ref('products');
    const loading = ref(false);
    const saving = ref(false);
    
    // Данные
    const products = ref({ data: [], current_page: 1, last_page: 1 });
    const categories = ref([]);
    const themes = ref([]);
    const attributes = ref([]);
    const attributeValues = ref([]);
    const selectedAttribute = ref(null);
    const images = ref([]);
    const stats = ref(null);
    
    // Фильтры и поиск
    const productSearch = ref('');
    const productThemeFilter = ref('');
    const productSort = ref('created_at');
    
    // Модальные окна
    const showCategoryModal = ref(false);
    const showThemeModal = ref(false);
    const showAttributeModal = ref(false);
    const showAttributeValueModal = ref(false);
    
    // Формы
    const categoryForm = reactive({
      id: null,
      name: '',
      parent_id: null,
      sort_order: 0
    });
    
    const themeForm = reactive({
      id: null,
      name: ''
    });
    
    const attributeForm = reactive({
      id: null,
      name: ''
    });
    
    const attributeValueForm = reactive({
      id: null,
      attribute_id: null,
      value: '',
      sort_order: 0,
      is_active: true
    });
    
    // Табы
    const tabs = ref([
      { id: 'overview', name: 'Обзор', icon: '📊' },
      { id: 'products', name: 'Товары', icon: '📦' },
      { id: 'categories', name: 'Категории', icon: '📂' },
      { id: 'themes', name: 'Темы', icon: '🎨' },
      { id: 'attributes', name: 'Атрибуты', icon: '🏷️' },
      { id: 'images', name: 'Изображения', icon: '🖼️' },
      { id: 'marketplace', name: 'Маркетплейсы', icon: '🛒' },  // НОВАЯ
      { id: 'quiz', name: 'Квиз', icon: '🔮' }  // НОВАЯ
    ]);
    
    // Computed
    const paginationPages = computed(() => {
      const pages = [];
      const current = products.value.current_page;
      const last = products.value.last_page;
      
      for (let i = Math.max(1, current - 2); i <= Math.min(last, current + 2); i++) {
        pages.push(i);
      }
      
      return pages;
    });
    
    // Методы загрузки данных
    const loadStats = async () => {
      try {
        const response = await axios.get('/admin/stats');
        stats.value = response.data;
      } catch (error) {
        console.error('Ошибка загрузки статистики:', error);
      }
    };
    
    
    
    const loadProducts = async (page = 1) => {
      try {
        loading.value = true;
        
        const params = {
          page: page,
          search: productSearch.value || '',
          theme_id: productThemeFilter.value || '',
          sort_by: productSort.value || 'created_at',
          per_page: 15
        };
    
        const response = await axios.get('/admin/products', { params });
        products.value = response.data;
        
      } catch (error) {
        console.error('Ошибка загрузки товаров:', error);
        
        // Детальная обработка ошибок
        if (error.response?.status === 500) {
          alert('Ошибка сервера при загрузке товаров. Проверьте логи сервера.');
        } else if (error.response?.status === 404) {
          alert('API метод не найден. Проверьте маршруты.');
        } else {
          alert('Ошибка загрузки товаров: ' + (error.message || 'Неизвестная ошибка'));
        }
        
      } finally {
        loading.value = false;
      }
    };
    
    const loadCategories = async () => {
      try {
        const response = await axios.get('/admin/categories');
        categories.value = response.data;
      } catch (error) {
        console.error('Ошибка загрузки категорий:', error);
      }
    };
    
    const loadThemes = async () => {
      try {
        const response = await axios.get('/admin/themes');
        themes.value = response.data;
      } catch (error) {
        console.error('Ошибка загрузки тем:', error);
      }
    };
    
    const loadAttributes = async () => {
      try {
        const response = await axios.get('/admin/attributes');
        attributes.value = response.data;
      } catch (error) {
        console.error('Ошибка загрузки атрибутов:', error);
      }
    };
    
    const loadAttributeValues = async (attributeId) => {
      try {
        const response = await axios.get(`/admin/attributes/${attributeId}/values`);
        attributeValues.value = response.data.values;
      } catch (error) {
        console.error('Ошибка загрузки значений атрибутов:', error);
        attributeValues.value = [];
      }
    };
    
    const loadImages = async () => {
      try {
        loading.value = true;
        const response = await axios.get('/admin/images');
        images.value = response.data.data || response.data;
      } catch (error) {
        console.error('Ошибка загрузки изображений:', error);
      } finally {
        loading.value = false;
      }
    };
    
    // Методы работы с товарами
    const deleteProduct = async (id) => {
      if (!confirm('Удалить этот товар? Это действие нельзя отменить.')) return;
      
      try {
        await axios.delete(`/admin/products/${id}`);
        alert('Товар удален!');
        loadProducts();
        loadStats();
      } catch (error) {
        console.error('Ошибка удаления товара:', error);
        alert('Ошибка удаления товара');
      }
    };
    
    // Методы работы с категориями
    const createCategory = () => {
      Object.assign(categoryForm, {
        id: null,
        name: '',
        parent_id: null,
        sort_order: 0
      });
      showCategoryModal.value = true;
    };
    
    const editCategory = (category) => {
      Object.assign(categoryForm, {
        id: category.id,
        name: category.name,
        parent_id: category.parent_id,
        sort_order: category.sort_order || 0
      });
      showCategoryModal.value = true;
    };
    
    const saveCategoryForm = async () => {
      // Проверяем обязательные поля
      if (!categoryForm.name.trim()) {
        alert('Название категории обязательно');
        return;
      }
    
      // Проверяем на циклические ссылки
      if (categoryForm.id && categoryForm.parent_id === categoryForm.id) {
        alert('Категория не может быть родителем самой себе');
        return;
      }
    
      try {
        saving.value = true;
        
        const data = {
          name: categoryForm.name.trim(),
          parent_id: categoryForm.parent_id || null,
          sort_order: categoryForm.sort_order || 0,
          meta_title: categoryForm.meta_title || null,
          meta_description: categoryForm.meta_description || null
        };
    
        let response;
        if (categoryForm.id) {
          // Обновление
          response = await axios.put(`/admin/categories/${categoryForm.id}`, data);
        } else {
          // Создание
          response = await axios.post('/admin/categories', data);
        }
    
        alert(response.data.message || 'Категория сохранена!');
        showCategoryModal.value = false;
        loadCategories();
        loadStats();
        
      } catch (error) {
        console.error('Ошибка сохранения категории:', error);
        
        // Показываем детальные ошибки валидации
        if (error.response?.status === 422 && error.response.data.errors) {
          const errors = error.response.data.errors;
          const errorMessages = Object.values(errors).flat();
          alert('Ошибки валидации:\n' + errorMessages.join('\n'));
        } else {
          alert('Ошибка сохранения категории: ' + (error.response?.data?.message || error.message));
        }
        
      } finally {
        saving.value = false;
      }
    };
    
    const debugApi = async () => {
      console.log('=== Отладка API ===');
      
      // Проверяем доступность маршрутов
      const routes = [
        '/admin/products',
        '/admin/categories', 
        '/admin/themes',
        '/admin/attributes',
        '/admin/images'
      ];
    
      for (const route of routes) {
        try {
          const response = await axios.get(route);
          console.log(`✅ ${route}:`, response.status);
        } catch (error) {
          console.error(`❌ ${route}:`, error.response?.status || error.message);
        }
      }
    };
    
    // НОВОЕ: Проверка подключения к API
    const checkApiConnection = async () => {
      try {
        const response = await axios.get('/admin/stats');
        console.log('API подключение работает:', response.status === 200);
        return true;
      } catch (error) {
        console.error('API недоступно:', error);
        return false;
      }
    };
    
    const deleteCategory = async (id) => {
      if (!confirm('Удалить эту категорию? Все товары в ней потеряют категорию.')) return;
      
      try {
        await axios.delete(`/admin/categories/${id}`);
        alert('Категория удалена!');
        loadCategories();
        loadStats();
      } catch (error) {
        console.error('Ошибка удаления категории:', error);
        alert('Ошибка удаления категории');
      }
    };
    
    // Методы работы с темами
    const createTheme = () => {
      Object.assign(themeForm, {
        id: null,
        name: ''
      });
      showThemeModal.value = true;
    };
    
    const editTheme = (theme) => {
      Object.assign(themeForm, {
        id: theme.id,
        name: theme.name
      });
      showThemeModal.value = true;
    };
    
    const saveThemeForm = async () => {
      try {
        saving.value = true;
        
        if (themeForm.id) {
          await axios.put(`/admin/themes/${themeForm.id}`, themeForm);
          alert('Тема обновлена!');
        } else {
          await axios.post('/admin/themes', themeForm);
          alert('Тема создана!');
        }
        
        showThemeModal.value = false;
        loadThemes();
        loadStats();
      } catch (error) {
        console.error('Ошибка сохранения темы:', error);
        alert('Ошибка сохранения темы');
      } finally {
        saving.value = false;
      }
    };
    
    const deleteTheme = async (id) => {
      if (!confirm('Удалить эту тему? Все товары в ней потеряют тему.')) return;
      
      try {
        await axios.delete(`/admin/themes/${id}`);
        alert('Тема удалена!');
        loadThemes();
        loadStats();
      } catch (error) {
        console.error('Ошибка удаления темы:', error);
        alert('Ошибка удаления темы');
      }
    };
    
    // Методы работы с атрибутами
    const selectAttribute = (attribute) => {
      selectedAttribute.value = attribute;
      loadAttributeValues(attribute.id);
    };
    
    const createAttribute = () => {
      Object.assign(attributeForm, {
        id: null,
        name: ''
      });
      showAttributeModal.value = true;
    };
    
    const editAttribute = (attribute) => {
      Object.assign(attributeForm, {
        id: attribute.id,
        name: attribute.name
      });
      showAttributeModal.value = true;
    };
    
    const saveAttributeForm = async () => {
      try {
        saving.value = true;
        
        if (attributeForm.id) {
          await axios.put(`/admin/attributes/${attributeForm.id}`, attributeForm);
          alert('Атрибут обновлен!');
        } else {
          await axios.post('/admin/attributes', attributeForm);
          alert('Атрибут создан!');
        }
        
        showAttributeModal.value = false;
        loadAttributes();
      } catch (error) {
        console.error('Ошибка сохранения атрибута:', error);
        alert('Ошибка сохранения атрибута');
      } finally {
        saving.value = false;
      }
    };
    
    const deleteAttribute = async (id) => {
      if (!confirm('Удалить этот атрибут? Все связанные товары потеряют этот атрибут.')) return;
      
      try {
        await axios.delete(`/admin/attributes/${id}`);
        alert('Атрибут удален!');
        loadAttributes();
        if (selectedAttribute.value?.id === id) {
          selectedAttribute.value = null;
          attributeValues.value = [];
        }
      } catch (error) {
        console.error('Ошибка удаления атрибута:', error);
        alert('Ошибка удаления атрибута');
      }
    };
    
    // Методы работы со значениями атрибутов
    const createAttributeValue = () => {
      if (!selectedAttribute.value) {
        alert('Сначала выберите атрибут');
        return;
      }
      
      Object.assign(attributeValueForm, {
        id: null,
        attribute_id: selectedAttribute.value.id,
        value: '',
        sort_order: 0,
        is_active: true
      });
      showAttributeValueModal.value = true;
    };
    
    const editAttributeValue = (value) => {
      Object.assign(attributeValueForm, {
        id: value.id,
        attribute_id: value.attribute_id,
        value: value.value,
        sort_order: value.sort_order || 0,
        is_active: value.is_active
      });
      showAttributeValueModal.value = true;
    };
    
    const saveAttributeValueForm = async () => {
      try {
        saving.value = true;
        
        if (attributeValueForm.id) {
          await axios.put(`/admin/attribute-values/${attributeValueForm.id}`, attributeValueForm);
          alert('Значение обновлено!');
        } else {
          await axios.post(`/admin/attributes/${attributeValueForm.attribute_id}/values`, attributeValueForm);
          alert('Значение создано!');
        }
        
        showAttributeValueModal.value = false;
        loadAttributeValues(selectedAttribute.value.id);
        loadAttributes();
      } catch (error) {
        console.error('Ошибка сохранения значения:', error);
        alert('Ошибка сохранения значения');
      } finally {
        saving.value = false;
      }
    };
    
    const deleteAttributeValue = async (id) => {
      if (!confirm('Удалить это значение?')) return;
      
      try {
        await axios.delete(`/admin/attribute-values/${id}`);
        alert('Значение удалено!');
        loadAttributeValues(selectedAttribute.value.id);
        loadAttributes();
      } catch (error) {
        console.error('Ошибка удаления значения:', error);
        alert('Ошибка удаления значения');
      }
    };
    
    // Методы работы с изображениями
    const uploadImages = async (event) => {
      const files = event.target.files;
      if (!files.length) return;
    
      try {
        saving.value = true;
        
        // ИСПРАВЛЕНО: Загружаем файлы по одному, так как AdminController ожидает поле 'image'
        for (let i = 0; i < files.length; i++) {
          const formData = new FormData();
          formData.append('image', files[i]); // ИЗМЕНЕНО: используем 'image' вместо 'images[]'
          formData.append('type', 'product'); // ДОБАВЛЕНО: указываем тип изображения
          
          try {
            const response = await axios.post('/admin/images', formData, {
              headers: { 
                'Content-Type': 'multipart/form-data' 
              },
              timeout: 30000 // 30 секунд на файл
            });
            
            console.log(`Файл ${i + 1}/${files.length} загружен:`, response.data.filename);
            
          } catch (fileError) {
            console.error(`Ошибка загрузки файла ${files[i].name}:`, fileError);
            // Продолжаем загрузку других файлов даже если один упал
          }
        }
        
        alert(`Загрузка завершена! Обработано файлов: ${files.length}`);
        loadImages(); // Обновляем список изображений
        
      } catch (error) {
        console.error('Общая ошибка загрузки изображений:', error);
        alert('Ошибка загрузки изображений: ' + (error.response?.data?.message || error.message));
        
      } finally {
        saving.value = false;
        event.target.value = ''; // Очищаем input
      }
    };
    
    const deleteImage = async (filename) => {
      if (!confirm('Удалить это изображение?')) return;
      
      try {
        await axios.delete(`/admin/images/${filename}`);
        alert('Изображение удалено!');
        loadImages();
      } catch (error) {
        console.error('Ошибка удаления изображения:', error);
        alert('Ошибка удаления изображения');
      }
    };
    
    // Вспомогательные методы
    const formatPrice = (price) => {
      return new Intl.NumberFormat('ru-RU', {
        style: 'currency',
        currency: 'RUB'
      }).format(price);
    };
    
    const formatDate = (date) => {
      return new Date(date).toLocaleDateString('ru-RU');
    };
    
    const clearFilters = () => {
      productSearch.value = '';
      productThemeFilter.value = '';
      productSort.value = 'created_at';
      loadProducts();
    };
    
    const debouncedSearch = debounce(() => {
      loadProducts();
    }, 500);
    
    function debounce(func, wait) {
      let timeout;
      return function executedFunction(...args) {
        const later = () => {
          clearTimeout(timeout);
          func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
      };
    }
    
    const logout = async () => {
      try {
        await axios.post('/admin/logout');
      } catch (error) {
        console.error('Ошибка выхода:', error);
      } finally {
        localStorage.removeItem('auth_token');
        delete axios.defaults.headers.common['Authorization'];
        router.push('/admin/login');
      }
    };
    
    // Загрузка данных при монтировании
    onMounted(() => {
      loadStats();
      loadProducts();
      loadCategories();
      loadThemes();
      loadAttributes();
      loadImages();
    });
    
    return {
      // Состояние
      activeTab,
      loading,
      saving,
      tabs,
      
      // Данные
      products,
      categories,
      themes,
      attributes,
      attributeValues,
      selectedAttribute,
      images,
      stats,
      
      // Фильтры
      productSearch,
      productThemeFilter,
      productSort,
      
      // Модальные окна
      showCategoryModal,
      showThemeModal,
      showAttributeModal,
      showAttributeValueModal,
      
      // Формы
      categoryForm,
      themeForm,
      attributeForm,
      attributeValueForm,
      
      // Computed
      paginationPages,
      
      // Методы
      loadProducts,
      loadCategories,
      loadThemes,
      loadAttributes,
      loadAttributeValues,
      loadImages,
      deleteProduct,
      createCategory,
      editCategory,
      saveCategoryForm,
      deleteCategory,
      createTheme,
      editTheme,
      saveThemeForm,
      deleteTheme,
      selectAttribute,
      createAttribute,
      editAttribute,
      saveAttributeForm,
      deleteAttribute,
      createAttributeValue,
      editAttributeValue,
      saveAttributeValueForm,
      deleteAttributeValue,
      uploadImages,
      deleteImage,
      formatPrice,
      formatDate,
      clearFilters,
      debouncedSearch,
      logout
    };
  }
};
</script>

<style scoped>
/* Мобильная навигация */
@media (max-width: 768px) {
  .admin-panel {
    padding: 1rem;
  }
  
  /* Табы в виде выпадающего меню на мобильных */
  .tabs-container {
    position: relative;
  }
  
  .tabs-mobile-toggle {
    display: block;
    width: 100%;
    background: #374151;
    color: white;
    padding: 0.75rem 1rem;
    border-radius: 0.5rem;
    text-align: left;
  }
  
  .tabs-list {
    position: absolute;
    top: 100%;
    left: 0;
    right: 0;
    background: #1f2937;
    border-radius: 0.5rem;
    margin-top: 0.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.3);
    z-index: 50;
  }
  
  /* Таблицы на мобильных */
  .table-responsive {
    overflow-x: auto;
    -webkit-overflow-scrolling: touch;
  }
  
  table {
    min-width: 600px;
  }
  
  /* Карточки вместо таблиц на маленьких экранах */
  @media (max-width: 640px) {
    .product-table,
    .category-table {
      display: none;
    }
    
    .product-cards,
    .category-cards {
      display: block;
    }
    
    .product-card,
    .category-card {
      background: #374151;
      border-radius: 0.5rem;
      padding: 1rem;
      margin-bottom: 1rem;
    }
  }
  
  /* Формы на мобильных */
  .form-grid {
    display: grid;
    grid-template-columns: 1fr;
    gap: 1rem;
  }
  
  @media (min-width: 768px) {
    .form-grid {
      grid-template-columns: repeat(2, 1fr);
    }
  }
  
  @media (min-width: 1024px) {
    .form-grid {
      grid-template-columns: repeat(3, 1fr);
    }
  }
  
  /* Кнопки на мобильных */
  .action-buttons {
    display: flex;
    flex-direction: column;
    gap: 0.5rem;
  }
  
  @media (min-width: 640px) {
    .action-buttons {
      flex-direction: row;
    }
  }
}

/* Улучшенная доступность */
button:focus,
a:focus,
select:focus,
input:focus,
textarea:focus {
  outline: 2px solid #3B82F6;
  outline-offset: 2px;
}

/* Плавные переходы */
* {
  transition: background-color 0.2s, border-color 0.2s, color 0.2s;
}

/* Тач-френдли размеры */
button,
a.button,
.clickable {
  min-height: 44px;
  min-width: 44px;
}

/* Улучшенные hover эффекты для тач-устройств */
@media (hover: hover) {
  button:hover,
  a:hover {
    transform: translateY(-1px);
  }
}
</style>