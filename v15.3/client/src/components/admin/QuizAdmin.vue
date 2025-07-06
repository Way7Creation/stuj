<!-- Путь: /client/src/components/admin/QuizAdmin.vue -->
<template>
  <div class="quiz-admin p-6">
    <div class="bg-gray-800 rounded-lg p-6">
      <div class="flex justify-between items-center mb-6">
        <h2 class="text-2xl font-bold text-white">Правила квиза</h2>
        <button 
          @click="showRuleModal = true"
          class="bg-green-600 text-white px-4 py-2 rounded hover:bg-green-700 transition-all"
        >
          + Добавить правило
        </button>
      </div>

      <!-- Фильтр по месяцу -->
      <div class="mb-4 flex gap-4">
        <select 
          v-model="filterMonth"
          @change="loadRules"
          class="bg-gray-700 text-white px-4 py-2 rounded border border-gray-600 focus:border-blue-500 focus:outline-none"
        >
          <option value="">Все месяцы</option>
          <option v-for="month in 12" :key="month" :value="month">
            {{ getMonthName(month) }}
          </option>
        </select>
        
        <button 
          @click="loadStones"
          class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 transition-all"
        >
          Обновить список камней
        </button>
      </div>

      <!-- Таблица правил -->
      <div class="overflow-x-auto">
        <table class="w-full">
          <thead>
            <tr class="border-b border-gray-700">
              <th class="text-left py-3 px-4 text-gray-400">Дата</th>
              <th class="text-left py-3 px-4 text-gray-400">Время</th>
              <th class="text-left py-3 px-4 text-gray-400">Рекомендуемые камни</th>
              <th class="text-left py-3 px-4 text-gray-400">Действия</th>
            </tr>
          </thead>
          <tbody>
            <tr 
              v-for="rule in rules" 
              :key="rule.id"
              class="border-b border-gray-700 hover:bg-gray-700/50 transition-colors"
            >
              <td class="py-3 px-4 text-white">
                {{ rule.day }} {{ getMonthName(rule.month) }}
              </td>
              <td class="py-3 px-4 text-white">
                {{ rule.hour_start }}:00 - {{ rule.hour_end }}:00
              </td>
              <td class="py-3 px-4">
                <div class="flex flex-wrap gap-1">
                  <span 
                    v-for="stone in rule.stones" 
                    :key="stone"
                    class="bg-gray-700 text-white px-2 py-1 rounded text-sm"
                  >
                    {{ stone }}
                  </span>
                </div>
              </td>
              <td class="py-3 px-4">
                <button 
                  @click="editRule(rule)"
                  class="text-blue-400 hover:text-blue-300 mr-3"
                >
                  Изменить
                </button>
                <button 
                  @click="deleteRule(rule)"
                  class="text-red-400 hover:text-red-300"
                >
                  Удалить
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <!-- Список всех камней -->
      <div class="mt-6">
        <h3 class="text-lg font-semibold text-white mb-3">Доступные камни</h3>
        <div class="flex flex-wrap gap-2">
          <span 
            v-for="stone in availableStones" 
            :key="stone"
            class="bg-gray-700 text-white px-3 py-1 rounded"
          >
            {{ stone }}
          </span>
        </div>
      </div>
    </div>

    <!-- Модальное окно правила -->
    <div 
      v-if="showRuleModal"
      class="fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center p-4 z-50"
    >
      <div class="bg-gray-800 rounded-lg p-6 w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <h3 class="text-xl font-bold text-white mb-4">
          {{ editingRule ? 'Редактировать правило' : 'Добавить правило' }}
        </h3>

        <form @submit.prevent="saveRule" class="space-y-4">
          <!-- Дата -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-white mb-2">
                День
              </label>
              <input 
                v-model.number="ruleForm.day"
                type="number"
                min="1"
                max="31"
                required
                class="w-full bg-gray-700 text-white px-4 py-2 rounded border border-gray-600 focus:border-blue-500 focus:outline-none"
              >
            </div>
            
            <div>
              <label class="block text-sm font-medium text-white mb-2">
                Месяц
              </label>
              <select 
                v-model.number="ruleForm.month"
                required
                class="w-full bg-gray-700 text-white px-4 py-2 rounded border border-gray-600 focus:border-blue-500 focus:outline-none"
              >
                <option value="">Выберите месяц</option>
                <option v-for="month in 12" :key="month" :value="month">
                  {{ getMonthName(month) }}
                </option>
              </select>
            </div>
          </div>

          <!-- Время -->
          <div class="grid grid-cols-2 gap-4">
            <div>
              <label class="block text-sm font-medium text-white mb-2">
                Начальный час
              </label>
              <select 
                v-model.number="ruleForm.hour_start"
                required
                class="w-full bg-gray-700 text-white px-4 py-2 rounded border border-gray-600 focus:border-blue-500 focus:outline-none"
              >
                <option v-for="hour in 24" :key="hour-1" :value="hour-1">
                  {{ (hour-1).toString().padStart(2, '0') }}:00
                </option>
              </select>
            </div>
            
            <div>
              <label class="block text-sm font-medium text-white mb-2">
                Конечный час
              </label>
              <select 
                v-model.number="ruleForm.hour_end"
                required
                class="w-full bg-gray-700 text-white px-4 py-2 rounded border border-gray-600 focus:border-blue-500 focus:outline-none"
              >
                <option v-for="hour in 24" :key="hour-1" :value="hour-1">
                  {{ (hour-1).toString().padStart(2, '0') }}:00
                </option>
              </select>
            </div>
          </div>

          <!-- Камни -->
          <div>
            <label class="block text-sm font-medium text-white mb-2">
              Рекомендуемые камни
            </label>
            
            <!-- Выбранные камни -->
            <div class="mb-3 flex flex-wrap gap-2">
              <span 
                v-for="(stone, index) in ruleForm.stones" 
                :key="index"
                class="bg-blue-600 text-white px-3 py-1 rounded flex items-center gap-2"
              >
                {{ stone }}
                <button 
                  type="button"
                  @click="removeStone(index)"
                  class="text-white hover:text-red-300"
                >
                  ×
                </button>
              </span>
            </div>
            
            <!-- Добавление камня -->
            <div class="flex gap-2">
              <input 
                v-model="newStone"
                type="text"
                @keyup.enter="addStone"
                class="flex-1 bg-gray-700 text-white px-4 py-2 rounded border border-gray-600 focus:border-blue-500 focus:outline-none"
                placeholder="Введите название камня"
              >
              <button 
                type="button"
                @click="addStone"
                class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700"
              >
                Добавить
              </button>
            </div>
            
            <!-- Быстрый выбор -->
            <div class="mt-3">
              <p class="text-sm text-gray-400 mb-2">Быстрый выбор:</p>
              <div class="flex flex-wrap gap-1">
                <button 
                  v-for="stone in popularStones" 
                  :key="stone"
                  type="button"
                  @click="quickAddStone(stone)"
                  :disabled="ruleForm.stones.includes(stone)"
                  class="bg-gray-700 text-white px-2 py-1 rounded text-sm hover:bg-gray-600 disabled:opacity-50 disabled:cursor-not-allowed"
                >
                  {{ stone }}
                </button>
              </div>
            </div>
          </div>

          <div class="flex justify-end gap-3 pt-4">
            <button 
              type="button"
              @click="closeRuleModal"
              class="bg-gray-600 text-white px-4 py-2 rounded hover:bg-gray-700"
            >
              Отмена
            </button>
            <button 
              type="submit"
              :disabled="savingRule || ruleForm.stones.length === 0"
              class="bg-blue-600 text-white px-4 py-2 rounded hover:bg-blue-700 disabled:opacity-50"
            >
              {{ savingRule ? 'Сохранение...' : 'Сохранить' }}
            </button>
          </div>
        </form>
      </div>
    </div>
  </div>
</template>

<script>
import { ref, onMounted } from 'vue'
import axios from 'axios'

export default {
  name: 'QuizAdmin',
  setup() {
    // Состояние
    const rules = ref([])
    const availableStones = ref([])
    const filterMonth = ref('')
    const showRuleModal = ref(false)
    const editingRule = ref(null)
    const savingRule = ref(false)
    const newStone = ref('')
    
    const ruleForm = ref({
      month: '',
      day: '',
      hour_start: 0,
      hour_end: 23,
      stones: []
    })
    
    const popularStones = [
      'агат', 'аметист', 'бирюза', 'гранат', 'жемчуг', 
      'изумруд', 'лазурит', 'малахит', 'опал', 'рубин',
      'сапфир', 'топаз', 'турмалин', 'хризолит', 'янтарь'
    ]
    
    const monthNames = [
      'Январь', 'Февраль', 'Март', 'Апрель', 'Май', 'Июнь',
      'Июль', 'Август', 'Сентябрь', 'Октябрь', 'Ноябрь', 'Декабрь'
    ]
    
    // Получить название месяца
    const getMonthName = (month) => {
      return monthNames[month - 1] || ''
    }
    
    // Загрузка правил
    const loadRules = async () => {
      try {
        const params = {}
        if (filterMonth.value) {
          params.month = filterMonth.value
        }
        
        const response = await axios.get('/admin/quiz-rules', { params })
        rules.value = response.data.data || response.data || []
      } catch (error) {
        console.error('Ошибка загрузки правил:', error)
      }
    }
    
    // Загрузка списка камней
    const loadStones = async () => {
      try {
        const response = await axios.get('/admin/quiz-rules/stones')
        availableStones.value = response.data || []
      } catch (error) {
        console.error('Ошибка загрузки камней:', error)
      }
    }
    
    // Добавление камня
    const addStone = () => {
      const stone = newStone.value.trim().toLowerCase()
      if (stone && !ruleForm.value.stones.includes(stone)) {
        ruleForm.value.stones.push(stone)
        newStone.value = ''
      }
    }
    
    // Быстрое добавление камня
    const quickAddStone = (stone) => {
      if (!ruleForm.value.stones.includes(stone)) {
        ruleForm.value.stones.push(stone)
      }
    }
    
    // Удаление камня
    const removeStone = (index) => {
      ruleForm.value.stones.splice(index, 1)
    }
    
    // Сохранение правила
    const saveRule = async () => {
      // Валидация времени
      if (ruleForm.value.hour_end < ruleForm.value.hour_start) {
        alert('Конечный час должен быть больше или равен начальному')
        return
      }
      
      savingRule.value = true
      
      try {
        if (editingRule.value) {
          await axios.put(`/admin/quiz-rules/${editingRule.value.id}`, ruleForm.value)
        } else {
          await axios.post('/admin/quiz-rules', ruleForm.value)
        }
        
        alert('Правило сохранено!')
        closeRuleModal()
        await loadRules()
      } catch (error) {
        console.error('Ошибка сохранения:', error)
        const message = error.response?.data?.error || 'Ошибка сохранения правила'
        alert(message)
      } finally {
        savingRule.value = false
      }
    }
    
    // Редактирование правила
    const editRule = (rule) => {
      editingRule.value = rule
      ruleForm.value = {
        month: rule.month,
        day: rule.day,
        hour_start: rule.hour_start,
        hour_end: rule.hour_end,
        stones: [...rule.stones]
      }
      showRuleModal.value = true
    }
    
    // Удаление правила
    const deleteRule = async (rule) => {
      const dateStr = `${rule.day} ${getMonthName(rule.month)}`
      if (!confirm(`Удалить правило для ${dateStr}?`)) return
      
      try {
        await axios.delete(`/admin/quiz-rules/${rule.id}`)
        alert('Правило удалено!')
        await loadRules()
      } catch (error) {
        console.error('Ошибка удаления:', error)
        alert('Ошибка удаления правила')
      }
    }
    
    // Закрытие модального окна
    const closeRuleModal = () => {
      showRuleModal.value = false
      editingRule.value = null
      ruleForm.value = {
        month: '',
        day: '',
        hour_start: 0,
        hour_end: 23,
        stones: []
      }
      newStone.value = ''
    }
    
    // Загрузка данных при монтировании
    onMounted(() => {
      loadRules()
      loadStones()
    })
    
    return {
      rules,
      availableStones,
      filterMonth,
      showRuleModal,
      editingRule,
      savingRule,
      newStone,
      ruleForm,
      popularStones,
      getMonthName,
      loadRules,
      loadStones,
      addStone,
      quickAddStone,
      removeStone,
      saveRule,
      editRule,
      deleteRule,
      closeRuleModal
    }
  }
}
</script>

<style scoped>
/* Адаптивность для мобильных */
@media (max-width: 768px) {
  .quiz-admin {
    padding: 1rem;
  }
  
  table {
    font-size: 0.875rem;
  }
  
  th, td {
    padding: 0.5rem;
  }
  
  .grid-cols-2 {
    grid-template-columns: 1fr;
    gap: 1rem;
  }
}
</style>