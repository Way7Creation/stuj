
/**
 * ChartsManager - Управляет страницей с графиками.
 * Инициализирует TradingView, графики баланса, позиций и обновляет их в реальном времени.
 */
class ChartsManager {
    constructor() {
        this.socket = null;
        this.charts = {};
        this.currentSymbol = 'BTCUSDT';
        this.tradingViewWidget = null;
        this.tvChart = null; // Добавлено для хранения активного графика TradingView
        
        console.log('📊 ChartsManager создан');
        this.initialize();
    }

    async initialize() {
        this.setupEventHandlers();
        this.initializeAllCharts();
        await this.loadInitialData();
        this.connectWebSocket();
    }

    /**
     * Настройка обработчиков событий
     */
    setupEventHandlers() {
        document.getElementById('symbolSelector')?.addEventListener('change', (e) => {
            this.currentSymbol = e.target.value;
            this.onSymbolChange();
        });

        document.getElementById('refreshBtn')?.addEventListener('click', () => {
            this.loadInitialData();
            this.showToast('Данные обновлены', 'success');
        });
    }

    /**
     * Инициализация всех графиков на странице
     */
    initializeAllCharts() {
        this.initTradingViewChart();
        this.initBalanceChart();
        this.initPositionsChart();
    }
    
    /**
     * Загрузка начальных данных для всех элементов
     */
    async loadInitialData() {
        try {
            const [balanceData, positionsData, tradesData, tickerData] = await Promise.all([
                this.apiRequest('/api/balance'),
                this.apiRequest('/api/bot/positions'),
                this.apiRequest('/api/trades/recent?limit=10'),
                this.apiRequest(`/api/ticker/${this.currentSymbol}`)
            ]);

            if (balanceData.success) this.updateBalanceIndicators(balanceData.balance);
            if (positionsData.success) this.updatePositionsChart(positionsData.positions);
            if (tradesData.success) this.updateTradesTable(tradesData.trades);
            // ИСПРАВЛЕНО: Раньше было tickerData.ticker, что приводило к ошибке.
            if (tickerData.success) this.updatePriceIndicator(tickerData);
            
        } catch (error) {
            console.error("Ошибка загрузки начальных данных:", error);
            this.showToast("Не удалось загрузить данные", "error");
        }
    }

    /**
     * Инициализация графика TradingView
     */
    initTradingViewChart() {
        const container = document.getElementById('tradingview_chart');
        if (!container || typeof TradingView === 'undefined') {
            console.error('TradingView library is not loaded or container not found.');
            return;
        }

        this.tradingViewWidget = new TradingView.widget({
            autosize: true,
            symbol: this.currentSymbol,
            interval: "15",
            timezone: "Etc/UTC",
            theme: "dark",
            style: "1",
            locale: "ru",
            enable_publishing: false,
            allow_symbol_change: false,
            container_id: "tradingview_chart"
        });

        // ИСПРАВЛЕНО: Ждем готовности виджета, чтобы получить доступ к его методам
        this.tradingViewWidget.onChartReady(() => {
            this.tvChart = this.tradingViewWidget.activeChart();
            console.log('TradingView Chart is ready.');
        });
    }

    /**
     * Инициализация графика баланса
     */
    initBalanceChart() {
        const ctx = document.getElementById('balanceChart')?.getContext('2d');
        if (!ctx) return;
        this.charts.balance = new Chart(ctx, {
            type: 'line',
            data: { labels: [], datasets: [{ label: 'Баланс (USDT)', data: [], borderColor: '#4caf50', tension: 0.3, fill: true, backgroundColor: 'rgba(76, 175, 80, 0.1)' }] },
            options: this.getCommonChartOptions()
        });
    }

    /**
     * Инициализация графика позиций
     */
    initPositionsChart() {
        const ctx = document.getElementById('positionsChart')?.getContext('2d');
        if (!ctx) return;
        this.charts.positions = new Chart(ctx, {
            type: 'doughnut',
            data: { labels: [], datasets: [{ data: [], backgroundColor: ['#26a69a', '#2196f3', '#ffb74d', '#ef5350', '#7e57c2'] }] },
            options: this.getCommonChartOptions({ plugins: { legend: { position: 'right', labels: {color: '#d1d4dc'} } } })
        });
    }

    /**
     * Подключение к WebSocket для real-time обновлений
     */
    connectWebSocket() {
        if (typeof io === 'undefined') return;
        this.socket = io();
        this.socket.on('connect', () => console.log('🔌 WebSocket на странице графиков подключен'));
        this.socket.on('balance_update', (data) => this.updateBalanceIndicators(data.balance));
        this.socket.on('price_update', (data) => this.updatePriceIndicator(data));
        this.socket.on('position_update', (data) => this.updatePositionsChart(data.positions || []));
        this.socket.on('new_trade', (trade) => this.handleNewTrade(trade));
    }
    
    /**
     * Обновление индикаторов и графиков
     */
    updateBalanceIndicators(balance) {
        document.getElementById('totalBalance').textContent = balance && balance.total_usdt != null ? `$${balance.total_usdt.toFixed(2)}` : '-';
        document.getElementById('pnlToday').textContent = balance && balance.pnl_today != null ? `$${balance.pnl_today.toFixed(2)}` : '-';
        
        const chart = this.charts.balance;
        if (!chart || !balance || balance.total_usdt == null) return;
        
        const label = new Date().toLocaleTimeString();
        chart.data.labels.push(label);
        chart.data.datasets[0].data.push(balance.total_usdt);
        if (chart.data.labels.length > 20) {
            chart.data.labels.shift();
            chart.data.datasets[0].data.shift();
        }
        chart.update();
    }
    
    updatePriceIndicator(priceUpdate) {
        if (!priceUpdate || priceUpdate.symbol !== this.currentSymbol) return;
        
        document.getElementById('currentPrice').textContent = priceUpdate.price != null ? `$${Number(priceUpdate.price).toFixed(2)}` : '-';
        const changeEl = document.getElementById('priceChange');
        if (priceUpdate.change_24h != null) {
            const changeVal = Number(priceUpdate.change_24h);
            changeEl.textContent = `${changeVal.toFixed(2)}%`;
            changeEl.className = `indicator-value ${changeVal >= 0 ? 'text-success' : 'text-danger'}`;
        } else {
            changeEl.textContent = '-';
            changeEl.className = 'indicator-value';
        }
        document.getElementById('volume24h').textContent = priceUpdate.volume_24h != null ? `$${(Number(priceUpdate.volume_24h) / 1e9).toFixed(2)}B` : '-';
    }

    updatePositionsChart(positions) {
        if (!positions) return;
        document.getElementById('activePositions').textContent = positions.length;
        const chart = this.charts.positions;
        if(!chart) return;
        
        chart.data.labels = positions.map(p => p.symbol);
        chart.data.datasets[0].data = positions.map(p => p.size_usdt);
        chart.update();
    }
    
    updateTradesTable(trades) {
        const tbody = document.getElementById('tradesTableBody');
        if (!trades || trades.length === 0) {
            tbody.innerHTML = `<tr><td colspan="5" class="text-center">Нет сделок</td></tr>`;
            return;
        }
        tbody.innerHTML = trades.map(trade => `
            <tr>
                <td>${trade.created_at ? new Date(trade.created_at).toLocaleTimeString() : '-'}</td>
                <td><span class="badge bg-primary">${trade.symbol || '-'}</span></td>
                <td><span class="badge bg-${trade.side === 'BUY' ? 'success' : 'danger'}">${trade.side || '-'}</span></td>
                <td>${trade.entry_price != null ? '$' + Number(trade.entry_price).toFixed(2) : '-'}</td>
                <td class="${(trade.profit_loss || 0) >= 0 ? 'text-success' : 'text-danger'}">
                    ${trade.profit_loss != null ? '$' + Number(trade.profit_loss).toFixed(2) : '-'}
                </td>
            </tr>`).join('');
    }

    handleNewTrade(trade) {
        this.showToast(`Новая сделка: ${trade.symbol}`, 'success');
        this.loadInitialData();
    }
    
    onSymbolChange() {
        console.log(`Смена символа на: ${this.currentSymbol}`);
        // ИСПРАВЛЕНО: Используем tvChart, полученный после готовности виджета
        if (this.tvChart) {
            this.tvChart.setSymbol(this.currentSymbol);
        } else if (this.tradingViewWidget) {
            // Fallback, если tvChart еще не установился
            this.tradingViewWidget.onChartReady(() => {
                this.tradingViewWidget.activeChart().setSymbol(this.currentSymbol);
            });
        }
        this.loadInitialData();
    }

    /**
     * Вспомогательные функции
     */
    async apiRequest(url, options = {}) {
        const response = await fetch(url, options);
        if (!response.ok) throw new Error(`API Request failed: ${response.status}`);
        return response.json();
    }
    
    getCommonChartOptions(extraOptions = {}) {
        return {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                x: { ticks: { color: '#787b86' }, grid: { color: 'rgba(255,255,255,0.1)' } },
                y: { ticks: { color: '#787b86' }, grid: { color: 'rgba(255,255,255,0.1)' } }
            },
            ...extraOptions
        };
    }
    
    showToast(message, type = 'info') {
        const toastId = 'charts-toast';
        let toast = document.getElementById(toastId);
        if (toast) toast.remove();
        
        toast = document.createElement('div');
        toast.id = toastId;
        toast.textContent = message;
        const bgColor = type === 'success' ? 'var(--accent-green)' : 'var(--accent-red)';
        toast.style.cssText = `
            position: fixed; top: 80px; right: 20px; padding: 15px; 
            background-color: ${bgColor}; 
            color: white; border-radius: 5px; z-index: 1060;
            transition: opacity 0.3s ease;
        `;
        document.body.appendChild(toast);
        setTimeout(() => {
            toast.style.opacity = '0';
            setTimeout(() => toast.remove(), 300);
        }, 3000);
    }
}

let chartsManager;
document.addEventListener('DOMContentLoaded', () => {
    chartsManager = new ChartsManager();
});
