"""
WebSocket endpoint и менеджер для real-time обновлений
Файл: src/web/websocket.py
"""
from fastapi import WebSocket, WebSocketDisconnect
from typing import List, Dict, Any, Optional
import asyncio
import json
import logging
from datetime import datetime
from collections import defaultdict

logger = logging.getLogger(__name__)

class EnhancedWebSocketManager:
    """Улучшенный менеджер WebSocket соединений"""
    
    def __init__(self, socketio=None, bot_manager=None):
        self.active_connections: List[WebSocket] = []
        self.socketio = socketio
        self.bot_manager = bot_manager
        self._broadcast_task: Optional[asyncio.Task] = None
        self.is_running = False
        
        # Статистика
        self.stats = {
            'total_connections': 0,
            'messages_sent': 0,
            'messages_failed': 0,
            'queue_size': 0,
            'active_connections': 0
        }
        
        # Кеш последних данных
        self.last_data = {
            'bot_status': {},
            'balance': {},
            'positions': [],
            'trades': [],
            'signals': []
        }
        
        logger.info("✅ EnhancedWebSocketManager инициализирован")
    
    def start(self):
        """Запуск менеджера"""
        if not self.is_running:
            self.is_running = True
            if asyncio.get_event_loop().is_running():
                asyncio.create_task(self._broadcast_loop())
            logger.info("🚀 WebSocket менеджер запущен")
    
    def stop(self):
        """Остановка менеджера"""
        self.is_running = False
        if self._broadcast_task:
            self._broadcast_task.cancel()
        logger.info("⏹️ WebSocket менеджер остановлен")
    
    async def connect(self, websocket: WebSocket):
        """Подключение нового клиента"""
        await websocket.accept()
        self.active_connections.append(websocket)
        self.stats['total_connections'] += 1
        self.stats['active_connections'] = len(self.active_connections)
        
        logger.info(f"🔌 WebSocket подключен. Активных: {len(self.active_connections)}")
        
        # Отправляем начальные данные
        await self._send_initial_data(websocket)
    
    def disconnect(self, websocket: WebSocket):
        """Отключение клиента"""
        if websocket in self.active_connections:
            self.active_connections.remove(websocket)
            self.stats['active_connections'] = len(self.active_connections)
        
        logger.info(f"🔌 WebSocket отключен. Активных: {len(self.active_connections)}")
    
    async def _send_initial_data(self, websocket: WebSocket):
        """Отправка начальных данных при подключении"""
        try:
            # Получаем текущий статус от бота
            if self.bot_manager:
                status = self.bot_manager.get_status()
                balance = self.bot_manager.get_balance_info()
                positions = self.bot_manager.get_positions_info()
                
                initial_data = {
                    'type': 'initial',
                    'timestamp': datetime.utcnow().isoformat(),
                    'data': {
                        'bot_status': status,
                        'balance': balance,
                        'positions': positions
                    }
                }
            else:
                initial_data = {
                    'type': 'initial',
                    'timestamp': datetime.utcnow().isoformat(),
                    'data': self.last_data
                }
            
            await websocket.send_json(initial_data)
            self.stats['messages_sent'] += 1
            
        except Exception as e:
            logger.error(f"❌ Ошибка отправки начальных данных: {e}")
            self.stats['messages_failed'] += 1
    
    async def broadcast(self, message_type: str, data: Dict[str, Any]):
        """Отправка сообщения всем подключенным клиентам"""
        if not self.active_connections:
            return
        
        message = {
            'type': message_type,
            'timestamp': datetime.utcnow().isoformat(),
            'data': data
        }
        
        # Сохраняем в кеш
        if message_type == 'bot_status':
            self.last_data['bot_status'] = data
        elif message_type == 'balance_update':
            self.last_data['balance'] = data
        elif message_type == 'position_update':
            self.last_data['positions'] = data.get('positions', [])
        elif message_type == 'new_trade':
            self.last_data['trades'].append(data)
            if len(self.last_data['trades']) > 100:
                self.last_data['trades'] = self.last_data['trades'][-100:]
        
        # Отправляем через WebSocket
        disconnected = []
        for connection in self.active_connections:
            try:
                await connection.send_json(message)
                self.stats['messages_sent'] += 1
            except Exception as e:
                logger.error(f"❌ Ошибка отправки: {e}")
                disconnected.append(connection)
                self.stats['messages_failed'] += 1
        
        # Удаляем отключенные соединения
        for connection in disconnected:
            self.disconnect(connection)
        
        # Отправляем через SocketIO если доступен
        if self.socketio:
            try:
                self.socketio.emit(message_type, data)
            except Exception as e:
                logger.error(f"❌ Ошибка SocketIO: {e}")
    
    async def _broadcast_loop(self):
        """Основной цикл отправки обновлений"""
        logger.info("🔄 Запущен broadcast loop")
        
        while self.is_running:
            try:
                if self.bot_manager:
                    # Получаем обновленные данные
                    status = self.bot_manager.get_status()
                    await self.broadcast('bot_status', status)
                    
                    # Баланс
                    balance = self.bot_manager.get_balance_info()
                    if balance:
                        await self.broadcast('balance_update', balance)
                    
                    # Позиции
                    positions = self.bot_manager.get_positions_info()
                    if positions:
                        await self.broadcast('position_update', positions)
                
                await asyncio.sleep(5)  # Обновление каждые 5 секунд
                
            except Exception as e:
                logger.error(f"❌ Ошибка в broadcast loop: {e}")
                await asyncio.sleep(10)
    
    def get_stats(self) -> Dict[str, Any]:
        """Получение статистики"""
        return {
            **self.stats,
            'uptime': 'running' if self.is_running else 'stopped'
        }

def create_websocket_manager(socketio=None, bot_manager=None) -> EnhancedWebSocketManager:
    """Фабрика для создания WebSocket менеджера"""
    return EnhancedWebSocketManager(socketio, bot_manager)

# Глобальный экземпляр для FastAPI WebSocket
ws_manager = None

async def websocket_endpoint(websocket: WebSocket):
    """WebSocket endpoint для FastAPI"""
    global ws_manager
    
    if not ws_manager:
        ws_manager = EnhancedWebSocketManager()
    
    await ws_manager.connect(websocket)
    
    try:
        while True:
            # Ждем сообщения от клиента
            data = await websocket.receive_text()
            
            # Обрабатываем команды
            try:
                message = json.loads(data)
                
                if message.get('type') == 'ping':
                    await websocket.send_json({'type': 'pong'})
                elif message.get('type') == 'subscribe':
                    # Можно добавить логику подписок
                    await websocket.send_json({
                        'type': 'subscribed',
                        'topics': message.get('topics', [])
                    })
                    
            except json.JSONDecodeError:
                logger.warning(f"Неверный формат сообщения: {data}")
                
    except WebSocketDisconnect:
        ws_manager.disconnect(websocket)
    except Exception as e:
        logger.error(f"❌ WebSocket ошибка: {e}")
        ws_manager.disconnect(websocket)

__all__ = ['EnhancedWebSocketManager', 'create_websocket_manager', 'websocket_endpoint', 'ws_manager']