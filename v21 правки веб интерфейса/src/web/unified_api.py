"""
Объединенный API для всех функций веб-интерфейса
Файл: src/web/unified_api.py
"""
import logging
from datetime import datetime, timedelta
from typing import Dict, Any, List, Optional
from flask import jsonify, request
from sqlalchemy import func, desc
from sqlalchemy.orm import Session


from ..core.database import SessionLocal
from ..core.models import (
    Trade, Signal, Balance, Position, MarketData,
    TradeStatus, OrderSide, SignalAction
)
from ..core.unified_config import unified_config
from ..logging.smart_logger import get_logger

logger = get_logger(__name__)

def register_unified_api_routes(app, bot_manager=None, exchange_client=None):
    """
    Регистрация всех API роутов для веб-интерфейса
    """
    logger.info("🚀 Регистрация unified API routes...")
    
    # ===== CHARTS API =====
    
    @app.route('/api/charts/candles/<symbol>')
    def get_candles(symbol):
        """Получение свечей для графика"""
        try:
            interval = request.args.get('interval', '5m')
            limit = int(request.args.get('limit', 100))
            
            if exchange_client:
                # Получаем с биржи
                candles = exchange_client.fetch_ohlcv(symbol, interval, limit=limit)
                
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
                
                return jsonify({
                    'success': True,
                    'symbol': symbol,
                    'interval': interval,
                    'candles': candles_data
                })
            else:
                # Демо данные
                import random
                base_price = 50000 if symbol == 'BTCUSDT' else 3000
                
                candles_data = []
                for i in range(limit):
                    timestamp = int((datetime.utcnow() - timedelta(minutes=i*5)).timestamp() * 1000)
                    open_price = base_price + random.uniform(-1000, 1000)
                    close_price = open_price + random.uniform(-100, 100)
                    high = max(open_price, close_price) + random.uniform(0, 50)
                    low = min(open_price, close_price) - random.uniform(0, 50)
                    
                    candles_data.append({
                        'timestamp': timestamp,
                        'open': open_price,
                        'high': high,
                        'low': low,
                        'close': close_price,
                        'volume': random.uniform(100, 1000)
                    })
                
                return jsonify({
                    'success': True,
                    'symbol': symbol,
                    'interval': interval,
                    'candles': candles_data[::-1],
                    'source': 'demo'
                })
                
        except Exception as e:
            logger.error(f"Ошибка получения свечей: {e}")
            return jsonify({
                'success': False,
                'error': str(e)
            }), 500
    
    @app.route('/api/charts/indicators/<symbol>')
    def get_indicators(symbol):
        """Получение индикаторов для графика"""
        try:
            indicators = request.args.get('indicators', 'sma,rsi').split(',')
            
            # Здесь должен быть расчет индикаторов
            # Для примера возвращаем демо данные
            
            data = {
                'success': True,
                'symbol': symbol,
                'indicators': {}
            }
            
            if 'sma' in indicators:
                data['indicators']['sma'] = {
                    'period': 20,
                    'values': [50000 + i*10 for i in range(50)]
                }
            
            if 'rsi' in indicators:
                data['indicators']['rsi'] = {
                    'period': 14,
                    'values': [50 + i % 30 for i in range(50)]
                }
            
            return jsonify(data)
            
        except Exception as e:
            logger.error(f"Ошибка получения индикаторов: {e}")
            return jsonify({
                'success': False,
                'error': str(e)
            }), 500
    
    # ===== TRADING API =====
    
    @app.route('/api/trades')
    def get_trades():
        """Получение списка сделок"""
        with SessionLocal() as db:
            try:
                page = int(request.args.get('page', 1))
                limit = int(request.args.get('limit', 20))
                offset = (page - 1) * limit
                
                # Получаем сделки
                trades = db.query(Trade).order_by(
                    desc(Trade.created_at)
                ).offset(offset).limit(limit).all()
                
                # Общее количество
                total = db.query(func.count(Trade.id)).scalar()
                
                trades_data = []
                for trade in trades:
                    trades_data.append({
                        'id': trade.id,
                        'timestamp': trade.created_at.isoformat() if trade.created_at else None,
                        'symbol': trade.symbol,
                        'side': trade.side.value if hasattr(trade.side, 'value') else str(trade.side),
                        'price': float(trade.price) if trade.price else 0,
                        'size': float(trade.quantity) if trade.quantity else 0,
                        'pnl': float(trade.profit_loss) if trade.profit_loss else 0,
                        'pnl_percent': float(trade.profit_loss_percent) if trade.profit_loss_percent else 0,
                        'strategy': trade.strategy,
                        'status': trade.status.value if hasattr(trade.status, 'value') else str(trade.status)
                    })
                
                return jsonify({
                    'success': True,
                    'trades': trades_data,
                    'pagination': {
                        'page': page,
                        'limit': limit,
                        'total': total,
                        'pages': (total + limit - 1) // limit
                    }
                })
                
            except Exception as e:
                logger.error(f"Ошибка получения сделок: {e}")
                return jsonify({
                    'success': False,
                    'error': str(e)
                }), 500
    
    @app.route('/api/bot/positions')
    def get_bot_positions():
        """Получение активных позиций бота"""
        try:
            if bot_manager:
                positions_info = bot_manager.get_positions_info()
                return jsonify({
                    'success': True,
                    **positions_info
                })
            else:
                # Демо данные
                return jsonify({
                    'success': True,
                    'positions': [],
                    'count': 0,
                    'total_pnl': 0,
                    'source': 'demo'
                })
                
        except Exception as e:
            logger.error(f"Ошибка получения позиций: {e}")
            return jsonify({
                'success': False,
                'error': str(e)
            }), 500
    
    @app.route('/api/positions/<int:position_id>/close', methods=['POST'])
    def close_position(position_id):
        """Закрытие позиции"""
        try:
            if bot_manager and hasattr(bot_manager, 'close_position'):
                result = bot_manager.close_position(position_id)
                return jsonify({
                    'success': result,
                    'message': 'Position closed' if result else 'Failed to close position'
                })
            else:
                return jsonify({
                    'success': False,
                    'error': 'Bot manager not available'
                }), 503
                
        except Exception as e:
            logger.error(f"Ошибка закрытия позиции: {e}")
            return jsonify({
                'success': False,
                'error': str(e)
            }), 500
    
    # ===== SOCIAL TRADING API =====
    
    @app.route('/api/social/sentiment')
    def get_social_sentiment():
        """Получение социального настроения"""
        try:
            symbol = request.args.get('symbol', 'BTCUSDT')
            
            # Здесь должен быть анализ социальных сетей
            # Для примера возвращаем демо данные
            
            return jsonify({
                'success': True,
                'symbol': symbol,
                'sentiment': {
                    'overall': 0.65,  # от -1 до 1
                    'twitter': 0.7,
                    'reddit': 0.6,
                    'news': 0.65
                },
                'volume': {
                    'mentions': 1234,
                    'increase_24h': 15.5
                },
                'timestamp': datetime.utcnow().isoformat()
            })
            
        except Exception as e:
            logger.error(f"Ошибка получения sentiment: {e}")
            return jsonify({
                'success': False,
                'error': str(e)
            }), 500
    
    @app.route('/api/social/trending')
    def get_trending_symbols():
        """Получение трендовых символов"""
        try:
            # Демо данные
            trending = [
                {'symbol': 'BTCUSDT', 'mentions': 5432, 'change': 25.5},
                {'symbol': 'ETHUSDT', 'mentions': 3210, 'change': 18.2},
                {'symbol': 'SOLUSDT', 'mentions': 2109, 'change': 45.8},
                {'symbol': 'BNBUSDT', 'mentions': 1876, 'change': -5.3},
                {'symbol': 'XRPUSDT', 'mentions': 1654, 'change': 12.1}
            ]
            
            return jsonify({
                'success': True,
                'trending': trending,
                'updated_at': datetime.utcnow().isoformat()
            })
            
        except Exception as e:
            logger.error(f"Ошибка получения трендов: {e}")
            return jsonify({
                'success': False,
                'error': str(e)
            }), 500
    
    # ===== BOT CONTROL API =====
    
    @app.route('/api/bot/start', methods=['POST'])
    def start_bot():
        """Запуск бота"""
        try:
            if bot_manager:
                success, message = bot_manager.start()
                
                # Отправляем обновление через WebSocket
                if app.enhanced_api and hasattr(app.enhanced_api, 'ws_manager'):
                    app.enhanced_api.ws_manager.broadcast('bot_started', {
                        'status': 'started',
                        'message': message
                    })
                
                return jsonify({
                    'success': success,
                    'message': message
                })
            else:
                return jsonify({
                    'success': False,
                    'error': 'Bot manager not initialized'
                }), 503
                
        except Exception as e:
            logger.error(f"Ошибка запуска бота: {e}")
            return jsonify({
                'success': False,
                'error': str(e)
            }), 500
    
    @app.route('/api/bot/stop', methods=['POST'])
    def stop_bot():
        """Остановка бота"""
        try:
            if bot_manager:
                success, message = bot_manager.stop()
                
                # Отправляем обновление через WebSocket
                if app.enhanced_api and hasattr(app.enhanced_api, 'ws_manager'):
                    app.enhanced_api.ws_manager.broadcast('bot_stopped', {
                        'status': 'stopped',
                        'message': message
                    })
                
                return jsonify({
                    'success': success,
                    'message': message
                })
            else:
                return jsonify({
                    'success': False,
                    'error': 'Bot manager not initialized'
                }), 503
                
        except Exception as e:
            logger.error(f"Ошибка остановки бота: {e}")
            return jsonify({
                'success': False,
                'error': str(e)
            }), 500
    
    # ===== BALANCE API =====
    
    @app.route('/api/balance')
    def get_balance():
        """Получение баланса"""
        try:
            if bot_manager:
                balance_info = bot_manager.get_balance_info()
                return jsonify({
                    'success': True,
                    **balance_info
                })
            else:
                # Демо данные
                return jsonify({
                    'success': True,
                    'total_usdt': 10000,
                    'available_usdt': 9500,
                    'in_positions': 500,
                    'change_24h': 2.5,
                    'source': 'demo'
                })
                
        except Exception as e:
            logger.error(f"Ошибка получения баланса: {e}")
            return jsonify({
                'success': False,
                'error': str(e)
            }), 500
    
    # ===== ANALYTICS API =====
    
    @app.route('/api/analytics/performance')
    def get_performance():
        """Получение производительности"""
        with SessionLocal() as db:
            try:
                days = int(request.args.get('days', 7))
                since = datetime.utcnow() - timedelta(days=days)
                
                # Статистика по сделкам
                trades = db.query(Trade).filter(
                    Trade.created_at >= since,
                    Trade.status == TradeStatus.CLOSED
                ).all()
                
                total_trades = len(trades)
                profitable_trades = len([t for t in trades if t.profit_loss and t.profit_loss > 0])
                total_pnl = sum(t.profit_loss or 0 for t in trades)
                
                # Статистика по дням
                daily_stats = []
                for i in range(days):
                    day_start = datetime.utcnow().replace(hour=0, minute=0, second=0) - timedelta(days=i)
                    day_end = day_start + timedelta(days=1)
                    
                    day_trades = [t for t in trades if day_start <= t.created_at < day_end]
                    day_pnl = sum(t.profit_loss or 0 for t in day_trades)
                    
                    daily_stats.append({
                        'date': day_start.strftime('%Y-%m-%d'),
                        'trades': len(day_trades),
                        'pnl': round(day_pnl, 2)
                    })
                
                return jsonify({
                    'success': True,
                    'period_days': days,
                    'summary': {
                        'total_trades': total_trades,
                        'profitable_trades': profitable_trades,
                        'win_rate': round(profitable_trades / total_trades * 100, 1) if total_trades > 0 else 0,
                        'total_pnl': round(total_pnl, 2)
                    },
                    'daily': daily_stats[::-1]
                })
                
            except Exception as e:
                logger.error(f"Ошибка получения производительности: {e}")
                return jsonify({
                    'success': False,
                    'error': str(e)
                }), 500
    
    # ===== SYSTEM API =====
    
    @app.route('/api/system/stats')
    def get_system_stats():
        """Получение системной статистики"""
        try:
            stats = {
                'bot_manager': bot_manager is not None,
                'exchange_client': exchange_client is not None,
                'websocket': hasattr(app, 'enhanced_api') and app.enhanced_api is not None
            }
            
            if bot_manager:
                bot_status = bot_manager.get_status()
                stats['bot'] = {
                    'is_running': bot_status.get('is_running', False),
                    'uptime': bot_status.get('uptime', '0h 0m'),
                    'cycles': bot_status.get('cycles_completed', 0)
                }
            
            return jsonify({
                'success': True,
                'stats': stats,
                'timestamp': datetime.utcnow().isoformat()
            })
            
        except Exception as e:
            logger.error(f"Ошибка получения статистики: {e}")
            return jsonify({
                'success': False,
                'error': str(e)
            }), 500
            
    @app.route('/api/trades/recent')
    def get_recent_trades():
        """Получение последних сделок"""
        try:
            limit = int(request.args.get('limit', 10))
            
            # Если есть данные в БД
            with SessionLocal() as db:
                trades = db.query(Trade).order_by(
                    desc(Trade.created_at)
                ).limit(limit).all()
                
                if trades:
                    trades_data = []
                    for trade in trades:
                        trades_data.append({
                            'id': trade.id,
                            'symbol': trade.symbol,
                            'side': trade.side.value if hasattr(trade.side, 'value') else str(trade.side),
                            'price': float(trade.price) if trade.price else 0,
                            'quantity': float(trade.quantity) if trade.quantity else 0,
                            'profit': float(trade.profit_loss) if trade.profit_loss else 0,
                            'timestamp': trade.created_at.isoformat() if trade.created_at else None
                        })
                    
                    return jsonify({
                        'success': True,
                        'trades': trades_data
                    })
            
            # Демо данные
            demo_trades = [
                {
                    'id': 1,
                    'symbol': 'BTCUSDT',
                    'side': 'BUY',
                    'price': 67500.0,
                    'quantity': 0.01,
                    'profit': 25.5,
                    'timestamp': (datetime.utcnow() - timedelta(hours=1)).isoformat()
                },
                {
                    'id': 2,
                    'symbol': 'ETHUSDT',
                    'side': 'SELL',
                    'price': 3450.0,
                    'quantity': 0.1,
                    'profit': -12.3,
                    'timestamp': (datetime.utcnow() - timedelta(hours=2)).isoformat()
                }
            ]
            
            return jsonify({
                'success': True,
                'trades': demo_trades[:limit]
            })
            
        except Exception as e:
            logger.error(f"Ошибка получения последних сделок: {e}")
            return jsonify({
                'success': False,
                'error': str(e)
            }), 500
    
    @app.route('/api/ticker/<symbol>')
    def get_ticker(symbol):
        """Получение текущей цены символа"""
        try:
            if exchange_client:
                import asyncio
                try:
                    ticker = asyncio.run(exchange_client.fetch_ticker(symbol))
                    return jsonify({
                        'success': True,
                        'symbol': symbol,
                        'price': float(ticker.get('last', 0)),
                        'bid': float(ticker.get('bid', 0)),
                        'ask': float(ticker.get('ask', 0)),
                        'volume': float(ticker.get('baseVolume', 0)),
                        'change_24h': float(ticker.get('percentage', 0))
                    })
                except Exception as e:
                    logger.warning(f"Не удалось получить тикер с биржи: {e}")
            
            # Демо данные
            demo_prices = {
                'BTCUSDT': {'price': 67800.0, 'change': 2.5},
                'ETHUSDT': {'price': 3450.0, 'change': -1.2},
                'BNBUSDT': {'price': 625.0, 'change': 0.8},
                'SOLUSDT': {'price': 145.0, 'change': 5.3}
            }
            
            if symbol in demo_prices:
                data = demo_prices[symbol]
                return jsonify({
                    'success': True,
                    'symbol': symbol,
                    'price': data['price'],
                    'bid': data['price'] - 0.1,
                    'ask': data['price'] + 0.1,
                    'volume': 125000.0,
                    'change_24h': data['change']
                })
            else:
                return jsonify({
                    'success': False,
                    'error': 'Symbol not found'
                }), 404
                
        except Exception as e:
            logger.error(f"Ошибка получения тикера: {e}")
            return jsonify({
                'success': False,
                'error': str(e)
            }), 500
    
    @app.route('/api/trading/stats')
    def get_trading_stats():
        """Получение торговой статистики"""
        try:
            stats = {
                'total_trades': 0,
                'profitable_trades': 0,
                'win_rate': 0.0,
                'total_profit': 0.0,
                'avg_profit': 0.0,
                'best_trade': 0.0,
                'worst_trade': 0.0
            }
            
            # Получаем статистику из БД
            with SessionLocal() as db:
                trades = db.query(Trade).filter(
                    Trade.status == TradeStatus.CLOSED
                ).all()
                
                if trades:
                    stats['total_trades'] = len(trades)
                    profitable = [t for t in trades if t.profit_loss and t.profit_loss > 0]
                    stats['profitable_trades'] = len(profitable)
                    stats['win_rate'] = round(len(profitable) / len(trades) * 100, 1) if trades else 0
                    
                    profits = [float(t.profit_loss) for t in trades if t.profit_loss]
                    if profits:
                        stats['total_profit'] = round(sum(profits), 2)
                        stats['avg_profit'] = round(sum(profits) / len(profits), 2)
                        stats['best_trade'] = round(max(profits), 2)
                        stats['worst_trade'] = round(min(profits), 2)
            
            return jsonify({
                'success': True,
                'stats': stats
            })
            
        except Exception as e:
            logger.error(f"Ошибка получения статистики: {e}")
            return jsonify({
                'success': False,
                'error': str(e)
            }), 500
    
    @app.route('/api/news/latest')
    def get_latest_news():
        """Получение последних новостей"""
        try:
            # Демо новости
            news = [
                {
                    'id': 1,
                    'title': 'Bitcoin показывает признаки восстановления',
                    'summary': 'Аналитики отмечают позитивные сигналы на рынке',
                    'sentiment': 0.7,
                    'impact': 'high',
                    'source': 'CryptoNews',
                    'timestamp': datetime.utcnow().isoformat()
                },
                {
                    'id': 2,
                    'title': 'Ethereum обновляет годовые максимумы',
                    'summary': 'Рост связан с ожиданиями обновления сети',
                    'sentiment': 0.8,
                    'impact': 'medium',
                    'source': 'MarketWatch',
                    'timestamp': (datetime.utcnow() - timedelta(hours=1)).isoformat()
                }
            ]
            
            return jsonify({
                'success': True,
                'news': news
            })
            
        except Exception as e:
            logger.error(f"Ошибка получения новостей: {e}")
            return jsonify({
                'success': False,
                'error': str(e)
            }), 500
    
    @app.route('/api/social/signals')
    def get_social_signals():
        """Получение социальных сигналов"""
        try:
            # Демо сигналы
            signals = [
                {
                    'platform': 'twitter',
                    'symbol': 'BTCUSDT',
                    'sentiment': 0.65,
                    'mentions': 1234,
                    'trend': 'bullish',
                    'timestamp': datetime.utcnow().isoformat()
                },
                {
                    'platform': 'reddit',
                    'symbol': 'ETHUSDT',
                    'sentiment': 0.55,
                    'mentions': 567,
                    'trend': 'neutral',
                    'timestamp': datetime.utcnow().isoformat()
                }
            ]
            
            return jsonify({
                'success': True,
                'signals': signals
            })
            
        except Exception as e:
            logger.error(f"Ошибка получения соц. сигналов: {e}")
            return jsonify({
                'success': False,
                'error': str(e)
            }), 500
    
    @app.route('/api/settings')
    def get_settings():
        """Получение настроек"""
        try:
            settings = {
                'trading': {
                    'max_positions': 15,
                    'risk_per_trade': 2.0,
                    'stop_loss': 1.5,
                    'take_profit': 3.0
                },
                'bot': {
                    'auto_trading': True,
                    'paper_trading': True,
                    'notifications': True
                },
                'strategies': {
                    'available': ['multi_indicator', 'momentum', 'scalping'],
                    'active': 'multi_indicator'
                }
            }
            
            return jsonify({
                'success': True,
                'settings': settings
            })
            
        except Exception as e:
            logger.error(f"Ошибка получения настроек: {e}")
            return jsonify({
                'success': False,
                'error': str(e)
            }), 500
    
    @app.route('/api/trading-pairs')
    def get_trading_pairs():
        """Получение торговых пар"""
        try:
            pairs = [
                {'symbol': 'BTCUSDT', 'active': True, 'min_size': 0.0001},
                {'symbol': 'ETHUSDT', 'active': True, 'min_size': 0.001},
                {'symbol': 'BNBUSDT', 'active': False, 'min_size': 0.01},
                {'symbol': 'SOLUSDT', 'active': True, 'min_size': 0.01}
            ]
            
            return jsonify({
                'success': True,
                'pairs': pairs
            })
            
        except Exception as e:
            logger.error(f"Ошибка получения торговых пар: {e}")
            return jsonify({
                'success': False,
                'error': str(e)
            }), 500
    
    logger.info("✅ Unified API routes зарегистрированы:")
    logger.info("   📊 Charts API - /api/charts/*")
    logger.info("   💹 Trading API - /api/trades, /api/bot/*")
    logger.info("   🌐 Social API - /api/social/*")
    logger.info("   💰 Balance API - /api/balance")
    logger.info("   📈 Analytics API - /api/analytics/*")
    logger.info("   🖥️ System API - /api/system/*")
    
    return True