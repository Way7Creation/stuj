
/**
 * SettingsManager - Управляет страницей настроек.
 * Загружает и сохраняет различные конфигурации системы.
 */
class SettingsManager {
    constructor() {
        console.log('⚙️ SettingsManager создан');
        this.initialize();
    }

    initialize() {
        this.setupEventListeners();
        this.loadAllSettings();
    }

    setupEventListeners() {
        document.getElementById('general-settings-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveForm('general-settings-form', '/api/settings/general', 'Основные настройки сохранены');
        });

        document.getElementById('risk-settings-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.saveForm('risk-settings-form', '/api/settings/risk', 'Настройки риска сохранены');
        });

        document.getElementById('add-pair-form')?.addEventListener('submit', (e) => {
            e.preventDefault();
            this.addTradingPair();
        });
        
        document.getElementById('test-telegram')?.addEventListener('click', () => this.testTelegram());

        // Event delegation for dynamic buttons
        const pairsTableBody = document.querySelector('#trading-pairs-table tbody');
        pairsTableBody?.addEventListener('click', (e) => {
            const deleteButton = e.target.closest('.delete-pair');
            if (deleteButton) {
                 this.deleteTradingPair(deleteButton.dataset.id);
            }
        });

        pairsTableBody?.addEventListener('change', (e) => {
             const toggleSwitch = e.target.closest('.form-check-input');
             if(toggleSwitch) {
                this.togglePairActive(toggleSwitch.dataset.id, e.target.checked);
             }
        });
    }

    async loadAllSettings() {
        try {
            await this.loadMainSettings();
            await this.loadTradingPairs();
            await this.checkApiStatus();
        } catch (error) {
            this.showAlert('danger', 'Не удалось загрузить все настройки.');
            console.error("Ошибка загрузки настроек:", error);
        }
    }

    async loadMainSettings() {
        try {
            const response = await fetch('/api/settings');
            const data = await response.json();
            if (data.success) {
                this.populateForms(data.settings);
            }
        } catch (error) {
            console.error('Ошибка загрузки основных настроек:', error);
        }
    }
    
    populateForms(settings) {
        for (const key in settings) {
            const element = document.getElementById(key);
            if (element) {
                element.value = settings[key];
            }
        }
    }
    
    async loadTradingPairs() {
        try {
            const response = await fetch('/api/trading-pairs');
            const data = await response.json();
            const tbody = document.querySelector('#trading-pairs-table tbody');
            if (!tbody) return;

            if (data.success && data.pairs.length > 0) {
                tbody.innerHTML = data.pairs.map(pair => `
                    <tr>
                        <td>${pair.symbol}</td>
                        <td>
                             <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" role="switch" 
                                       id="pair-toggle-${pair.id}" data-id="${pair.id}" ${pair.is_active ? 'checked' : ''}>
                            </div>
                        </td>
                        <td>${pair.strategy}</td>
                        <td>${pair.stop_loss_percent}%</td>
                        <td>${pair.take_profit_percent}%</td>
                        <td>
                            <button class="btn btn-sm btn-danger delete-pair" data-id="${pair.id}">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = '<tr><td colspan="6" class="text-center">Торговые пары не добавлены</td></tr>';
            }
        } catch (error) {
             console.error('Ошибка загрузки торговых пар:', error);
             const tbody = document.querySelector('#trading-pairs-table tbody');
             if(tbody) tbody.innerHTML = '<tr><td colspan="6" class="text-center text-danger">Ошибка загрузки</td></tr>';
        }
    }

    async checkApiStatus() {
         try {
            const response = await fetch('/api/status');
            const data = await response.json();
            
            const bybitStatus = document.getElementById('bybit-status');
            if (data.exchange?.connected) {
                bybitStatus.className = 'badge bg-success';
                bybitStatus.textContent = 'Подключено';
            } else {
                 bybitStatus.className = 'badge bg-danger';
                 bybitStatus.textContent = 'Отключено';
            }
            document.getElementById('bybit-mode').textContent = data.config?.mode || 'N/A';

         } catch (error) {
             console.error('Ошибка проверки статуса API:', error);
         }
    }

    async saveForm(formId, url, successMessage) {
        const form = document.getElementById(formId);
        const formData = new FormData(form);
        const data = Object.fromEntries(formData.entries());

        try {
            const response = await fetch(url, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data),
            });
            const result = await response.json();
            if (result.success) {
                this.showAlert('success', successMessage);
            } else {
                throw new Error(result.error || 'Неизвестная ошибка');
            }
        } catch (error) {
            this.showAlert('danger', `Ошибка сохранения: ${error.message}`);
        }
    }

    async addTradingPair() {
        const symbolInput = document.getElementById('pair-symbol');
        const strategyInput = document.getElementById('pair-strategy');
        const symbol = symbolInput.value.toUpperCase();
        const strategy = strategyInput.value;

        if (!symbol) {
            this.showAlert('warning', 'Необходимо указать символ пары.');
            return;
        }

        try {
            const response = await fetch('/api/trading-pairs', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ symbol, strategy, is_active: true }),
            });
            const result = await response.json();
            if (result.success) {
                this.showAlert('success', 'Торговая пара добавлена.');
                this.loadTradingPairs();
                const modal = bootstrap.Modal.getInstance(document.getElementById('addPairModal'));
                if (modal) modal.hide();
                symbolInput.value = '';
            } else {
                throw new Error(result.error);
            }
        } catch (error) {
            this.showAlert('danger', `Ошибка добавления пары: ${error.message}`);
        }
    }

    async deleteTradingPair(pairId) {
        if (!confirm('Вы уверены, что хотите удалить эту торговую пару?')) return;

        try {
            const response = await fetch(`/api/trading-pairs/${pairId}`, { method: 'DELETE' });
            if (!response.ok) throw new Error('Failed to delete');
            this.showAlert('success', 'Торговая пара удалена.');
            this.loadTradingPairs();
        } catch (error) {
            this.showAlert('danger', 'Ошибка удаления пары.');
        }
    }
    
    async togglePairActive(pairId, isActive) {
        try {
            const response = await fetch(`/api/trading-pairs/${pairId}/toggle`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ is_active: isActive }),
            });
            if (!response.ok) throw new Error('Failed to toggle');
             this.showAlert('success', `Статус пары обновлен.`);
        } catch (error) {
            this.showAlert('danger', 'Ошибка обновления статуса пары.');
            this.loadTradingPairs(); // Revert UI on failure
        }
    }

    async testTelegram() {
        try {
            const response = await fetch('/api/test/telegram', { method: 'POST' });
            if (!response.ok) throw new Error('Failed to send');
            this.showAlert('success', 'Тестовое сообщение отправлено в Telegram.');
        } catch (error) {
            this.showAlert('danger', 'Ошибка отправки тестового сообщения.');
        }
    }

    showAlert(type, message) {
        const container = document.getElementById('settings-alerts');
        if (!container) return;
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        `;
        container.innerHTML = alertHtml;
    }
}

let settingsManager;
document.addEventListener('DOMContentLoaded', () => {
    settingsManager = new SettingsManager();
});
