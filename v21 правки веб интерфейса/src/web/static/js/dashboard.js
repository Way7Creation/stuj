
/**
 * DashboardManager - Управляет главной страницей дашборда.
 * Обеспечивает интерактивность, загрузку данных и real-time обновления.
 * Версия 3.0, переписана на Vanilla JS для консистентности и производительности.
 */
class DashboardManager {
    constructor() {
        this.socket = window.wsHandler; // Используем глобальный обработчик
        this.charts = {};
        this.elements = {};
        this.initialize();
    }

    /**
     * Инициализация менеджера
     */
    async initialize() {
        this.mapElements();
        if (!this.elements.botStatus) {
            console.warn('Элементы дашборда не найдены. Скрипт не будет выполнен.');
            return;
        }
        this.initializeCharts();
        this.setupEventListeners();
        this.setupWebSocketHandlers();
        await this.loadInitialData();
    }

    /**
     * Кэширование ссылок на DOM-элементы
     */
    mapElements() {
        const ids = [
            'bot-status', 'total-balance', 'available-balance', 'in-positions', 'pnl-today',
            'pnl-percent', 'start-bot-btn', 'stop-bot-btn', 'emergency-stop-btn', 'refresh-data-btn',
            'balance-updated', 'total-trades', 'win-rate', 'profitable-trades', 'losing-trades',
            'max-drawdown', 'trades-table-body', 'positions-table-body', 'active-positions-count',
            'balanceChart', 'notifications-container'
        ];
        ids.forEach(id => {
            // Преобразуем kebab-case в camelCase для имени свойства
            const camelCaseId = id.replace(/-(\w)/g, (_, c) => c.toUpperCase());
            this.elements[camelCaseId] = document.getElementById(id);
        });
    }

    /**
     * Инициализация графика баланса
     */
    initializeCharts() {
        if (!this.elements.balanceChart) return;
        this.charts.balance = new Chart(this.elements.balanceChart, {
            type: 'line',
            data: {
                labels: [],
                datasets: [{
                    label: 'Баланс USDT',
                    data: [],
                    borderColor: 'rgba(33, 150, 243, 0.8)',
                    backgroundColor: 'rgba(33, 150, 243, 0.1)',
                    tension: 0.3,
                    fill: true,
                    pointRadius: 0,
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    x: { ticks: { display: false }, grid: { color: 'rgba(255,255,255,0.1)' } },
                    y: { ticks: { color: '#787b86' }, grid: { color: 'rgba(255,255,255,0.1)' } }
                }
            }
        });
    }

    /**
     * Настройка обработчиков событий
     */
    setupEventListeners() {
        this.elements.startBotBtn?.addEventListener('click', () => this.controlBot('start'));
        this.elements.stopBotBtn?.addEventListener('click', () => this.controlBot('stop'));
        this.elements.emergencyStopBtn?.addEventListener('click', () => this.controlBot('emergency_stop'));
        this.elements.refreshDataBtn?.addEventListener('click', () => this.loadInitialData());
    }
    
    /**
     * Подписка на события WebSocket
     */
    setupWebSocketHandlers() {
        if (!this.socket) return;
        this.socket.on('bot_status', (data) => this.updateUI(data));
        this.socket.on('balance_update', (data) => this.updateBalance(data.balance));
        this.socket.on('trade_update', () => this.loadRecentTrades());
        this.socket.on('position_update', (data) => this.updatePositionsTable(data.positions || []));
    }
    
    /**
     * Загрузка всех начальных данных
     */
    async loadInitialData() {
        this.showNotification('Обновление данных...', 'info');
        await Promise.allSettled([
            this.apiRequest('/api/bot/status').then(data => this.updateUI(data)),
            this.apiRequest('/api/balance').then(data => this.updateBalance(data.balance)),
            this.apiRequest('/api/bot/positions').then(data => this.updatePositionsTable(data.positions)),
            this.apiRequest('/api/trades/recent?limit=5').then(data => this.updateTradesTable(data.trades)),
            this.apiRequest('/api/trading/stats').then(data => this.updateStatistics(data.stats)),
        ]);
    }
    
    /**
     * Отправка запроса на управление ботом
     * @param {string} action - 'start', 'stop', 'emergency_stop'
     */
    async controlBot(action) {
        const btn = {
            'start': this.elements.startBotBtn,
            'stop': this.elements.stopBotBtn,
            'emergency_stop': this.elements.emergencyStopBtn
        }[action];

        if (!btn) return;
        
        const originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<span class="spinner-border spinner-border-sm" role="status" aria-hidden="true"></span> Выполняется...`;

        try {
            const data = await this.apiRequest(`/api/bot/${action}`, { method: 'POST' });
            this.showNotification(data.message || `Действие "${action}" выполнено.`, 'success');
            // Статус обновится через WebSocket, что вернет кнопку в правильное состояние
        } catch (error) {
            this.showNotification(`Ошибка: ${error.message}`, 'error');
            btn.innerHTML = originalText; // Возвращаем текст кнопки только в случае ошибки
            btn.disabled = false;
        }
    }
    
    /**
     * Общий метод для API запросов
     */
    async apiRequest(url, options = {}) {
        try {
            const response = await fetch(url, options);
            const data = await response.json();
            if (!response.ok || !data.success) {
                throw new Error(data.error || data.message || `Ошибка запроса к ${url}`);
            }
            return data;
        } catch (error) {
            console.error(`Ошибка API [${url}]:`, error);
            this.showNotification(error.message, 'error');
            throw error;
        }
    }

    /**
     * Обновление всего UI на основе полного статуса бота
     */
    updateUI(data) {
        if (!data) return;
        this.updateBotStatus(data);
        if (data.balance) this.updateBalance(data.balance);
        if (data.statistics) this.updateStatistics(data.statistics);
        if (data.open_positions) this.updatePositionsTable(data.open_positions);
    }
    
    /**
     * Обновление статуса и кнопок управления
     */
    updateBotStatus(data) {
        const { is_running } = data;
        const statusEl = this.elements.botStatus;
        statusEl.classList.remove('status-running', 'status-stopped', 'status-loading');
        
        if (is_running) {
            statusEl.classList.add('status-running');
            statusEl.innerHTML = `<i class="fas fa-check-circle"></i> Бот работает`;
            this.elements.startBotBtn.disabled = true;
            this.elements.stopBotBtn.disabled = false;
        } else {
            statusEl.classList.add('status-stopped');
            statusEl.innerHTML = `<i class="fas fa-stop-circle"></i> Бот остановлен`;
            this.elements.startBotBtn.disabled = false;
            this.elements.stopBotBtn.disabled = true;
        }
        this.elements.emergencyStopBtn.disabled = !is_running;
    }

    /**
     * Обновление виджетов баланса и графика
     */
    updateBalance(balance) {
        if (!balance) return;
        const format = (val) => `$${(val || 0).toFixed(2)}`;
        this.elements.totalBalance.textContent = format(balance.total_usdt);
        this.elements.availableBalance.textContent = format(balance.available_usdt);
        this.elements.inPositions.textContent = format(balance.in_positions_usdt);
        
        const pnlValue = balance.pnl_today || 0;
        this.elements.pnlToday.textContent = format(pnlValue);
        this.elements.pnlToday.className = `stat-value ${pnlValue >= 0 ? 'text-success' : 'text-danger'}`;

        if (this.charts.balance) {
            const chart = this.charts.balance;
            chart.data.labels.push(new Date().toLocaleTimeString());
            chart.data.datasets[0].data.push(balance.total_usdt || 0);
            if (chart.data.labels.length > 50) { // Keep last 50 points
                chart.data.labels.shift();
                chart.data.datasets[0].data.shift();
            }
            chart.update('none');
            this.elements.balanceUpdated.textContent = new Date().toLocaleTimeString();
        }
    }
    
    /**
     * Обновление виджета статистики
     */
    updateStatistics(stats) {
        if (!stats) return;
        this.elements.totalTrades.textContent = stats.total_trades || 0;
        this.elements.winRate.textContent = `${(stats.win_rate * 100 || 0).toFixed(1)}%`;
        this.elements.profitableTrades.textContent = stats.winning_trades || 0;
        this.elements.losingTrades.textContent = stats.losing_trades || 0;
        this.elements.maxDrawdown.textContent = `${(stats.max_drawdown || 0).toFixed(2)}%`;
    }

    /**
     * Обновление таблицы недавних сделок
     */
    updateTradesTable(trades) {
        const tbody = this.elements.tradesTableBody;
        if (!trades) return;
        tbody.innerHTML = trades.length === 0 
            ? '<tr><td colspan="8" class="text-center">Нет недавних сделок</td></tr>'
            : trades.map(trade => {
                const pnlClass = (trade.profit || 0) >= 0 ? 'profit-positive' : 'profit-negative';
                return `
                    <tr>
                        <td>${new Date(trade.timestamp || trade.created_at).toLocaleString()}</td>
                        <td>${trade.symbol}</td>
                        <td><span class="badge bg-${trade.side === 'BUY' ? 'success' : 'danger'}">${trade.side}</span></td>
                        <td>$${(trade.entry_price || 0).toFixed(4)}</td>
                        <td>$${(trade.exit_price || 0).toFixed(4)}</td>
                        <td>${(trade.quantity || 0).toFixed(4)}</td>
                        <td class="${pnlClass}">$${(trade.profit || 0).toFixed(2)}</td>
                        <td><span class="badge bg-info">${trade.status || 'CLOSED'}</span></td>
                    </tr>`;
            }).join('');
    }

    /**
     * Обновление таблицы активных позиций
     */
    updatePositionsTable(positions) {
        const tbody = this.elements.positionsTableBody;
        if (!positions) return;
        this.elements.activePositionsCount.textContent = positions.length;
        tbody.innerHTML = positions.length === 0
            ? '<tr><td colspan="8" class="text-center">Нет активных позиций</td></tr>'
            : positions.map(pos => {
                const pnlClass = (pos.pnl || 0) >= 0 ? 'profit-positive' : 'profit-negative';
                return `
                    <tr>
                        <td>${pos.symbol}</td>
                        <td><span class="badge bg-${pos.side.toLowerCase() === 'long' ? 'success' : 'danger'}">${pos.side}</span></td>
                        <td>${(pos.quantity || 0).toFixed(4)}</td>
                        <td>$${(pos.entry_price || 0).toFixed(4)}</td>
                        <td>$${(pos.current_price || 0).toFixed(4)}</td>
                        <td class="${pnlClass}">$${(pos.pnl || 0).toFixed(2)} (${(pos.pnl_percent || 0).toFixed(2)}%)</td>
                        <td>${new Date(pos.open_time).toLocaleString()}</td>
                        <td><button class="btn btn-sm btn-warning">Закрыть</button></td>
                    </tr>`;
            }).join('');
    }

    /**
     * Показ уведомлений
     */
    showNotification(message, type = 'info') {
        if (!this.elements.notificationsContainer) return;
        const notification = document.createElement('div');
        notification.className = `notification notification-${type}`;
        notification.innerHTML = `<strong>${type.toUpperCase()}:</strong> ${message}`;
        this.elements.notificationsContainer.appendChild(notification);
        setTimeout(() => notification.remove(), 5000);
    }
}

document.addEventListener('DOMContentLoaded', () => {
    new DashboardManager();
});
