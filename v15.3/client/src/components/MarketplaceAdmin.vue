<template>
  <div class="marketplace-admin p-6">
    <div class="bg-gray-800 rounded-lg p-6">
      <h2 class="text-2xl font-bold text-white mb-6">Управление маркетплейсами</h2>
      
      <!-- Выбор маркетплейса -->
      <div class="mb-6">
        <label class="block text-white mb-2">Выберите маркетплейс</label>
        <select 
          v-model="selectedMarketplace" 
          @change="loadMarketplaceData"
          class="w-full bg-gray-700 text-white px-4 py-2 rounded-lg"
        >
          <option value="">-- Выберите маркетплейс --</option>
          <option value="wildberries">Wildberries</option>
          <option value="ozon">Ozon</option>
          <option value="yandex_market">Яндекс.Маркет</option>
          <option value="flowwow">Flowwow</option>
        </select>
      </div>

      <!-- Табы -->
      <div v-if="selectedMarketplace" class="mb-6">
        <div class="flex gap-4 mb-4">
          <button 
            @click="activeTab = 'categories'"
            :class="['px-4 py-2 rounded', activeTab === 'categories' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300']"
          >
            Категории
          </button>
          <button 
            @click="activeTab = 'attributes'"
            :class="['px-4 py-2 rounded', activeTab === 'attributes' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300']"
          >
            Атрибуты
          </button>
          <button 
            @click="activeTab = 'settings'"
            :class="['px-4 py-2 rounded', activeTab === 'settings' ? 'bg-blue-600 text-white' : 'bg-gray-700 text-gray-300']"
          >
            Настройки
          </button>
        </div>
      </div>

      <!-- Контент табов -->
      <div v-if="selectedMarketplace">
        <!-- Маппинг категорий -->
        <div v-if="activeTab === 'categories'">
          <div class="grid grid-cols-2 gap-6">
            <!-- Наши категории -->
            <div>
              <h3 class="text-lg font-semibold text-white mb-3">Наши категории</h3>
              <div class="bg-gray-700 rounded-lg p-4 max-h-96 overflow-y-auto">
                <div 
                  v-for="category in ourCategories" 
                  :key="category.id"
                  class="mb-2 p-2 bg-gray-600 rounded cursor-pointer hover:bg-gray-500"
                  :class="{ 'ring-2 ring-blue-500': selectedOurCategory === category.id }"
                  @click="selectOurCategory(category)"
                >
                  {{ category.name }}
                  <span v-if="getCategoryMapping(category.id)" class="text-green-400 text-sm ml-2">
                    ✓ Связано
                  </span>
                </div>
              </div>
            </div>

            <!-- Категории маркетплейса -->
            <div>
              <h3 class="text-lg font-semibold text-white mb-3">
                Категории {{ getMarketplaceName(selectedMarketplace) }}
              </h3>
              <button 
                @click="loadMarketplaceCategories" 
                :disabled="loadingCategories"
                class="mb-3 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
              >
                {{ loadingCategories ? 'Загрузка...' : 'Загрузить категории' }}
              </button>
              
              <div class="bg-gray-700 rounded-lg p-4 max-h-96 overflow-y-auto">
                <div v-if="marketplaceCategories.length === 0" class="text-gray-400">
                  Нажмите "Загрузить категории" для получения списка
                </div>
                <div 
                  v-for="category in marketplaceCategories" 
                  :key="category.id"
                  class="mb-2 p-2 bg-gray-600 rounded cursor-pointer hover:bg-gray-500"
                  :class="{ 'ring-2 ring-blue-500': selectedMarketplaceCategory === category.id }"
                  @click="selectMarketplaceCategory(category)"
                >
                  {{ category.name }}
                </div>
              </div>
            </div>
          </div>

          <!-- Кнопка связывания -->
          <div v-if="selectedOurCategory && selectedMarketplaceCategory" class="mt-4 text-center">
            <button 
              @click="createCategoryMapping"
              class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700"
            >
              Связать выбранные категории
            </button>
          </div>

          <!-- Список существующих маппингов -->
          <div class="mt-6">
            <h3 class="text-lg font-semibold text-white mb-3">Существующие связи</h3>
            <div class="bg-gray-700 rounded-lg p-4">
              <div v-if="categoryMappings.length === 0" class="text-gray-400">
                Нет связанных категорий
              </div>
              <div 
                v-for="mapping in categoryMappings" 
                :key="mapping.id"
                class="flex justify-between items-center mb-2 p-2 bg-gray-600 rounded"
              >
                <div>
                  <span class="text-white">{{ mapping.ourCategory?.name }}</span>
                  <span class="text-gray-400 mx-2">→</span>
                  <span class="text-white">{{ mapping.marketplace_name }}</span>
                </div>
                <button 
                  @click="deleteMapping(mapping.id)"
                  class="text-red-400 hover:text-red-300"
                >
                  Удалить
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Маппинг атрибутов -->
        <div v-if="activeTab === 'attributes'">
          <div v-if="!selectedCategoryForAttributes" class="mb-4">
            <label class="block text-white mb-2">Выберите категорию для настройки атрибутов</label>
            <select 
              v-model="selectedCategoryForAttributes" 
              @change="loadAttributesForCategory"
              class="w-full bg-gray-700 text-white px-4 py-2 rounded-lg"
            >
              <option value="">-- Выберите категорию --</option>
              <option 
                v-for="category in ourCategories" 
                :key="category.id" 
                :value="category.id"
              >
                {{ category.name }}
              </option>
            </select>
          </div>

          <div v-else class="grid grid-cols-2 gap-6">
            <!-- Наши атрибуты -->
            <div>
              <h3 class="text-lg font-semibold text-white mb-3">Наши атрибуты</h3>
              <div class="bg-gray-700 rounded-lg p-4 max-h-96 overflow-y-auto">
                <div 
                  v-for="attribute in ourAttributes" 
                  :key="attribute.id"
                  class="mb-2 p-2 bg-gray-600 rounded cursor-pointer hover:bg-gray-500"
                  :class="{ 'ring-2 ring-blue-500': selectedOurAttribute === attribute.id }"
                  @click="selectOurAttribute(attribute)"
                >
                  {{ attribute.name }}
                  <span v-if="getAttributeMapping(attribute.id)" class="text-green-400 text-sm ml-2">
                    ✓ Связано
                  </span>
                </div>
              </div>
            </div>

            <!-- Атрибуты маркетплейса -->
            <div>
              <h3 class="text-lg font-semibold text-white mb-3">
                Атрибуты {{ getMarketplaceName(selectedMarketplace) }}
              </h3>
              <button 
                @click="loadMarketplaceAttributes" 
                :disabled="loadingAttributes"
                class="mb-3 bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
              >
                {{ loadingAttributes ? 'Загрузка...' : 'Загрузить атрибуты' }}
              </button>
              
              <div class="bg-gray-700 rounded-lg p-4 max-h-96 overflow-y-auto">
                <div v-if="marketplaceAttributes.length === 0" class="text-gray-400">
                  Нажмите "Загрузить атрибуты" для получения списка
                </div>
                <div 
                  v-for="attribute in marketplaceAttributes" 
                  :key="attribute.id"
                  class="mb-2 p-2 bg-gray-600 rounded cursor-pointer hover:bg-gray-500"
                  :class="{ 'ring-2 ring-blue-500': selectedMarketplaceAttribute === attribute.id }"
                  @click="selectMarketplaceAttribute(attribute)"
                >
                  {{ attribute.name }}
                  <span v-if="attribute.required" class="text-red-400 text-sm ml-2">*</span>
                </div>
              </div>
            </div>
          </div>

          <!-- Кнопка связывания -->
          <div v-if="selectedOurAttribute && selectedMarketplaceAttribute" class="mt-4 text-center">
            <button 
              @click="createAttributeMapping"
              class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700"
            >
              Связать выбранные атрибуты
            </button>
          </div>

          <!-- Список существующих маппингов -->
          <div class="mt-6">
            <h3 class="text-lg font-semibold text-white mb-3">Существующие связи атрибутов</h3>
            <div class="bg-gray-700 rounded-lg p-4">
              <div v-if="attributeMappings.length === 0" class="text-gray-400">
                Нет связанных атрибутов
              </div>
              <div 
                v-for="mapping in attributeMappings" 
                :key="mapping.id"
                class="flex justify-between items-center mb-2 p-2 bg-gray-600 rounded"
              >
                <div>
                  <span class="text-white">{{ mapping.ourAttribute?.name }}</span>
                  <span class="text-gray-400 mx-2">→</span>
                  <span class="text-white">{{ mapping.marketplace_name }}</span>
                </div>
                <button 
                  @click="deleteMapping(mapping.id)"
                  class="text-red-400 hover:text-red-300"
                >
                  Удалить
                </button>
              </div>
            </div>
          </div>
        </div>

        <!-- Настройки подключения -->
        <div v-if="activeTab === 'settings'">
          <div class="bg-gray-700 rounded-lg p-4">
            <h3 class="text-lg font-semibold text-white mb-4">
              Настройки {{ getMarketplaceName(selectedMarketplace) }}
            </h3>
            
            <div class="mb-4">
              <button 
                @click="testConnection" 
                :disabled="testingConnection"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
              >
                {{ testingConnection ? 'Проверка...' : 'Проверить подключение' }}
              </button>
              
              <div v-if="connectionStatus" class="mt-2">
                <span 
                  :class="connectionStatus.success ? 'text-green-400' : 'text-red-400'"
                >
                  {{ connectionStatus.message }}
                </span>
              </div>
            </div>

            <div class="text-gray-400">
              <p>API ключи настраиваются в файле .env</p>
              <p class="mt-2">Необходимые переменные:</p>
              <ul class="list-disc list-inside mt-1">
                <li v-if="selectedMarketplace === 'wildberries'">WILDBERRIES_API_KEY</li>
                <li v-if="selectedMarketplace === 'ozon'">OZON_CLIENT_ID, OZON_API_KEY</li>
                <li v-if="selectedMarketplace === 'yandex_market'">YANDEX_MARKET_OAUTH_TOKEN, YANDEX_MARKET_CAMPAIGN_ID</li>
                <li v-if="selectedMarketplace === 'flowwow'">FLOWWOW_API_KEY</li>
              </ul>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import axios from 'axios'

// Состояние
const selectedMarketplace = ref('')
const activeTab = ref('categories')
const loadingCategories = ref(false)
const loadingAttributes = ref(false)
const testingConnection = ref(false)
const connectionStatus = ref(null)

// Категории
const ourCategories = ref([])
const marketplaceCategories = ref([])
const selectedOurCategory = ref(null)
const selectedMarketplaceCategory = ref(null)
const categoryMappings = ref([])

// Атрибуты
const ourAttributes = ref([])
const marketplaceAttributes = ref([])
const selectedOurAttribute = ref(null)
const selectedMarketplaceAttribute = ref(null)
const selectedCategoryForAttributes = ref(null)
const attributeMappings = ref([])

// Все маппинги
const allMappings = ref([])

// Методы
const getMarketplaceName = (id) => {
  const names = {
    'wildberries': 'Wildberries',
    'ozon': 'Ozon', 
    'yandex_market': 'Яндекс.Маркет',
    'flowwow': 'Flowwow'
  }
  return names[id] || id
}

const loadMarketplaceData = async () => {
  if (!selectedMarketplace.value) return
  
  // Загружаем маппинги для выбранного маркетплейса
  try {
    const response = await axios.get('/admin/marketplace-maps', {
      params: { marketplace: selectedMarketplace.value }
    })
    
    const data = response.data.data[selectedMarketplace.value] || []
    
    // Разделяем на категории и атрибуты
    categoryMappings.value = data.filter(m => m.mapping_type === 'category')
    attributeMappings.value = data.filter(m => m.mapping_type === 'attribute')
    
    // Добавляем связанные сущности
    categoryMappings.value.forEach(m => {
      m.ourCategory = ourCategories.value.find(c => c.id === m.our_id)
    })
    
    attributeMappings.value.forEach(m => {
      m.ourAttribute = ourAttributes.value.find(a => a.id === m.our_id)
    })
    
  } catch (error) {
    console.error('Ошибка загрузки маппингов:', error)
  }
}

const loadOurData = async () => {
  try {
    // Загружаем наши категории
    const catResponse = await axios.get('/admin/categories')
    ourCategories.value = catResponse.data
    
    // Загружаем наши атрибуты
    const attrResponse = await axios.get('/admin/attributes')
    ourAttributes.value = attrResponse.data
    
  } catch (error) {
    console.error('Ошибка загрузки данных:', error)
  }
}

const loadMarketplaceCategories = async () => {
  if (!selectedMarketplace.value) return
  
  loadingCategories.value = true
  try {
    const response = await axios.get(`/admin/marketplace-maps/load-categories/${selectedMarketplace.value}`)
    marketplaceCategories.value = response.data.data
  } catch (error) {
    console.error('Ошибка загрузки категорий:', error)
    alert('Ошибка загрузки категорий')
  } finally {
    loadingCategories.value = false
  }
}

const loadMarketplaceAttributes = async () => {
  if (!selectedMarketplace.value) return
  
  loadingAttributes.value = true
  try {
    const categoryId = getCategoryMapping(selectedCategoryForAttributes.value)?.marketplace_id
    const response = await axios.get(`/admin/marketplace-maps/load-attributes/${selectedMarketplace.value}/${categoryId || ''}`)
    marketplaceAttributes.value = response.data.data
  } catch (error) {
    console.error('Ошибка загрузки атрибутов:', error)
    alert('Ошибка загрузки атрибутов')
  } finally {
    loadingAttributes.value = false
  }
}

const selectOurCategory = (category) => {
  selectedOurCategory.value = category.id
}

const selectMarketplaceCategory = (category) => {
  selectedMarketplaceCategory.value = category.id
  selectedMarketplaceCategoryName.value = category.name
}

const selectOurAttribute = (attribute) => {
  selectedOurAttribute.value = attribute.id
}

const selectMarketplaceAttribute = (attribute) => {
  selectedMarketplaceAttribute.value = attribute.id
  selectedMarketplaceAttributeName.value = attribute.name
}

const selectedMarketplaceCategoryName = ref('')
const selectedMarketplaceAttributeName = ref('')

const createCategoryMapping = async () => {
  try {
    await axios.post('/admin/marketplace-maps', {
      marketplace: selectedMarketplace.value,
      mapping_type: 'category',
      our_id: selectedOurCategory.value,
      marketplace_id: selectedMarketplaceCategory.value,
      marketplace_name: selectedMarketplaceCategoryName.value
    })
    
    alert('Категории успешно связаны!')
    
    // Очищаем выбор
    selectedOurCategory.value = null
    selectedMarketplaceCategory.value = null
    
    // Перезагружаем маппинги
    await loadMarketplaceData()
    
  } catch (error) {
    console.error('Ошибка создания маппинга:', error)
    alert('Ошибка создания связи')
  }
}

const createAttributeMapping = async () => {
  try {
    await axios.post('/admin/marketplace-maps', {
      marketplace: selectedMarketplace.value,
      mapping_type: 'attribute',
      our_id: selectedOurAttribute.value,
      marketplace_id: selectedMarketplaceAttribute.value,
      marketplace_name: selectedMarketplaceAttributeName.value
    })
    
    alert('Атрибуты успешно связаны!')
    
    // Очищаем выбор
    selectedOurAttribute.value = null
    selectedMarketplaceAttribute.value = null
    
    // Перезагружаем маппинги
    await loadMarketplaceData()
    
  } catch (error) {
    console.error('Ошибка создания маппинга:', error)
    alert('Ошибка создания связи')
  }
}

const deleteMapping = async (id) => {
  if (!confirm('Удалить эту связь?')) return
  
  try {
    await axios.delete(`/admin/marketplace-maps/${id}`)
    alert('Связь удалена')
    await loadMarketplaceData()
  } catch (error) {
    console.error('Ошибка удаления:', error)
    alert('Ошибка удаления связи')
  }
}

const getCategoryMapping = (categoryId) => {
  return categoryMappings.value.find(m => m.our_id === categoryId)
}

const getAttributeMapping = (attributeId) => {
  return attributeMappings.value.find(m => m.our_id === attributeId)
}

const loadAttributesForCategory = () => {
  if (selectedCategoryForAttributes.value) {
    loadMarketplaceAttributes()
  }
}

const testConnection = async () => {
  testingConnection.value = true
  connectionStatus.value = null
  
  try {
    const response = await axios.post(`/admin/marketplace/test-connection/${selectedMarketplace.value}`)
    connectionStatus.value = {
      success: true,
      message: response.data.message
    }
  } catch (error) {
    connectionStatus.value = {
      success: false,
      message: error.response?.data?.message || 'Ошибка подключения'
    }
  } finally {
    testingConnection.value = false
  }
}

// При монтировании
onMounted(() => {
  loadOurData()
})
</script>