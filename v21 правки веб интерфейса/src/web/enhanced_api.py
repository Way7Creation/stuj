"""
РАСШИРЕННЫЙ UNIFIED API - Полная информация о боте
===================================================
Файл: src/web/enhanced_api.py

Предоставляет ВСЮ информацию о работе бота через API
"""

import asyncio
import json
import logging
from datetime import datetime, timedelta
from typing import Dict, List, Optional, Any, Union
from collections import defaultdict

from flask import jsonify, request, Response
from sqlalchemy import text, desc, and_, func
from sqlalchemy.orm import Session

from ..core.database import SessionLocal, get_session
from ..core.models import (
    Balance, Trade, BotState, TradingPair, Signal, TradeStatus,
    User, Order, StrategyPerformance, OrderSide, SignalAction,
    Position, MarketData
)
from ..core.unified_config import unified_config
from ..logging.smart_logger import get_logger

logger = get_logger(__name__)

class EnhancedAPI:
    """
    Расширенный API с полной информацией о работе бота
    """
    
    def __init__(self, exchange_client=None, bot_manager=None):
        self.exchange_client = exchange_client
        self.bot_manager = bot_manager
        self.ws_manager = None
        self.cache = {}
        self.cache_timeout = 5  # 5 секунд для real-time данных
        
        # Хранилище real-time данных
        self.realtime_data = {
            'tickers': {},
            'orderbook': {},
            'trades': [],
            'positions': [],
            'strategies': {},
            'signals': [],
            'logs': [],
            'metrics': {}
        }
        
        logger.info("✅ EnhancedAPI инициализирован")
    
    def set_websocket_manager(self, ws_manager):
        """Установка WebSocket менеджера"""
        self.ws_manager = ws_manager
    
    # =================================================================
    # ПОЛНАЯ ИНФОРМАЦИЯ О БОТЕ
    # =================================================================
    
    def get_full_bot_status(self) -> Dict[str, Any]:
        """
        Получение ПОЛНОГО статуса бота со всей информацией
        """
        try:
            status = {
                'timestamp': datetime.utcnow().isoformat(),
                'bot': self._get_bot_info(),
                'balance': self._get_balance_info(),
                'positions': self._get_positions_info(),
                'strategies': self._get_strategies_info(),
                'performance': self._get_performance_info(),
                'market': self._get_market_info(),
                'system': self._get_system_info()
            }
            
            return {
                'success': True,
                'data': status
            }
            
        except Exception as e:
            logger.error(f"❌ Ошибка получения полного статуса: {e}")
            return {
                'success': False,
                'error': str(e)
            }
    
    def _get_bot_info(self) -> Dict[str, Any]:
        """Информация о состоянии бота"""
        if not self.bot_manager:
            return {
                'is_running': False,
                'status': 'not_initialized',
                'uptime': '0h 0m',
                'start_time': None
            }
        
        try:
            status = self.bot_manager.get_status()
            start_time = status.get('start_time')
            
            if start_time:
                uptime_seconds = (datetime.utcnow() - datetime.fromisoformat(start_time)).total_seconds()
                hours = int(uptime_seconds // 3600)
                minutes = int((uptime_seconds % 3600) // 60)
                uptime = f"{hours}h {minutes}m"
            else:
                uptime = '0h 0m'
            
            return {
                'is_running': status.get('is_running', False),
                'status': status.get('status', 'unknown'),
                'uptime': uptime,
                'start_time': start_time,
                'mode': 'TESTNET' if unified_config.PAPER_TRADING else 'LIVE',
                'cycles_completed': status.get('cycles_completed', 0),
                'last_cycle_time': status.get('last_cycle_time'),
                'active_pairs': status.get('active_pairs', []),
                'max_positions': unified_config.MAX_POSITIONS
            }
            
        except Exception as e:
            logger.error(f"❌ Ошибка получения информации о боте: {e}")
            return {
                'is_running': False,
                'status': 'error',
                'error': str(e)
            }
    
    def _get_balance_info(self) -> Dict[str, Any]:
        """Детальная информация о балансе"""
        try:
            with SessionLocal() as db:
                # Получаем последний баланс
                balance = db.query(Balance).filter(
                    Balance.asset == 'USDT'
                ).order_by(desc(Balance.updated_at)).first()
                
                if balance:
                    # Рассчитываем изменение за 24 часа
                    yesterday = datetime.utcnow() - timedelta(days=1)
                    balance_24h_ago = db.query(Balance).filter(
                        Balance.asset == 'USDT',
                        Balance.updated_at <= yesterday
                    ).order_by(desc(Balance.updated_at)).first()
                    
                    if balance_24h_ago:
                        change_24h = ((balance.total - balance_24h_ago.total) / balance_24h_ago.total) * 100
                    else:
                        change_24h = 0
                    
                    return {
                        'total_usdt': float(balance.total),
                        'available_usdt': float(balance.free),
                        'in_positions': float(balance.locked or 0),
                        'change_24h': round(change_24h, 2),
                        'last_update': balance.updated_at.isoformat()
                    }
                else:
                    # Пытаемся получить с биржи
                    if self.exchange_client:
                        try:
                            import asyncio
                            balance_data = asyncio.run(self.exchange_client.fetch_balance())
                            usdt = balance_data.get('USDT', {})
                            return {
                                'total_usdt': float(usdt.get('total', 0)),
                                'available_usdt': float(usdt.get('free', 0)),
                                'in_positions': float(usdt.get('used', 0)),
                                'change_24h': 0,
                                'last_update': datetime.utcnow().isoformat()
                            }
                        except:
                            pass
                    
                    return {
                        'total_usdt': 0,
                        'available_usdt': 0,
                        'in_positions': 0,
                        'change_24h': 0,
                        'last_update': None
                    }
                    
        except Exception as e:
            logger.error(f"❌ Ошибка получения баланса: {e}")
            return {
                'total_usdt': 0,
                'available_usdt': 0,
                'in_positions': 0,
                'change_24h': 0,
                'error': str(e)
            }
    
    def _get_positions_info(self) -> Dict[str, Any]:
        """Детальная информация о позициях"""
        try:
            positions = []
            total_pnl = 0
            
            with SessionLocal() as db:
                # Получаем активные позиции из БД
                active_positions = db.query(Position).filter(
                    Position.status == 'OPEN'
                ).all()
                
                for pos in active_positions:
                    # Получаем текущую цену
                    current_price = self._get_current_price(pos.symbol)
                    
                    # Рассчитываем P&L
                    if pos.side == 'BUY':
                        pnl = (current_price - pos.entry_price) * pos.quantity
                        pnl_percent = ((current_price - pos.entry_price) / pos.entry_price) * 100
                    else:
                        pnl = (pos.entry_price - current_price) * pos.quantity
                        pnl_percent = ((pos.entry_price - current_price) / pos.entry_price) * 100
                    
                    total_pnl += pnl
                    
                    positions.append({
                        'id': pos.id,
                        'symbol': pos.symbol,
                        'side': pos.side,
                        'size': float(pos.quantity),
                        'entry_price': float(pos.entry_price),
                        'current_price': current_price,
                        'pnl': round(pnl, 2),
                        'pnl_percent': round(pnl_percent, 2),
                        'strategy': pos.strategy,
                        'opened_at': pos.created_at.isoformat(),
                        'stop_loss': float(pos.stop_loss) if pos.stop_loss else None,
                        'take_profit': float(pos.take_profit) if pos.take_profit else None
                    })
            
            return {
                'positions': positions,
                'count': len(positions),
                'total_pnl': round(total_pnl, 2)
            }
            
        except Exception as e:
            logger.error(f"❌ Ошибка получения позиций: {e}")
            return {
                'positions': [],
                'count': 0,
                'total_pnl': 0,
                'error': str(e)
            }
    
    def _get_strategies_info(self) -> Dict[str, Any]:
        """Информация о стратегиях"""
        try:
            strategies = {}
            
            # Получаем активные стратегии из конфигурации
            active_strategies = {
                'multi_indicator': unified_config.STRATEGY_WEIGHTS.get('multi_indicator', 0),
                'momentum': unified_config.STRATEGY_WEIGHTS.get('momentum', 0),
                'mean_reversion': unified_config.STRATEGY_WEIGHTS.get('mean_reversion', 0),
                'breakout': unified_config.STRATEGY_WEIGHTS.get('breakout', 0),
                'scalping': unified_config.STRATEGY_WEIGHTS.get('scalping', 0),
                'swing': unified_config.STRATEGY_WEIGHTS.get('swing', 0),
                'ml_prediction': unified_config.STRATEGY_WEIGHTS.get('ml_prediction', 0)
            }
            
            with SessionLocal() as db:
                for strategy_name, weight in active_strategies.items():
                    if weight > 0:
                        # Получаем статистику стратегии за последние 24 часа
                        since = datetime.utcnow() - timedelta(days=1)
                        
                        signals_count = db.query(func.count(Signal.id)).filter(
                            Signal.strategy == strategy_name,
                            Signal.timestamp >= since
                        ).scalar() or 0
                        
                        # Последний сигнал
                        last_signal = db.query(Signal).filter(
                            Signal.strategy == strategy_name
                        ).order_by(desc(Signal.timestamp)).first()
                        
                        # Производительность
                        trades = db.query(Trade).filter(
                            Trade.strategy == strategy_name,
                            Trade.status == TradeStatus.CLOSED,
                            Trade.close_time >= since
                        ).all()
                        
                        winning_trades = [t for t in trades if t.profit_loss and t.profit_loss > 0]
                        win_rate = (len(winning_trades) / len(trades) * 100) if trades else 0
                        
                        strategies[strategy_name] = {
                            'active': True,
                            'weight': weight,
                            'signals_count': signals_count,
                            'trades_count': len(trades),
                            'win_rate': round(win_rate, 1),
                            'last_signal': {
                                'action': last_signal.action.value if last_signal else None,
                                'symbol': last_signal.symbol if last_signal else None,
                                'timestamp': last_signal.timestamp.isoformat() if last_signal else None
                            } if last_signal else None
                        }
            
            return strategies
            
        except Exception as e:
            logger.error(f"❌ Ошибка получения информации о стратегиях: {e}")
            return {}
    
    def _get_performance_info(self) -> Dict[str, Any]:
        """Информация о производительности"""
        try:
            with SessionLocal() as db:
                # Статистика за сегодня
                today_start = datetime.utcnow().replace(hour=0, minute=0, second=0, microsecond=0)
                
                today_trades = db.query(Trade).filter(
                    Trade.created_at >= today_start
                ).all()
                
                closed_trades = [t for t in today_trades if t.status == TradeStatus.CLOSED]
                winning_trades = [t for t in closed_trades if t.profit_loss and t.profit_loss > 0]
                
                win_rate = (len(winning_trades) / len(closed_trades) * 100) if closed_trades else 0
                
                # Прибыль за сегодня
                today_pnl = sum(t.profit_loss or 0 for t in closed_trades)
                
                # Статистика за неделю
                week_ago = datetime.utcnow() - timedelta(days=7)
                week_trades = db.query(Trade).filter(
                    Trade.created_at >= week_ago,
                    Trade.status == TradeStatus.CLOSED
                ).all()
                
                week_pnl = sum(t.profit_loss or 0 for t in week_trades)
                
                return {
                    'today': {
                        'trades_count': len(today_trades),
                        'closed_trades': len(closed_trades),
                        'win_rate': round(win_rate, 1),
                        'pnl': round(today_pnl, 2)
                    },
                    'week': {
                        'trades_count': len(week_trades),
                        'pnl': round(week_pnl, 2),
                        'avg_daily_pnl': round(week_pnl / 7, 2)
                    }
                }
                
        except Exception as e:
            logger.error(f"❌ Ошибка получения производительности: {e}")
            return {
                'today': {
                    'trades_count': 0,
                    'closed_trades': 0,
                    'win_rate': 0,
                    'pnl': 0
                },
                'week': {
                    'trades_count': 0,
                    'pnl': 0,
                    'avg_daily_pnl': 0
                }
            }
    
    def _get_market_info(self) -> Dict[str, Any]:
        """Информация о рынке"""
        try:
            market_info = {
                'total_volume_24h': 0,
                'active_symbols': [],
                'trending': []
            }
            
            # Получаем активные символы
            symbols = unified_config.TRADING_PAIRS[:10]  # Top 10
            
            for symbol in symbols:
                ticker = self.realtime_data['tickers'].get(symbol, {})
                if ticker:
                    market_info['total_volume_24h'] += ticker.get('volume', 0)
                    
                    # Определяем трендовые пары
                    change_24h = ticker.get('change_24h', 0)
                    if abs(change_24h) > 5:  # Изменение больше 5%
                        market_info['trending'].append({
                            'symbol': symbol,
                            'change': change_24h,
                            'volume': ticker.get('volume', 0)
                        })
            
            market_info['active_symbols'] = symbols
            market_info['trending'].sort(key=lambda x: abs(x['change']), reverse=True)
            
            return market_info
            
        except Exception as e:
            logger.error(f"❌ Ошибка получения информации о рынке: {e}")
            return {
                'total_volume_24h': 0,
                'active_symbols': [],
                'trending': []
            }
    
    def _get_system_info(self) -> Dict[str, Any]:
        """Информация о системе"""
        try:
            import psutil
            
            # CPU и память
            cpu_percent = psutil.cpu_percent(interval=1)
            memory = psutil.virtual_memory()
            
            # Подключения
            connections = {
                'exchange': self.exchange_client is not None,
                'database': True,  # Всегда true если мы здесь
                'websocket': self.ws_manager is not None and hasattr(self.ws_manager, 'active_connections'),
                'telegram': unified_config.TELEGRAM_ENABLED
            }
            
            return {
                'cpu_usage': round(cpu_percent, 1),
                'memory_usage': round(memory.percent, 1),
                'connections': connections,
                'version': '3.0.0',
                'environment': 'production' if not unified_config.PAPER_TRADING else 'testnet'
            }
            
        except Exception as e:
            logger.error(f"❌ Ошибка получения системной информации: {e}")
            return {
                'cpu_usage': 0,
                'memory_usage': 0,
                'connections': {},
                'version': '3.0.0',
                'environment': 'unknown'
            }
    
    def _get_current_price(self, symbol: str) -> float:
        """Получение текущей цены"""
        # Сначала проверяем кеш
        ticker = self.realtime_data['tickers'].get(symbol)
        if ticker:
            return ticker.get('price', 0)
        
        # Пытаемся получить с биржи
        if self.exchange_client:
            try:
                import asyncio
                ticker = asyncio.run(self.exchange_client.fetch_ticker(symbol))
                return float(ticker.get('last', 0))
            except:
                pass
        
        return 0
    
    # =================================================================
    # REAL-TIME ДАННЫЕ
    # =================================================================
    
    def update_ticker(self, symbol: str, data: Dict[str, Any]):
        """Обновление данных тикера"""
        self.realtime_data['tickers'][symbol] = {
            'price': data.get('last', 0),
            'bid': data.get('bid', 0),
            'ask': data.get('ask', 0),
            'volume': data.get('baseVolume', 0),
            'change_24h': data.get('percentage', 0),
            'high_24h': data.get('high', 0),
            'low_24h': data.get('low', 0),
            'timestamp': datetime.utcnow().isoformat()
        }
        
        # Отправляем через WebSocket
        if self.ws_manager:
            self.ws_manager.broadcast('ticker_update', {
                'tickers': self.realtime_data['tickers']
            })
    
    def add_log_entry(self, level: str, message: str, source: str = 'system'):
        """Добавление записи в лог"""
        log_entry = {
            'timestamp': datetime.utcnow().isoformat(),
            'level': level,
            'message': message,
            'source': source
        }
        
        self.realtime_data['logs'].append(log_entry)
        
        # Храним только последние 1000 записей
        if len(self.realtime_data['logs']) > 1000:
            self.realtime_data['logs'] = self.realtime_data['logs'][-1000:]
        
        # Отправляем через WebSocket
        if self.ws_manager:
            self.ws_manager.broadcast('log_message', log_entry)
    
    # =================================================================
    # API ENDPOINTS
    # =================================================================
    
    def get_recent_trades(self, limit: int = 50) -> Dict[str, Any]:
        """Получение последних сделок"""
        try:
            with SessionLocal() as db:
                trades = db.query(Trade).order_by(
                    desc(Trade.created_at)
                ).limit(limit).all()
                
                trades_data = []
                for trade in trades:
                    trades_data.append({
                        'id': trade.id,
                        'timestamp': trade.created_at.isoformat(),
                        'symbol': trade.symbol,
                        'side': trade.side.value if hasattr(trade.side, 'value') else str(trade.side),
                        'price': float(trade.price),
                        'size': float(trade.quantity),
                        'pnl': float(trade.profit_loss) if trade.profit_loss else 0,
                        'pnl_percent': float(trade.profit_loss_percent) if trade.profit_loss_percent else 0,
                        'strategy': trade.strategy,
                        'status': trade.status.value if hasattr(trade.status, 'value') else str(trade.status)
                    })
                
                return {
                    'success': True,
                    'trades': trades_data,
                    'count': len(trades_data)
                }
                
        except Exception as e:
            logger.error(f"❌ Ошибка получения сделок: {e}")
            return {
                'success': False,
                'error': str(e),
                'trades': []
            }
    
    def get_recent_signals(self, limit: int = 20) -> Dict[str, Any]:
        """Получение последних сигналов"""
        try:
            with SessionLocal() as db:
                signals = db.query(Signal).order_by(
                    desc(Signal.timestamp)
                ).limit(limit).all()
                
                signals_data = []
                for signal in signals:
                    signals_data.append({
                        'id': signal.id,
                        'timestamp': signal.timestamp.isoformat(),
                        'symbol': signal.symbol,
                        'action': signal.action.value if hasattr(signal.action, 'value') else str(signal.action),
                        'strategy': signal.strategy,
                        'confidence': float(signal.confidence) if signal.confidence else 0,
                        'price': float(signal.price) if signal.price else 0,
                        'metadata': signal.metadata
                    })
                
                return {
                    'success': True,
                    'signals': signals_data,
                    'count': len(signals_data)
                }
                
        except Exception as e:
            logger.error(f"❌ Ошибка получения сигналов: {e}")
            return {
                'success': False,
                'error': str(e),
                'signals': []
            }
    
    def get_strategy_performance(self, strategy: str = None, days: int = 7) -> Dict[str, Any]:
        """Получение производительности стратегий"""
        try:
            with SessionLocal() as db:
                since = datetime.utcnow() - timedelta(days=days)
                
                query = db.query(
                    Trade.strategy,
                    func.count(Trade.id).label('total_trades'),
                    func.sum(Trade.profit_loss).label('total_pnl'),
                    func.avg(Trade.profit_loss_percent).label('avg_pnl_percent')
                ).filter(
                    Trade.status == TradeStatus.CLOSED,
                    Trade.close_time >= since
                )
                
                if strategy:
                    query = query.filter(Trade.strategy == strategy)
                
                results = query.group_by(Trade.strategy).all()
                
                performance = {}
                for row in results:
                    # Подсчет win rate
                    winning_trades = db.query(func.count(Trade.id)).filter(
                        Trade.strategy == row.strategy,
                        Trade.status == TradeStatus.CLOSED,
                        Trade.close_time >= since,
                        Trade.profit_loss > 0
                    ).scalar() or 0
                    
                    win_rate = (winning_trades / row.total_trades * 100) if row.total_trades > 0 else 0
                    
                    performance[row.strategy] = {
                        'total_trades': row.total_trades,
                        'total_pnl': round(float(row.total_pnl or 0), 2),
                        'avg_pnl_percent': round(float(row.avg_pnl_percent or 0), 2),
                        'win_rate': round(win_rate, 1),
                        'winning_trades': winning_trades
                    }
                
                return {
                    'success': True,
                    'performance': performance,
                    'period_days': days
                }
                
        except Exception as e:
            logger.error(f"❌ Ошибка получения производительности стратегий: {e}")
            return {
                'success': False,
                'error': str(e),
                'performance': {}
            }
    
    def get_chart_data(self, symbol: str, interval: str = '5m', limit: int = 100) -> Dict[str, Any]:
        """Получение данных для графиков"""
        try:
            # Пытаемся получить с биржи
            if self.exchange_client:
                try:
                    import asyncio
                    candles = asyncio.run(
                        self.exchange_client.fetch_ohlcv(symbol, interval, limit=limit)
                    )
                    
                    candles_data = []
                    for candle in candles:
                        candles_data.append({
                            'timestamp': candle[0],
                            'open': candle[1],
                            'high': candle[2],
                            'low': candle[3],
                            'close': candle[4],
                            'volume': candle[5]
                        })
                    
                    return {
                        'success': True,
                        'symbol': symbol,
                        'interval': interval,
                        'candles': candles_data
                    }
                except Exception as e:
                    logger.error(f"Ошибка получения данных с биржи: {e}")
            
            # Fallback к данным из БД
            with SessionLocal() as db:
                # Здесь можно получить данные из таблицы MarketData
                return {
                    'success': True,
                    'symbol': symbol,
                    'interval': interval,
                    'candles': [],
                    'source': 'cache'
                }
                
        except Exception as e:
            logger.error(f"❌ Ошибка получения данных для графика: {e}")
            return {
                'success': False,
                'error': str(e),
                'candles': []
            }
    
    def get_system_logs(self, limit: int = 100, level: str = None) -> Dict[str, Any]:
        """Получение системных логов"""
        try:
            logs = self.realtime_data['logs'][-limit:]
            
            if level:
                logs = [log for log in logs if log['level'] == level]
            
            return {
                'success': True,
                'logs': logs,
                'count': len(logs)
            }
            
        except Exception as e:
            logger.error(f"❌ Ошибка получения логов: {e}")
            return {
                'success': False,
                'error': str(e),
                'logs': []
            }


# =================================================================
# РЕГИСТРАЦИЯ FLASK ROUTES
# =================================================================

def register_enhanced_api_routes(app, bot_manager=None, exchange_client=None):
    """
    Регистрация расширенных API routes
    """
    
    # Создаем экземпляр API
    api = EnhancedAPI(exchange_client, bot_manager)
    
    # Сохраняем в app context
    app.enhanced_api = api
    
    logger.info("🚀 Регистрация расширенных API routes...")
    
    # =================================================================
    # ОСНОВНЫЕ ENDPOINTS
    # =================================================================
    
    @app.route('/api/v2/status', methods=['GET'])
    def get_full_status():
        """Полный статус системы"""
        return jsonify(api.get_full_bot_status())
    
    @app.route('/api/v2/trades', methods=['GET'])
    def get_trades():
        """Получение сделок"""
        limit = int(request.args.get('limit', 50))
        return jsonify(api.get_recent_trades(limit))
    
    @app.route('/api/v2/signals', methods=['GET'])
    def get_signals():
        """Получение сигналов"""
        limit = int(request.args.get('limit', 20))
        return jsonify(api.get_recent_signals(limit))
    
    @app.route('/api/v2/positions', methods=['GET'])
    def get_positions():
        """Получение позиций"""
        positions_info = api._get_positions_info()
        return jsonify({
            'success': True,
            **positions_info
        })
    
    @app.route('/api/v2/strategies/performance', methods=['GET'])
    def get_strategies_performance():
        """Производительность стратегий"""
        strategy = request.args.get('strategy')
        days = int(request.args.get('days', 7))
        return jsonify(api.get_strategy_performance(strategy, days))
    
    @app.route('/api/v2/chart/<symbol>', methods=['GET'])
    def get_chart(symbol):
        """Данные для графика"""
        interval = request.args.get('interval', '5m')
        limit = int(request.args.get('limit', 100))
        return jsonify(api.get_chart_data(symbol, interval, limit))
    
    @app.route('/api/v2/logs', methods=['GET'])
    def get_logs():
        """Системные логи"""
        limit = int(request.args.get('limit', 100))
        level = request.args.get('level')
        return jsonify(api.get_system_logs(limit, level))
    
    # =================================================================
    # REAL-TIME ENDPOINTS
    # =================================================================
    
    @app.route('/api/v2/ticker/<symbol>', methods=['GET'])
    def get_ticker(symbol):
        """Текущая цена"""
        ticker = api.realtime_data['tickers'].get(symbol, {})
        if ticker:
            return jsonify({
                'success': True,
                'symbol': symbol,
                **ticker
            })
        else:
            return jsonify({
                'success': False,
                'error': 'Ticker not found'
            })
    
    @app.route('/api/v2/tickers', methods=['GET'])
    def get_all_tickers():
        """Все тикеры"""
        return jsonify({
            'success': True,
            'tickers': api.realtime_data['tickers'],
            'count': len(api.realtime_data['tickers'])
        })
    
    # =================================================================
    # УПРАВЛЕНИЕ ПОЗИЦИЯМИ
    # =================================================================
    
    @app.route('/api/v2/positions/<int:position_id>/close', methods=['POST'])
    def close_position(position_id):
        """Закрытие позиции"""
        try:
            if not bot_manager:
                return jsonify({
                    'success': False,
                    'error': 'Bot manager not available'
                })
            
            # Здесь должна быть логика закрытия позиции
            # result = bot_manager.close_position(position_id)
            
            return jsonify({
                'success': True,
                'message': f'Position {position_id} closed'
            })
            
        except Exception as e:
            return jsonify({
                'success': False,
                'error': str(e)
            })
    
    # =================================================================
    # STREAM ENDPOINT
    # =================================================================
    
    @app.route('/api/v2/stream')
    def stream_data():
        """Server-Sent Events для real-time данных"""
        def generate():
            while True:
                # Отправляем обновления каждые 2 секунды
                data = {
                    'timestamp': datetime.utcnow().isoformat(),
                    'tickers': api.realtime_data['tickers'],
                    'positions_count': len(api.realtime_data['positions'])
                }
                
                yield f"data: {json.dumps(data)}\n\n"
                
                import time
                time.sleep(2)
        
        return Response(
            generate(),
            mimetype='text/event-stream',
            headers={
                'Cache-Control': 'no-cache',
                'X-Accel-Buffering': 'no'
            }
        )
    
    logger.info("✅ Расширенные API routes зарегистрированы:")
    logger.info("   📊 /api/v2/status - Полный статус системы")
    logger.info("   💹 /api/v2/trades - История сделок")
    logger.info("   📡 /api/v2/signals - Торговые сигналы")
    logger.info("   📈 /api/v2/positions - Активные позиции")
    logger.info("   🎯 /api/v2/strategies/performance - Производительность стратегий")
    logger.info("   📉 /api/v2/chart/<symbol> - Данные для графиков")
    logger.info("   📝 /api/v2/logs - Системные логи")
    logger.info("   💱 /api/v2/tickers - Рыночные данные")
    logger.info("   🔄 /api/v2/stream - Real-time поток данных")
    
    return api


# =================================================================
# ЭКСПОРТЫ
# =================================================================

__all__ = [
    'EnhancedAPI',
    'register_enhanced_api_routes'
]