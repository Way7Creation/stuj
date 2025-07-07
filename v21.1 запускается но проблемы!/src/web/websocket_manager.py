"""
WebSocket менеджер для Flask-SocketIO
Файл: src/web/websocket_manager.py
"""
import logging
from typing import Dict, Any, Optional
from datetime import datetime

logger = logging.getLogger(__name__)

class FlaskWebSocketManager:
    """Менеджер WebSocket для Flask-SocketIO"""
    
    def __init__(self, socketio, bot_manager=None):
        self.socketio = socketio
        self.bot_manager = bot_manager
        self.is_running = False
        
    def start(self):
        """Запуск менеджера"""
        self.is_running = True
        logger.info("✅ Flask WebSocket Manager запущен")
    
    def stop(self):
        """Остановка менеджера"""
        self.is_running = False
        logger.info("⏹️ Flask WebSocket Manager остановлен")
    
    def emit(self, event: str, data: Dict[str, Any], room=None):
        """Отправка события через SocketIO"""
        if self.socketio:
            self.socketio.emit(event, data, room=room)
    
    def broadcast_update(self, update_type: str, data: Dict[str, Any]):
        """Broadcast обновления всем клиентам"""
        self.emit(update_type, {
            'timestamp': datetime.utcnow().isoformat(),
            'data': data
        })
    
    def get_stats(self) -> Dict[str, Any]:
        """Получение статистики"""
        return {
            'active_connections': 0,  # SocketIO управляет соединениями
            'queue_size': 0,
            'messages_sent': 0,
            'messages_failed': 0
        }

def create_websocket_manager(socketio, bot_manager=None):
    """Создание WebSocket менеджера для Flask"""
    return FlaskWebSocketManager(socketio, bot_manager)