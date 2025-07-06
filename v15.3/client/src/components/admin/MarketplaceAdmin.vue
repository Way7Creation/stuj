<!-- Путь: /client/src/components/admin/MarketplaceAdmin.vue -->
<template>
  <div class="marketplace-admin p-6">
    <!-- Статус синхронизации -->
    <div class="bg-gray-800 rounded-lg p-6 mb-6">
      <h2 class="text-2xl font-bold text-white mb-6">Статус маркетплейсов</h2>
      
      <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <div 
          v-for="mp in marketplaces" 
          :key="mp.id"
          class="bg-gray-700 rounded-lg p-4"
        >
          <h3 class="font-semibold text-white mb-3">{{ mp.name }}</h3>
          
          <div class="space-y-2 text-sm">
            <div class="flex justify-between">
              <span class="text-gray-400">Статус:</span>
              <span :class="mp.enabled ? 'text-green-400' : 'text-red-400'">
                {{ mp.enabled ? 'Активен' : 'Не настроен' }}
              </span>
            </div>
            
            <div class="flex justify-between">
              <span class="text-gray-400">Настроен:</span>
              <span :class="mp.configured ? 'text-green-400' : 'text-yellow-400'">
                {{ mp.configured ? 'Да' : 'Нет' }}
              </span>
            </div>
            
            <div v-if="mp.last_sync" class="flex justify-between">
              <span class="text-gray-400">Синхронизация:</span>
              <span class="text-white">{{ formatDate(mp.last_sync) }}</span>
            </div>
            
            <div class="flex justify-between">
              <span class="text-gray-400">Товаров:</span>
              <span class="text-white">{{ mp.products_count || 0 }}</span>
            </div>
          </div>
          
          <button 
            v-if="mp.enabled && mp.configured"
            @click="syncMarketplace(mp.id)"
            :disabled="syncing[mp.id]"
            class="mt-4 w-full bg-blue-600 text-white py-2 rounded hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
          >
            {{ syncing[mp.id] ? 'Синхронизация...' : 'Синхронизировать' }}
          </button>
        </div>
      </div>
      
      <!-- Кнопка синхронизации всех -->
      <button 
        @click="syncAll"
        :disabled="syncingAll"
        class="bg-green-600 text-white px-6 py-2 rounded hover:bg-green-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all"
      >
        {{ syncingAll ? 'Синхронизация всех...' : 'Синхронизировать все' }}
      </button>
    </div>

    <!-- Маппинг атрибутов -->
    <div class="bg-gray-800 rounded-lg p-6">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-white">Маппинг атрибутов</h2>
        <button 
          @click="showMapModal = true"
          class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition-all"
        >
          + Добавить маппинг
        </button>
      </div>

      <!-- Фильтр по маркетплейсу -->
      <div class="mb-4">
        <select 
          v-model="filterMarketplace"
          @change="loadMaps"
          class="bg-gray-700 text-white px-4 py-2 rounded border border-gray-600 focus:border-blue-500 focus:outline-none"
        >
          <option value="">Все маркетплейсы</option>
          <option value="wildberries">Wildberries</option>
          <option value="ozon">Ozon</option>
          <option value="yandex_market">Яндекс.Маркет</option>
          <option value="flowwow">Flowwow</option>
        </select>
      </div>

      <!-- Таблица маппингов -->
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-700">
              <th class="text-left py-3 px-4 text-gray-400">Маркетплейс</th>
              <th class="text-left py-3 px-4 text-gray-400">Наш атрибут</th>
              <th class="text-left py-3 px-4 text-gray-400">Атрибут маркетплейса</th>
              <th class="text-left py-3 px-4 text-gray-400">Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="map in maps" 
              :key="map.id"
              class="border-b border-gray-700 hover:bg-gray-700/50 transition-colors"
            >
              <td class="py-3 px-4 text-white capitalize">
                {{ map.marketplace.replace('_', ' ') }}
              </td>
              <td class="py-3 px-4 text-white">
                {{ map.attribute?.name || 'Неизвестный атрибут' }}
              </td>
              <td class="py-3 px-4 text-white">
                {{ map.marketplace_attr_name }}
              </td>
              <td class="py-3 px-4">
                <button 
                  @click="editMap(map)"
                  class="text-blue-400 hover:text-blue-300 mr-3"
                >
                  Изменить
                </button>
                <button 
                  @click="deleteMap(map)"
                  class="text-red-400 hover:text-red-300"
                >
                  Удалить
                </button>
              </td>
            </tr>
          </tbody>
        </table>
        
        <!-- Пагинация -->
        <div v-if="pagination.last_page > 1" class="flex justify-center mt-4 space-x-2">
          <button 
            v-for="page in pagination.last_page" 
            :key="page"
            @click="loadMaps(page)"
            :class="[
              'px-3 py-1 rounded',
              pagination.current_page === page 
                ? 'bg-blue-600 text-white' 
                : 'bg-gray-700 text-gray-400 hover:bg-gray-600'
            ]"
          >
            {{ page }}
          </button>
        </div>
      </div>
    </div>

    <!-- Модальное окно маппинга -->
    <div 
      v-if="showMapModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
    >
      <div class="bg-gray-800 rounded-lg p-6 w-full max-w-md">
        <h3 class="text-xl font-bold text-white mb-4">
          {{ editingMap ? 'Редактировать маппинг' : 'Добавить маппинг' }}
        </h3>

        <form @submit.prevent="saveMap" class="space-y-4">
          <div>
            <label class="block text-sm font-medium text-white mb-2">
              Маркетплейс
            </label>
            <select 
              v-model="mapForm.marketplace"
              required
              :disabled="editingMap"
              class="w-full bg-gray-700 text-white px-4 py-2 rounded border border-gray-600 focus:border-blue-500 focus:outline-none disabled:opacity-50"
            >
              <option value="">Выберите маркетплейс</option>
              <option value="wildberries">Wildberries</option>
              <option value="ozon">Ozon</option>
              <option value="yandex_market">Яндекс.Маркет</option>
              <option value="flowwow">Flowwow</option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-white mb-2">
              Наш атрибут
            </label>
            <select 
              v-model="mapForm.our_attr_id"
              required
              class="w-full bg-gray-700 text-white px-4 py-2 rounded border border-gray-600 focus:border-blue-500 focus:outline-none"
            >
              <option value="">Выберите атрибут</option>
              <option 
                v-for="attr in attributes" 
                :key="attr.id"
                :value="attr.id"
              >
                {{ attr.name }}
              </option>
            </select>
          </div>

          <div>
            <label class="block text-sm font-medium text-white mb-2">
              Название атрибута в маркетплейсе
            </label>
            <input 
              v-model="mapForm.marketplace_attr_name"
              type="text"
              required
              class="w-full bg-gray-700 text-white px-4 py-2 rounded border border-gray-600 focus:border-blue-500 focus:outline-none"
              placeholder="Например: brand, material, color"
            >
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <button 
              type="button"
              @click="closeMapModal"
              class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700"
            >
              Отмена
            </button>
            <button 
              type="submit"
              :disabled="savingMap"
              class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50"
            >
              {{ savingMap ? 'Сохранение...' : 'Сохранить' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted, reactive } from 'vue'
import axios from 'axios'

export default {
  name: 'MarketplaceAdmin',
  setup() {
    // Состояние
    const marketplaces = ref([])
    const maps = ref([])
    const attributes = ref([])
    const filterMarketplace = ref('')
    const showMapModal = ref(false)
    const editingMap = ref(null)
    const syncing = reactive({})
    const syncingAll = ref(false)
    const savingMap = ref(false)
    
    const pagination = ref({
      current_page: 1,
      last_page: 1
    })
    
    const mapForm = ref({
      marketplace: '',
      our_attr_id: '',
      marketplace_attr_name: ''
    })
    
    // Загрузка данных о маркетплейсах
    const loadMarketplaces = async () => {
      try {
        const response = await axios.get('/admin/marketplace-maps')
        marketplaces.value = response.data.marketplaces || []
      } catch (error) {
        console.error('Ошибка загрузки маркетплейсов:', error)
      }
    }
    
    // Загрузка маппингов
    const loadMaps = async (page = 1) => {
      try {
        const params = { page }
        if (filterMarketplace.value) {
          params.marketplace = filterMarketplace.value
        }
        
        const response = await axios.get('/admin/marketplace-maps', { params })
        maps.value = response.data.data || []
        pagination.value = {
          current_page: response.data.current_page,
          last_page: response.data.last_page
        }
      } catch (error) {
        console.error('Ошибка загрузки маппингов:', error)
      }
    }
    
    // Загрузка атрибутов
    const loadAttributes = async () => {
      try {
        const response = await axios.get('/admin/attributes')
        attributes.value = response.data
      } catch (error) {
        console.error('Ошибка загрузки атрибутов:', error)
      }
    }
    
    // Синхронизация маркетплейса
    const syncMarketplace = async (marketplace) => {
      syncing[marketplace] = true
      
      try {
        await axios.post('/admin/marketplace/sync', { marketplace })
        alert(`${marketplace} синхронизирован!`)
        await loadMarketplaces()
      } catch (error) {
        console.error('Ошибка синхронизации:', error)
        alert('Ошибка синхронизации')
      } finally {
        syncing[marketplace] = false
      }
    }
    
    // Синхронизация всех
    const syncAll = async () => {
      syncingAll.value = true
      
      try {
        await axios.post('/admin/marketplace/sync')
        alert('Все маркетплейсы синхронизированы!')
        await loadMarketplaces()
      } catch (error) {
        console.error('Ошибка синхронизации:', error)
        alert('Ошибка синхронизации')
      } finally {
        syncingAll.value = false
      }
    }
    
    // Сохранение маппинга
    const saveMap = async () => {
      savingMap.value = true
      
      try {
        if (editingMap.value) {
          await axios.put(`/admin/marketplace-maps/${editingMap.value.id}`, mapForm.value)
        } else {
          await axios.post('/admin/marketplace-maps', mapForm.value)
        }
        
        alert('Маппинг сохранен!')
        closeMapModal()
        await loadMaps()
      } catch (error) {
        console.error('Ошибка сохранения:', error)
        alert('Ошибка сохранения маппинга')
      } finally {
        savingMap.value = false
      }
    }
    
    // Редактирование маппинга
    const editMap = (map) => {
      editingMap.value = map
      mapForm.value = {
        marketplace: map.marketplace,
        our_attr_id: map.our_attr_id,
        marketplace_attr_name: map.marketplace_attr_name
      }
      showMapModal.value = true
    }
    
    // Удаление маппинга
    const deleteMap = async (map) => {
      if (!confirm(`Удалить маппинг для ${map.attribute?.name}?`)) return
      
      try {
        await axios.delete(`/admin/marketplace-maps/${map.id}`)
        alert('Маппинг удален!')
        await loadMaps()
      } catch (error) {
        console.error('Ошибка удаления:', error)
        alert('Ошибка удаления маппинга')
      }
    }
    
    // Закрытие модального окна
    const closeMapModal = () => {
      showMapModal.value = false
      editingMap.value = null
      mapForm.value = {
        marketplace: '',
        our_attr_id: '',
        marketplace_attr_name: ''
      }
    }
    
    // Форматирование даты
    const formatDate = (date) => {
      if (!date) return 'Никогда'
      return new Date(date).toLocaleString('ru-RU')
    }
    
    // Загрузка данных при монтировании
    onMounted(() => {
      loadMarketplaces()
      loadMaps()
      loadAttributes()
    })
    
    return {
      marketplaces,
      maps,
      attributes,
      filterMarketplace,
      showMapModal,
      editingMap,
      syncing,
      syncingAll,
      savingMap,
      pagination,
      mapForm,
      loadMaps,
      syncMarketplace,
      syncAll,
      saveMap,
      editMap,
      deleteMap,
      closeMapModal,
      formatDate
    }
  }
}
</script>

<style scoped>
/* Адаптивность для мобильных */
@media (max-width: 768px) {
  .marketplace-admin {
    padding: 1rem;
  }
  
  table {
    font-size: 0.875rem;
  }
  
  th, td {
    padding: 0.5rem;
  }
}
</style>