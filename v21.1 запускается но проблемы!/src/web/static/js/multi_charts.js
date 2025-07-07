// Управление множественными графиками валют
class MultiCurrencyCharts {
    constructor() {
        this.charts = {};
        this.activeSymbols = ['BTCUSDT', 'ETHUSDT', 'BNBUSDT', 'SOLUSDT'];
        this.updateInterval = 5000; // 5 секунд
        this.updateTimer = null;
    }

    // Инициализация
    init() {
        this.createChartContainers();
        this.initializeCharts();
        this.startUpdates();
    }

    // Создание контейнеров для графиков
    createChartContainers() {
        const container = document.getElementById('multi-charts-container');
        if (!container) return;

        container.innerHTML = '';
        
        this.activeSymbols.forEach(symbol => {
            const chartDiv = document.createElement('div');
            chartDiv.className = 'col-md-6 mb-4';
            chartDiv.innerHTML = `
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h6 class="mb-0 text-dark">${symbol}</h6>
                        <div class="price-info">
                            <span id="price-${symbol}" class="current-price text-dark">$0.00</span>
                            <span id="change-${symbol}" class="price-change">0.00%</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div style="height:200px"><canvas id="chart-${symbol}"></canvas></div>
                    </div>
                </div>
            `;
            container.appendChild(chartDiv);
        });
    }

    // Инициализация графиков
    initializeCharts() {
        this.activeSymbols.forEach(symbol => {
            const ctx = document.getElementById(`chart-${symbol}`);
            if (!ctx) return;

            const chart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: [],
                    datasets: [{
                        label: 'Цена',
                        data: [],
                        borderColor: this.getColorForSymbol(symbol),
                        backgroundColor: this.getColorForSymbol(symbol, 0.1),
                        borderWidth: 2,
                        tension: 0.1,
                        pointRadius: 0,
                        fill: true
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    plugins: {
                        legend: { display: false },
                        tooltip: {
                            mode: 'index',
                            intersect: false,
                            callbacks: {
                                label: (context) => `$${context.parsed.y.toFixed(2)}`
                            }
                        }
                    },
                    scales: {
                        x: { display: false },
                        y: {
                            display: true,
                            grid: { color: 'rgba(200, 200, 200, 0.1)' },
                            ticks: {
                                color: '#333',
                                callback: (value) => '$' + value.toFixed(0)
                            }
                        }
                    },
                    interaction: { mode: 'nearest', axis: 'x', intersect: false }
                }
            });

            this.charts[symbol] = { chart, maxPoints: 50 };
        });
    }

    // Получение цвета для символа
    getColorForSymbol(symbol, alpha = 1) {
        const colors = {
            'BTCUSDT': `rgba(247, 147, 26, ${alpha})`,
            'ETHUSDT': `rgba(98, 126, 234, ${alpha})`,
            'BNBUSDT': `rgba(243, 186, 47, ${alpha})`,
            'SOLUSDT': `rgba(133, 94, 255, ${alpha})`
        };
        return colors[symbol] || `rgba(75, 192, 192, ${alpha})`;
    }

    // Начать обновления
    startUpdates() {
        this.stopUpdates(); // Остановить предыдущий таймер, если он есть
        this.updateAllCharts();
        this.updateTimer = setInterval(() => this.updateAllCharts(), this.updateInterval);
    }

    // Остановить обновления
    stopUpdates() {
        if (this.updateTimer) {
            clearInterval(this.updateTimer);
            this.updateTimer = null;
        }
    }

    // Обновить все графики
    async updateAllCharts() {
        try {
            // Создаем массив промисов для получения данных по каждому тикеру
            const promises = this.activeSymbols.map(symbol =>
                fetch(`/api/ticker/${symbol}`).then(res => {
                    if (!res.ok) throw new Error(`Failed to fetch ${symbol}`);
                    return res.json();
                })
            );
            
            const results = await Promise.all(promises);
    
            results.forEach(result => {
                if (result.success && result.ticker) {
                    const { symbol, price, change_24h } = result.ticker;
                    if (this.charts[symbol]) {
                        this.updateChart(symbol, price);
                        this.updatePriceDisplay(symbol, price, change_24h);
                    }
                }
            });
        } catch (error) {
            console.error('Ошибка обновления мульти-графиков:', error);
            // Можно остановить таймер, чтобы не спамить ошибками в консоль
            // this.stopUpdates(); 
        }
    }

    // Обновить отдельный график
    updateChart(symbol, price) {
        const chartData = this.charts[symbol];
        if (!chartData) return;

        const timeLabel = new Date().toLocaleTimeString();
        const { labels, datasets } = chartData.chart.data;

        labels.push(timeLabel);
        datasets[0].data.push(price);

        if (labels.length > chartData.maxPoints) {
            labels.shift();
            datasets[0].data.shift();
        }

        chartData.chart.update('none'); // Обновляем без анимации для плавности
    }

    // Обновить отображение цены
    updatePriceDisplay(symbol, price, change) {
        const priceElement = document.getElementById(`price-${symbol}`);
        const changeElement = document.getElementById(`change-${symbol}`);

        if (priceElement) {
            priceElement.textContent = `$${Number(price).toFixed(2)}`;
        }

        if (changeElement) {
            const changeVal = Number(change) || 0;
            changeElement.textContent = `${changeVal > 0 ? '+' : ''}${changeVal.toFixed(2)}%`;
            changeElement.className = `price-change ${changeVal >= 0 ? 'text-success' : 'text-danger'}`;
        }
    }
    
    // Изменить интервал обновления
    setUpdateInterval(interval) {
        this.updateInterval = interval;
        this.startUpdates(); // Перезапускаем таймер с новым интервалом
    }
}

// Инициализация при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    if (document.getElementById('multi-charts-container')) {
        window.multiCharts = new MultiCurrencyCharts();
        window.multiCharts.init();
    }
});
