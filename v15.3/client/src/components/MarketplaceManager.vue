<!-- MarketplaceManager.vue - компонент управления маркетплейсами -->
<template>
  <div class="marketplace-manager">
    <!-- Список маркетплейсов -->
    <div class="mb-8">
      <h2 class="text-xl font-semibold text-white mb-4">Маркетплейсы</h2>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
        <div
          v-for="marketplace in marketplaces"
          :key="marketplace.id"
          class="bg-gray-800 rounded-lg p-4 border border-gray-700"
        >
          <div class="flex items-center justify-between mb-3">
            <h3 class="text-lg font-medium text-white">{{ marketplace.name }}</h3>
            <span
              :class="[
                'px-2 py-1 rounded text-xs font-medium',
                marketplace.configured
                  ? 'bg-green-600 text-green-100'
                  : 'bg-red-600 text-red-100'
              ]"
            >
              {{ marketplace.configured ? 'Настроен' : 'Не настроен' }}
            </span>
          </div>
          
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-400">Товаров синхронизировано:</span>
              <span class="text-white">{{ marketplace.products_count }}</span>
            </div>
            <div class="flex justify-between">
              <span class="text-gray-400">Последняя синхронизация:</span>
              <span class="text-white">{{ formatDate(marketplace.last_sync) }}</span>
            </div>
          </div>
          
          <div class="mt-4 space-y-2">
            <button
              @click="openMappingModal(marketplace.id)"
              class="w-full bg-blue-600 hover:bg-blue-700 text-white px-3 py-2 rounded text-sm transition-colors"
            >
              Настроить маппинг
            </button>
            <button
              @click="syncMarketplace(marketplace.id)"
              :disabled="!marketplace.configured || syncing"
              class="w-full bg-green-600 hover:bg-green-700 text-white px-3 py-2 rounded text-sm transition-colors disabled:opacity-50 disabled:cursor-not-allowed"
            >
              {{ syncing ? 'Синхронизация...' : 'Синхронизировать' }}
            </button>
          </div>
        </div>
      </div>
    </div>

    <!-- Статистика и логи -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
      <!-- Статистика -->
      <div class="bg-gray-800 rounded-lg p-6">
        <h3 class="text-lg font-medium text-white mb-4">Статистика синхронизации</h3>
        
        <div v-if="selectedMarketplace && stats" class="space-y-3">
          <div class="flex justify-between">
            <span class="text-gray-400">Всего товаров:</span>
            <span class="text-white">{{ stats.total_products }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-400">Синхронизировано:</span>
            <span class="text-white">{{ stats.synced_products }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-400">Ошибок синхронизации:</span>
            <span class="text-red-400">{{ stats.sync_errors }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-400">Ожидают обновления:</span>
            <span class="text-yellow-400">{{ stats.pending_updates }}</span>
          </div>
        </div>
        
        <div v-else class="text-gray-500 text-center py-8">
          Выберите маркетплейс для просмотра статистики
        </div>
      </div>

      <!-- Логи -->
      <div class="bg-gray-800 rounded-lg p-6">
        <h3 class="text-lg font-medium text-white mb-4">Последние действия</h3>
        
        <div v-if="logs.length > 0" class="space-y-3 max-h-64 overflow-y-auto">
          <div
            v-for="log in logs"
            :key="log.id"
            class="border-l-4 pl-3 py-2"
            :class="{
              'border-green-500': log.status === 'success',
              'border-red-500': log.status === 'error',
              'border-yellow-500': log.status === 'warning'
            }"
          >
            <div class="flex justify-between items-start">
              <div>
                <p class="text-white text-sm">{{ log.message }}</p>
                <p class="text-gray-500 text-xs">
                  {{ getMarketplaceName(log.marketplace) }} • {{ formatDate(log.created_at) }}
                </p>
              </div>
              <span
                class="text-xs px-2 py-1 rounded"
                :class="{
                  'bg-green-600 text-green-100': log.status === 'success',
                  'bg-red-600 text-red-100': log.status === 'error',
                  'bg-yellow-600 text-yellow-100': log.status === 'warning'
                }"
              >
                {{ log.status }}
              </span>
            </div>
          </div>
        </div>
        
        <div v-else class="text-gray-500 text-center py-8">
          Нет логов
        </div>
      </div>
    </div>

    <!-- Модальное окно маппинга атрибутов -->
    <div
      v-if="showMappingModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
    >
      <div class="bg-gray-800 rounded-lg p-6 max-w-4xl w-full max-h-[90vh] overflow-y-auto">
        <div class="flex justify-between items-center mb-4">
          <h3 class="text-xl font-semibold text-white">
            Маппинг атрибутов - {{ getMarketplaceName(currentMarketplace) }}
          </h3>
          <button
            @click="closeMappingModal"
            class="text-gray-400 hover:text-white"
          >
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
              <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path>
            </svg>
          </button>
        </div>

        <!-- Форма добавления маппинга -->
        <div class="bg-gray-700 rounded-lg p-4 mb-6">
          <h4 class="text-md font-medium text-white mb-3">Добавить маппинг</h4>
          
          <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
            <div>
              <label class="block text-sm font-medium text-white mb-2">
                Наш атрибут
              </label>
              <select
                v-model="newMapping.our_attr_id"
                class="w-full bg-gray-600 border border-gray-500 rounded-lg px-3 py-2 text-white"
              >
                <option value="">Выберите атрибут</option>
                <option
                  v-for="attr in ourAttributes"
                  :key="attr.id"
                  :value="attr.id"
                >
                  {{ attr.name }}
                </option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-white mb-2">
                Атрибут маркетплейса
              </label>
              <select
                v-model="newMapping.marketplace_attr_name"
                class="w-full bg-gray-600 border border-gray-500 rounded-lg px-3 py-2 text-white"
              >
                <option value="">Выберите атрибут</option>
                <option
                  v-for="attr in marketplaceAttributes"
                  :key="attr.id"
                  :value="attr.id"
                >
                  {{ attr.name }}
                </option>
              </select>
            </div>
            
            <div class="flex items-end">
              <button
                @click="saveMapping"
                :disabled="!newMapping.our_attr_id || !newMapping.marketplace_attr_name"
                class="w-full bg-green-600 hover:bg-green-700 text-white px-4 py-2 rounded-lg transition-colors disabled:opacity-50"
              >
                Добавить
              </button>
            </div>
          </div>
        </div>

        <!-- Список существующих маппингов -->
        <div>
          <h4 class="text-md font-medium text-white mb-3">Существующие маппинги</h4>
          
          <div v-if="mappings.length > 0" class="space-y-2">
            <div
              v-for="mapping in mappings"
              :key="mapping.id"
              class="bg-gray-700 rounded-lg p-3 flex justify-between items-center"
            >
              <div>
                <span class="text-white">{{ mapping.attribute?.name || 'Неизвестный атрибут' }}</span>
                <span class="text-gray-400 mx-2">→</span>
                <span class="text-white">{{ getMarketplaceAttributeName(mapping.marketplace_attr_name) }}</span>
              </div>
              
              <button
                @click="deleteMapping(mapping.id)"
                class="text-red-400 hover:text-red-300"
              >
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                  <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"></path>
                </svg>
              </button>
            </div>
          </div>
          
          <div v-else class="text-gray-500 text-center py-4">
            Нет настроенных маппингов
          </div>
        </div>
      </div>
    </div>

    <!-- Модальное окно результатов синхронизации -->
    <div
      v-if="showSyncResults"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50 p-4"
    >
      <div class="bg-gray-800 rounded-lg p-6 max-w-2xl w-full">
        <h3 class="text-xl font-semibold text-white mb-4">Результаты синхронизации</h3>
        
        <div class="space-y-3">
          <div class="flex justify-between">
            <span class="text-gray-400">Успешно синхронизировано:</span>
            <span class="text-green-400 font-semibold">{{ syncResults.success }}</span>
          </div>
          <div class="flex justify-between">
            <span class="text-gray-400">Ошибок:</span>
            <span class="text-red-400 font-semibold">{{ syncResults.failed }}</span>
          </div>
        </div>
        
        <div v-if="syncResults.errors && syncResults.errors.length > 0" class="mt-4">
          <h4 class="text-md font-medium text-white mb-2">Ошибки:</h4>
          <div class="bg-gray-700 rounded-lg p-3 max-h-48 overflow-y-auto">
            <div
              v-for="(error, index) in syncResults.errors"
              :key="index"
              class="text-sm mb-2"
            >
              <span class="text-red-400">{{ error.product_name }}:</span>
              <span class="text-gray-300 ml-2">{{ error.error }}</span>
            </div>
          </div>
        </div>
        
        <div class="mt-6 flex justify-end">
          <button
            @click="showSyncResults = false"
            class="bg-blue-600 hover:bg-blue-700 text-white px-4 py-2 rounded-lg transition-colors"
          >
            Закрыть
          </button>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { axios } from '@/main.js'

// Reactive данные
const marketplaces = ref([])
const selectedMarketplace = ref(null)
const stats = ref(null)
const logs = ref([])
const mappings = ref([])
const ourAttributes = ref([])
const marketplaceAttributes = ref([])
const showMappingModal = ref(false)
const showSyncResults = ref(false)
const currentMarketplace = ref(null)
const syncing = ref(false)
const syncResults = ref({})

const newMapping = ref({
  our_attr_id: '',
  marketplace_attr_name: ''
})

// Загрузка данных при монтировании
onMounted(() => {
  loadMarketplaces()
  loadSyncLogs()
})

// Методы
const loadMarketplaces = async () => {
  try {
    const response = await axios.get('/admin/marketplace')
    marketplaces.value = response.data.marketplaces
  } catch (error) {
    console.error('Ошибка загрузки маркетплейсов:', error)
  }
}

const loadSyncLogs = async () => {
  try {
    const response = await axios.get('/admin/marketplace/sync-log')
    logs.value = response.data.logs
  } catch (error) {
    console.error('Ошибка загрузки логов:', error)
  }
}

const openMappingModal = async (marketplaceId) => {
  currentMarketplace.value = marketplaceId
  showMappingModal.value = true
  
  try {
    const response = await axios.get(`/admin/marketplace/${marketplaceId}/mappings`)
    mappings.value = response.data.mappings
    ourAttributes.value = response.data.our_attributes
    marketplaceAttributes.value = response.data.marketplace_attributes
  } catch (error) {
    console.error('Ошибка загрузки маппингов:', error)
  }
}

const closeMappingModal = () => {
  showMappingModal.value = false
  currentMarketplace.value = null
  newMapping.value = {
    our_attr_id: '',
    marketplace_attr_name: ''
  }
}

const saveMapping = async () => {
  try {
    await axios.post('/admin/marketplace/mapping', {
      marketplace: currentMarketplace.value,
      our_attr_id: newMapping.value.our_attr_id,
      marketplace_attr_name: newMapping.value.marketplace_attr_name
    })
    
    // Перезагружаем маппинги
    openMappingModal(currentMarketplace.value)
    
    // Очищаем форму
    newMapping.value = {
      our_attr_id: '',
      marketplace_attr_name: ''
    }
    
  } catch (error) {
    console.error('Ошибка сохранения маппинга:', error)
    alert('Ошибка сохранения маппинга')
  }
}

const deleteMapping = async (id) => {
  if (!confirm('Удалить этот маппинг?')) {
    return
  }
  
  try {
    await axios.delete(`/admin/marketplace/mapping/${id}`)
    
    // Перезагружаем маппинги
    openMappingModal(currentMarketplace.value)
    
  } catch (error) {
    console.error('Ошибка удаления маппинга:', error)
    alert('Ошибка удаления маппинга')
  }
}

const syncMarketplace = async (marketplaceId) => {
  if (!confirm('Начать синхронизацию всех товаров с маркетплейсом?')) {
    return
  }
  
  syncing.value = true
  
  try {
    const response = await axios.post('/admin/marketplace/sync', {
      marketplace: marketplaceId
    })
    
    syncResults.value = response.data.results
    showSyncResults.value = true
    
    // Обновляем данные
    loadMarketplaces()
    loadSyncLogs()
    
  } catch (error) {
    console.error('Ошибка синхронизации:', error)
    alert('Ошибка синхронизации: ' + error.response?.data?.error || error.message)
  } finally {
    syncing.value = false
  }
}

// Вспомогательные методы
const formatDate = (date) => {
  if (!date) return 'Никогда'
  return new Date(date).toLocaleString('ru-RU')
}

const getMarketplaceName = (id) => {
  const marketplace = marketplaces.value.find(m => m.id === id)
  return marketplace?.name || id
}

const getMarketplaceAttributeName = (id) => {
  const attr = marketplaceAttributes.value.find(a => a.id === id)
  return attr?.name || id
}
</script>

<style scoped>
/* Мобильная адаптивность */
@media (max-width: 768px) {
  .marketplace-manager .grid {
    grid-template-columns: 1fr !important;
  }
  
  .fixed .max-w-4xl {
    max-width: 100%;
    margin: 1rem;
  }
}

/* Анимации */
.marketplace-manager button {
  transition: all 0.2s ease;
}

.marketplace-manager button:active {
  transform: scale(0.95);
}

/* Скроллбар для логов */
.max-h-64::-webkit-scrollbar,
.max-h-48::-webkit-scrollbar {
  width: 6px;
}

.max-h-64::-webkit-scrollbar-track,
.max-h-48::-webkit-scrollbar-track {
  background: #374151;
  border-radius: 3px;
}

.max-h-64::-webkit-scrollbar-thumb,
.max-h-48::-webkit-scrollbar-thumb {
  background: #6B7280;
  border-radius: 3px;
}

.max-h-64::-webkit-scrollbar-thumb:hover,
.max-h-48::-webkit-scrollbar-thumb:hover {
  background: #9CA3AF;
}
</style>