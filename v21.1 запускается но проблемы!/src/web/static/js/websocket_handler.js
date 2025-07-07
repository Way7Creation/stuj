// Улучшенный обработчик WebSocket соединений
class WebSocketHandler {
    constructor() {
        this.socket = null;
        this.reconnectInterval = 5000;
        this.reconnectAttempts = 0;
        this.maxReconnectAttempts = 10;
        this.handlers = {};
        this.isConnecting = false;
    }

    // Инициализация соединения
    connect() {
        if (this.isConnecting || (this.socket && this.socket.connected)) {
            return;
        }

        this.isConnecting = true;
        console.log('Подключение к WebSocket...');

        // Убедимся, что io доступно
        if (typeof io === 'undefined') {
            console.error('Socket.IO library not found!');
            this.isConnecting = false;
            return;
        }

        this.socket = io({
            transports: ['websocket', 'polling'],
            reconnection: true,
            reconnectionAttempts: this.maxReconnectAttempts,
            reconnectionDelay: this.reconnectInterval,
            // Добавим таймаут, чтобы избежать зависаний
            timeout: 10000,
        });

        this.setupEventHandlers();
    }

    // Настройка обработчиков событий
    setupEventHandlers() {
        this.socket.on('connect', () => {
            console.log('✅ WebSocket подключен. SID:', this.socket.id);
            this.isConnecting = false;
            this.reconnectAttempts = 0;
            this.onConnect();
        });

        this.socket.on('disconnect', (reason) => {
            console.warn('❌ WebSocket отключен:', reason);
            this.onDisconnect(reason);
        });

        this.socket.on('connect_error', (error) => {
            console.error('WebSocket ошибка подключения:', error.message);
            this.reconnectAttempts++;
            if(this.reconnectAttempts >= this.maxReconnectAttempts) {
                this.isConnecting = false;
            }
        });

        this.socket.on('error', (error) => {
            console.error('WebSocket общая ошибка:', error);
            this.onError(error);
        });

        // Слушаем все события для отладки
        this.socket.onAny((event, ...args) => {
            if (event !== 'log_message') { // Исключаем спам логов
                 console.log(`[WS RCV] ${event}`, args);
            }
            // Вызываем пользовательские обработчики
            this.trigger(event, ...args);
        });
    }

    // Обработчик успешного подключения
    onConnect() {
        // Уведомление об успешном подключении
        if (window.toastr) {
            window.toastr.success('WebSocket подключен', 'Соединение установлено');
        }
        
        // **КЛЮЧЕВОЕ ИЗМЕНЕНИЕ**: Убран вызов emit('get_status'). 
        // Сервер сам присылает 'bot_status' после подключения.
        // Этот вызов вызывал ошибку TypeError на сервере.
    }

    // Обработчик отключения
    onDisconnect(reason) {
        if (window.toastr) {
            window.toastr.warning(`WebSocket отключен: ${reason}`, 'Потеряно соединение');
        }
    }

    // Обработчик ошибок
    onError(error) {
        if (window.toastr) {
            window.toastr.error(`Ошибка WebSocket: ${error.message}`, 'Ошибка соединения');
        }
    }
    
    // Отправка события
    emit(event, data) {
        if (this.socket && this.socket.connected) {
            console.log(`[WS EMIT] ${event}`, data);
            this.socket.emit(event, data);
        } else {
            console.warn('WebSocket не подключен, не могу отправить:', event);
            if (window.toastr) {
                window.toastr.error(`Не удалось отправить данные. WebSocket не подключен.`, 'Ошибка отправки');
            }
        }
    }

    // Регистрация обработчика
    on(event, handler) {
        if (!this.handlers[event]) {
            this.handlers[event] = [];
        }
        this.handlers[event].push(handler);
    }

    // Удаление обработчика
    off(event, handler) {
        if (this.handlers[event]) {
            this.handlers[event] = this.handlers[event].filter(h => h !== handler);
        }
    }

    // Внутренний вызов обработчиков
    trigger(event, ...data) {
        if (this.handlers[event]) {
            this.handlers[event].forEach(handler => {
                try {
                    handler(...data);
                } catch (error) {
                    console.error(`Ошибка в обработчике события "${event}":`, error);
                }
            });
        }
    }

    // Отключение
    disconnect() {
        if (this.socket) {
            this.socket.disconnect();
            this.socket = null;
        }
    }

    // Проверка соединения
    isConnected() {
        return this.socket && this.socket.connected;
    }
}

// Создаем и экспортируем глобальный экземпляр
window.wsHandler = new WebSocketHandler();

// Автоматическое подключение при загрузке страницы
document.addEventListener('DOMContentLoaded', () => {
    window.wsHandler.connect();
});
