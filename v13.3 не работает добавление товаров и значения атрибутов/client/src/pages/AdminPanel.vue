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
            <div class="flex space-x-2">
              <router-link 
                to="/admin/products/create" 
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors"
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

          <!-- Поиск и фильтры товаров -->
          <div class="mb-6 grid grid-cols-1 md:grid-cols-4 gap-4">
            <input
              v-model="productSearch"
              @input="searchProducts"
              type="text"
              placeholder="Поиск товаров..."
              class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white placeholder-gray-400"
            >
            <select 
              v-model="productThemeFilter"
              @change="filterProducts"
              class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white"
            >
              <option value="">Все темы</option>
              <option v-for="theme in themes" :key="theme.id" :value="theme.id">{{ theme.name }}</option>
            </select>
            <select 
              v-model="productCategoryFilter"
              @change="filterProducts"
              class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white"
            >
              <option value="">Все категории</option>
              <option v-for="category in categories" :key="category.id" :value="category.id">{{ category.name }}</option>
            </select>
            <select 
              v-model="productSort"
              @change="sortProducts"
              class="bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white"
            >
              <option value="created_at">По дате создания</option>
              <option value="name">По названию</option>
              <option value="price">По цене</option>
            </select>
          </div>

          <!-- Список товаров -->
          <div class="space-y-4">
            <div 
              v-for="product in products.data" 
              :key="product.id"
              class="bg-gray-700 rounded-lg p-4 flex items-center justify-between"
            >
              <div class="flex items-center space-x-4">
                <img 
                  :src="getProductImage(product)" 
                  :alt="product.name"
                  class="w-16 h-16 object-cover rounded-lg bg-gray-600"
                  @error="handleImageError"
                >
                <div>
                  <h3 class="text-white font-medium">{{ product.name }}</h3>
                  <p class="text-gray-400 text-sm">{{ formatPrice(product.price) }} ₽</p>
                  <div class="flex items-center space-x-2 text-xs">
                    <span v-if="product.theme" class="bg-purple-600 text-white px-2 py-1 rounded">{{ product.theme.name }}</span>
                    <span v-if="product.use_matryoshka" class="bg-blue-600 text-white px-2 py-1 rounded">Матрёшка</span>
                    <span class="bg-gray-600 text-gray-300 px-2 py-1 rounded">{{ product.categories_count || 0 }} категорий</span>
                  </div>
                </div>
              </div>
              
              <div class="flex space-x-2">
                <router-link 
                  :to="`/admin/products/edit/${product.id}`"
                  class="bg-blue-600 hover:bg-blue-700 text-white px-3 py-1 rounded text-sm transition-colors"
                >
                  Изменить
                </router-link>
                <button 
                  @click="deleteProduct(product.id)"
                  class="bg-red-600 hover:bg-red-700 text-white px-3 py-1 rounded text-sm transition-colors"
                >
                  Удалить
                </button>
              </div>
            </div>
          </div>

          <!-- Пагинация -->
          <div v-if="products.last_page > 1" class="flex justify-center items-center space-x-2 mt-6">
            <button 
              v-for="page in visiblePages" 
              :key="page"
              @click="loadProducts(page)"
              class="px-3 py-2 rounded text-sm transition-colors"
              :class="page === products.current_page ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300 hover:bg-gray-600'"
            >
              {{ page }}
            </button>
          </div>
        </div>

        <!-- Изображения -->
        <div v-else-if="activeTab === 'images'">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-white">Управление изображениями</h2>
            <div class="flex space-x-2">
              <input 
                ref="imageInput"
                type="file" 
                multiple 
                accept="image/*"
                @change="uploadImages"
                class="hidden"
              >
              <button 
                @click="$refs.imageInput.click()"
                class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors"
              >
                + Загрузить изображения
              </button>
              <button 
                @click="loadImages"
                class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
              >
                Обновить
              </button>
            </div>
          </div>

          <!-- Галерея изображений -->
          <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-4">
            <div 
              v-for="image in images" 
              :key="image.filename"
              class="relative group bg-gray-700 rounded-lg overflow-hidden"
            >
              <img 
                :src="getImageUrl(image.filename)" 
                :alt="image.filename"
                class="w-full h-32 object-cover"
                @error="handleImageError"
              >
              <div class="absolute inset-0 bg-black bg-opacity-50 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center">
                <div class="flex space-x-2">
                  <button 
                    @click="copyImageUrl(image.filename)"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs"
                  >
                    Копировать URL
                  </button>
                  <button 
                    @click="deleteImage(image.filename)"
                    class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-xs"
                  >
                    Удалить
                  </button>
                </div>
              </div>
              <div class="p-2">
                <p class="text-white text-xs truncate">{{ image.filename }}</p>
                <p class="text-gray-400 text-xs">{{ formatFileSize(image.size) }}</p>
              </div>
            </div>
          </div>
        </div>

        <!-- Категории -->
        <div v-else-if="activeTab === 'categories'">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-white">Управление категориями</h2>
            <button 
              @click="createCategory"
              class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors"
            >
              + Создать категорию
            </button>
          </div>

          <!-- Дерево категорий -->
          <div class="space-y-2">
            <div 
              v-for="category in categoryTree" 
              :key="category.id"
              class="bg-gray-700 rounded-lg p-4"
            >
              <div class="flex items-center justify-between">
                <div class="flex items-center space-x-3">
                  <span class="text-white font-medium">{{ category.name }}</span>
                  <span class="bg-blue-600 text-white px-2 py-1 rounded text-xs">
                    {{ category.products_count || 0 }} товаров
                  </span>
                  <span v-if="category.children && category.children.length" 
                        class="bg-purple-600 text-white px-2 py-1 rounded text-xs">
                    {{ category.children.length }} подкатегорий
                  </span>
                </div>
                
                <div class="flex space-x-2">
                  <button 
                    @click="createSubcategory(category.id)"
                    class="bg-green-600 hover:bg-green-700 text-white px-2 py-1 rounded text-xs transition-colors"
                  >
                    + Подкатегория
                  </button>
                  <button 
                    @click="editCategory(category)"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs transition-colors"
                  >
                    Изменить
                  </button>
                  <button 
                    @click="deleteCategory(category.id)"
                    class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-xs transition-colors"
                  >
                    Удалить
                  </button>
                </div>
              </div>

              <!-- Подкатегории -->
              <div v-if="category.children && category.children.length" class="ml-6 mt-3 space-y-2">
                <div 
                  v-for="child in category.children" 
                  :key="child.id"
                  class="bg-gray-600 rounded p-3 flex items-center justify-between"
                >
                  <div class="flex items-center space-x-3">
                    <span class="text-gray-300">→</span>
                    <span class="text-white">{{ child.name }}</span>
                    <span class="bg-gray-500 text-white px-2 py-1 rounded text-xs">
                      {{ child.products_count || 0 }} товаров
                    </span>
                  </div>
                  
                  <div class="flex space-x-2">
                    <button 
                      @click="editCategory(child)"
                      class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs transition-colors"
                    >
                      Изменить
                    </button>
                    <button 
                      @click="deleteCategory(child.id)"
                      class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-xs transition-colors"
                    >
                      Удалить
                    </button>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Темы -->
        <div v-else-if="activeTab === 'themes'">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-white">Управление темами</h2>
            <button 
              @click="createTheme"
              class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors"
            >
              + Создать тему
            </button>
          </div>

          <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div 
              v-for="theme in themes" 
              :key="theme.id"
              class="bg-gray-700 rounded-lg p-4"
            >
              <div class="flex items-center justify-between">
                <div>
                  <h3 class="text-white font-medium">{{ theme.name }}</h3>
                  <p class="text-gray-400 text-sm">{{ theme.products_count || 0 }} товаров</p>
                </div>
                
                <div class="flex space-x-2">
                  <button 
                    @click="editTheme(theme)"
                    class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs transition-colors"
                  >
                    Изменить
                  </button>
                  <button 
                    @click="deleteTheme(theme.id)"
                    class="bg-red-600 hover:bg-red-700 text-white px-2 py-1 rounded text-xs transition-colors"
                  >
                    Удалить
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>

        <!-- Атрибуты -->
        <div v-else-if="activeTab === 'attributes'">
          <div class="flex justify-between items-center mb-6">
            <h2 class="text-xl font-semibold text-white">Управление атрибутами</h2>
            <button 
              @click="createAttribute"
              class="bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors"
            >
              + Создать атрибут
            </button>
          </div>

          <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
            <!-- Список атрибутов -->
            <div class="space-y-3">
              <h3 class="text-lg font-medium text-white mb-4">Атрибуты</h3>
              <div 
                v-for="attribute in attributes" 
                :key="attribute.id"
                class="bg-gray-700 rounded-lg p-4 cursor-pointer transition-colors"
                :class="selectedAttribute?.id === attribute.id ? 'ring-2 ring-blue-500' : 'hover:bg-gray-600'"
                @click="selectAttribute(attribute)"
              >
                <div class="flex items-center justify-between">
                  <div class="flex items-center space-x-3">
                    <span class="text-white font-medium">{{ attribute.name }}</span>
                    <span v-if="attribute.is_stone" 
                          class="bg-purple-600 text-white px-2 py-1 rounded text-xs">
                      Камень
                    </span>
                  </div>
                  
                  <div class="flex space-x-2">
                    <span class="bg-gray-600 text-gray-300 px-2 py-1 rounded text-xs">
                      {{ attribute.values_count || 0 }} значений
                    </span>
                    <button 
                      @click.stop="editAttribute(attribute)"
                      class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs transition-colors"
                    >
                      Изменить
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

            <!-- Значения атрибута -->
            <div v-if="selectedAttribute">
              <div class="flex justify-between items-center mb-4">
                <h3 class="text-lg font-medium text-white">
                  Значения "{{ selectedAttribute.name }}"
                </h3>
                <button 
                  @click="createAttributeValue"
                  class="bg-green-600 hover:bg-green-700 text-white px-3 py-1 rounded text-sm transition-colors"
                >
                  + Добавить
                </button>
              </div>

              <div v-if="!attributeValues.length" class="text-center py-8 text-gray-400">
                Нет значений для данного атрибута
              </div>

              <div v-else class="space-y-2">
                <div 
                  v-for="value in attributeValues" 
                  :key="value.id"
                  class="bg-gray-600 rounded p-3 flex items-center justify-between"
                >
                  <div class="flex items-center space-x-3">
                    <span class="text-white">{{ value.value }}</span>
                    <span class="bg-gray-500 text-gray-300 px-2 py-1 rounded text-xs">
                      {{ value.products_count || 0 }} товаров
                    </span>
                    <span v-if="!value.is_active" 
                          class="bg-red-600 text-white px-2 py-1 rounded text-xs">
                      Неактивно
                    </span>
                  </div>
                  
                  <div class="flex space-x-2">
                    <button 
                      @click="editAttributeValue(value)"
                      class="bg-blue-600 hover:bg-blue-700 text-white px-2 py-1 rounded text-xs transition-colors"
                    >
                      Изменить
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
          </div>
        </div>

        <!-- Квиз -->
        <div v-else-if="activeTab === 'quiz'">
          <h2 class="text-xl font-semibold text-white mb-6">Управление квизом</h2>
          <div class="bg-gray-700 rounded-lg p-6">
            <p class="text-gray-300 mb-4">Настройки алгоритма подбора украшений</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
              <div>
                <label class="block text-sm font-medium text-white mb-2">Приоритет камней (%)</label>
                <input 
                  v-model.number="quizSettings.stone_priority"
                  type="range" 
                  min="0" 
                  max="100" 
                  class="w-full"
                >
                <span class="text-gray-400 text-sm">{{ quizSettings.stone_priority }}%</span>
              </div>
              <div>
                <label class="block text-sm font-medium text-white mb-2">Приоритет темы (%)</label>
                <input 
                  v-model.number="quizSettings.theme_priority"
                  type="range" 
                  min="0" 
                  max="100" 
                  class="w-full"
                >
                <span class="text-gray-400 text-sm">{{ quizSettings.theme_priority }}%</span>
              </div>
            </div>
            <button 
              @click="saveQuizSettings"
              class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
            >
              Сохранить настройки
            </button>
          </div>
        </div>

        <!-- Маркетплейсы -->
        <div v-else-if="activeTab === 'marketplace'">
          <h2 class="text-xl font-semibold text-white mb-6">Интеграция с маркетплейсами</h2>
          <div class="space-y-4">
            <div class="bg-gray-700 rounded-lg p-6">
              <h3 class="text-lg font-medium text-white mb-4">Wildberries</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-white mb-2">API Token</label>
                  <input 
                    v-model="marketplaceSettings.wb_token"
                    type="password" 
                    class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-white mb-2">Статус</label>
                  <span class="text-green-400">Подключено</span>
                </div>
              </div>
              <button 
                @click="testMarketplaceConnection('wb')"
                class="mt-4 bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
              >
                Тестировать подключение
              </button>
            </div>
          </div>
        </div>

        <!-- Настройки -->
        <div v-else-if="activeTab === 'settings'">
          <h2 class="text-xl font-semibold text-white mb-6">Настройки системы</h2>
          <div class="space-y-6">
            <!-- Общие настройки -->
            <div class="bg-gray-700 rounded-lg p-6">
              <h3 class="text-lg font-medium text-white mb-4">Общие настройки</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-white mb-2">Название магазина</label>
                  <input 
                    v-model="settings.shop_name"
                    type="text" 
                    class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-white mb-2">Email для уведомлений</label>
                  <input 
                    v-model="settings.notification_email"
                    type="email" 
                    class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white"
                  >
                </div>
              </div>
            </div>

            <!-- Telegram настройки -->
            <div class="bg-gray-700 rounded-lg p-6">
              <h3 class="text-lg font-medium text-white mb-4">Telegram бот</h3>
              <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                  <label class="block text-sm font-medium text-white mb-2">Bot Token</label>
                  <input 
                    v-model="settings.telegram_token"
                    type="password" 
                    class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white"
                  >
                </div>
                <div>
                  <label class="block text-sm font-medium text-white mb-2">Username бота</label>
                  <input 
                    v-model="settings.telegram_username"
                    type="text" 
                    class="w-full bg-gray-800 border border-gray-600 rounded-lg px-4 py-2 text-white"
                  >
                </div>
              </div>
            </div>

            <button 
              @click="saveSettings"
              class="bg-green-600 hover:bg-green-700 text-white px-6 py-3 rounded-lg transition-colors"
              :disabled="saving"
            >
              {{ saving ? 'Сохранение...' : 'Сохранить настройки' }}
            </button>
          </div>
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
              <label class="block text-sm font-medium text-white mb-2">Название темы</label>
              <input
                v-model="themeForm.name"
                type="text"
                required
                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white"
                placeholder="Например: Минимализм, Готика, Винтаж"
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
              <label class="block text-sm font-medium text-white mb-2">Название атрибута</label>
              <input
                v-model="attributeForm.name"
                type="text"
                required
                class="w-full bg-gray-700 border border-gray-600 rounded-lg px-4 py-2 text-white"
                placeholder="Например: Материал, Камень, Размер"
              >
            </div>
            
            <div class="flex items-center">
              <input
                v-model="attributeForm.is_stone"
                type="checkbox"
                id="is_stone"
                class="mr-2"
              >
              <label for="is_stone" class="text-white">Это атрибут камня (для квиза)</label>
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
                placeholder="Например: Серебро, Агат, Большой"
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

export default {
  name: 'AdminPanel',
  setup() {
    const router = useRouter();
    
    // Состояние приложения
    const activeTab = ref('products');
    const loading = ref(false);
    const saving = ref(false);
    
    // Данные
    const products = ref({ data: [], current_page: 1, last_page: 1 });
    const categories = ref([]);
    const categoryTree = ref([]);
    const themes = ref([]);
    const attributes = ref([]);
    const attributeValues = ref([]);
    const selectedAttribute = ref(null);
    const images = ref([]);
    const stats = ref(null);
    
    // Фильтры и поиск
    const productSearch = ref('');
    const productThemeFilter = ref('');
    const productCategoryFilter = ref('');
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
      name: '',
      is_stone: false
    });
    
    const attributeValueForm = reactive({
      id: null,
      attribute_id: null,
      value: '',
      sort_order: 0,
      is_active: true
    });
    
    // Настройки
    const quizSettings = reactive({
      stone_priority: 70,
      theme_priority: 30
    });
    
    const marketplaceSettings = reactive({
      wb_token: ''
    });
    
    const settings = reactive({
      shop_name: 'Стужа',
      notification_email: '',
      telegram_token: '',
      telegram_username: ''
    });
    
    // Навигационные табы
    const tabs = [
      { id: 'products', name: 'Товары' },
      { id: 'images', name: 'Изображения' },
      { id: 'categories', name: 'Категории' },
      { id: 'themes', name: 'Темы' },
      { id: 'attributes', name: 'Атрибуты' },
      { id: 'quiz', name: 'Квиз' },
      { id: 'marketplace', name: 'Маркетплейсы' },
      { id: 'settings', name: 'Настройки' }
    ];
    
    // Вычисляемые свойства
    const visiblePages = computed(() => {
      const current = products.value.current_page;
      const last = products.value.last_page;
      const pages = [];
      
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
          page,
          search: productSearch.value,
          theme_id: productThemeFilter.value,
          category_id: productCategoryFilter.value,
          sort_by: productSort.value
        };
        
        const response = await axios.get('/admin/products', { params });
        products.value = response.data;
      } catch (error) {
        console.error('Ошибка загрузки товаров:', error);
        alert('Ошибка загрузки товаров');
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
    
    const loadCategoryTree = async () => {
      try {
        const response = await axios.get('/admin/categories/tree');
        categoryTree.value = response.data;
      } catch (error) {
        console.error('Ошибка загрузки дерева категорий:', error);
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
        attributeValues.value = response.data;
      } catch (error) {
        console.error('Ошибка загрузки значений атрибута:', error);
      }
    };
    
    const loadImages = async () => {
      try {
        const response = await axios.get('/admin/images');
        images.value = response.data;
      } catch (error) {
        console.error('Ошибка загрузки изображений:', error);
      }
    };
    
    // Методы работы с товарами
    const searchProducts = () => {
      loadProducts(1);
    };
    
    const filterProducts = () => {
      loadProducts(1);
    };
    
    const sortProducts = () => {
      loadProducts(1);
    };
    
    const deleteProduct = async (id) => {
      if (!confirm('Удалить этот товар?')) return;
      
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
    
    // Методы работы с изображениями
    const uploadImages = async (event) => {
      const files = event.target.files;
      if (!files.length) return;
      
      const formData = new FormData();
      for (let file of files) {
        formData.append('images[]', file);
      }
      
      try {
        loading.value = true;
        await axios.post('/admin/images/upload', formData, {
          headers: { 'Content-Type': 'multipart/form-data' }
        });
        alert('Изображения загружены!');
        loadImages();
      } catch (error) {
        console.error('Ошибка загрузки изображений:', error);
        alert('Ошибка загрузки изображений');
      } finally {
        loading.value = false;
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
    
    const copyImageUrl = (filename) => {
      const url = getImageUrl(filename);
      navigator.clipboard.writeText(url);
      alert('URL скопирован в буфер обмена!');
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
    
    const createSubcategory = (parentId) => {
      Object.assign(categoryForm, {
        id: null,
        name: '',
        parent_id: parentId,
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
      try {
        saving.value = true;
        
        if (categoryForm.id) {
          await axios.put(`/admin/categories/${categoryForm.id}`, categoryForm);
          alert('Категория обновлена!');
        } else {
          await axios.post('/admin/categories', categoryForm);
          alert('Категория создана!');
        }
        
        showCategoryModal.value = false;
        loadCategories();
        loadCategoryTree();
        loadStats();
      } catch (error) {
        console.error('Ошибка сохранения категории:', error);
        alert('Ошибка сохранения категории');
      } finally {
        saving.value = false;
      }
    };
    
    const deleteCategory = async (id) => {
      if (!confirm('Удалить эту категорию? Все связанные товары потеряют связь с категорией.')) return;
      
      try {
        await axios.delete(`/admin/categories/${id}`);
        alert('Категория удалена!');
        loadCategories();
        loadCategoryTree();
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
      if (!confirm('Удалить эту тему? Все связанные товары потеряют тему.')) return;
      
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
        name: '',
        is_stone: false
      });
      showAttributeModal.value = true;
    };
    
    const editAttribute = (attribute) => {
      Object.assign(attributeForm, {
        id: attribute.id,
        name: attribute.name,
        is_stone: attribute.is_stone || false
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
          await axios.post('/admin/attribute-values', attributeValueForm);
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
    
    // Прочие методы
    const saveQuizSettings = async () => {
      try {
        saving.value = true;
        await axios.post('/admin/quiz/settings', quizSettings);
        alert('Настройки квиза сохранены!');
      } catch (error) {
        console.error('Ошибка сохранения настроек квиза:', error);
        alert('Ошибка сохранения настроек');
      } finally {
        saving.value = false;
      }
    };
    
    const testMarketplaceConnection = async (platform) => {
      try {
        const response = await axios.post(`/admin/marketplace/test/${platform}`);
        alert(`Подключение к ${platform}: ${response.data.status}`);
      } catch (error) {
        alert('Ошибка подключения к маркетплейсу');
      }
    };
    
    const saveSettings = async () => {
      try {
        saving.value = true;
        await axios.post('/admin/settings', settings);
        alert('Настройки сохранены!');
      } catch (error) {
        console.error('Ошибка сохранения настроек:', error);
        alert('Ошибка сохранения настроек');
      } finally {
        saving.value = false;
      }
    };
    
    const logout = async () => {
      try {
        await axios.post('/admin/logout');
        localStorage.removeItem('auth_token');
        delete axios.defaults.headers.common['Authorization'];
        router.push('/admin/login');
      } catch (error) {
        console.error('Ошибка выхода:', error);
        // Все равно выходим
        localStorage.removeItem('auth_token');
        delete axios.defaults.headers.common['Authorization'];
        router.push('/admin/login');
      }
    };
    
    // Вспомогательные методы
    const formatPrice = (price) => {
      return new Intl.NumberFormat('ru-RU').format(price);
    };
    
    const formatFileSize = (bytes) => {
      const sizes = ['Bytes', 'KB', 'MB', 'GB'];
      if (bytes === 0) return '0 Byte';
      const i = Math.floor(Math.log(bytes) / Math.log(1024));
      return Math.round(bytes / Math.pow(1024, i) * 100) / 100 + ' ' + sizes[i];
    };
    
    const getProductImage = (product) => {
      if (product.use_matryoshka && product.image_layers?.outer) {
        return `/storage/images/${product.image_layers.outer}`;
      }
      if (product.gallery_images?.length > 0) {
        return `/storage/images/${product.gallery_images[0]}`;
      }
      return '/images/placeholder.jpg';
    };
    
    const getImageUrl = (filename) => {
      return `/storage/images/${filename}`;
    };
    
    const handleImageError = (event) => {
      event.target.src = '/images/placeholder.jpg';
    };
    
    // Инициализация
    onMounted(async () => {
      try {
        await Promise.all([
          loadStats(),
          loadProducts(),
          loadCategories(),
          loadCategoryTree(),
          loadThemes(),
          loadAttributes(),
          loadImages()
        ]);
      } catch (error) {
        console.error('Ошибка инициализации админ-панели:', error);
      }
    });
    
    return {
      // Состояние
      activeTab,
      loading,
      saving,
      
      // Данные
      products,
      categories,
      categoryTree,
      themes,
      attributes,
      attributeValues,
      selectedAttribute,
      images,
      stats,
      
      // Фильтры
      productSearch,
      productThemeFilter,
      productCategoryFilter,
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
      
      // Настройки
      quizSettings,
      marketplaceSettings,
      settings,
      
      // Константы
      tabs,
      
      // Вычисляемые
      visiblePages,
      
      // Методы
      loadProducts,
      searchProducts,
      filterProducts,
      sortProducts,
      deleteProduct,
      
      uploadImages,
      deleteImage,
      copyImageUrl,
      
      createCategory,
      createSubcategory,
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
      
      saveQuizSettings,
      testMarketplaceConnection,
      saveSettings,
      logout,
      
      // Вспомогательные
      formatPrice,
      formatFileSize,
      getProductImage,
      getImageUrl,
      handleImageError
    };
  }
};
</script>

<style scoped>
/* Дополнительные стили для админ-панели */
.admin-panel {
  font-family: 'Inter', sans-serif;
}

/* Стили для кастомных элементов */
input[type="range"] {
  -webkit-appearance: none;
  appearance: none;
  height: 6px;
  background: #374151;
  border-radius: 3px;
  outline: none;
}

input[type="range"]::-webkit-slider-thumb {
  -webkit-appearance: none;
  appearance: none;
  width: 20px;
  height: 20px;
  background: #3B82F6;
  border-radius: 50%;
  cursor: pointer;
}

input[type="range"]::-moz-range-thumb {
  width: 20px;
  height: 20px;
  background: #3B82F6;
  border-radius: 50%;
  cursor: pointer;
  border: none;
}

/* Анимации */
.transition-colors {
  transition: background-color 0.2s ease, color 0.2s ease;
}

/* Улучшенная прокрутка */
::-webkit-scrollbar {
  width: 8px;
}

::-webkit-scrollbar-track {
  background: #1F2937;
}

::-webkit-scrollbar-thumb {
  background: #374151;
  border-radius: 4px;
}

::-webkit-scrollbar-thumb:hover {
  background: #4B5563;
}
</style>