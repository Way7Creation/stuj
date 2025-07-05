<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\AttributeValueController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\TelegramController;

/*
|--------------------------------------------------------------------------
| API Routes для проекта Стужа (ИСПРАВЛЕННАЯ ВЕРСИЯ v2.2)
|--------------------------------------------------------------------------
*/

// Публичные маршруты (без авторизации)
Route::prefix('')->group(function () {
    Route::get('/catalog', [PublicController::class, 'catalog']);
    Route::get('/product/{slug}', [PublicController::class, 'product']);
    Route::post('/quiz', [QuizController::class, 'calculate']);
});

// Маршруты админки (с авторизацией)
Route::prefix('admin')->group(function () {
    // Вход без авторизации
    Route::post('/login', [AdminController::class, 'login']);
    
    // Защищенные маршруты
    Route::middleware('auth:sanctum')->group(function () {
       
        // Админ
        Route::post('/logout', [AdminController::class, 'logout']);
        Route::get('/me', [AdminController::class, 'me']);
        Route::put('/change-password', [AdminController::class, 'changePassword']);
        Route::get('/stats', [AdminController::class, 'getStats']);
        
        // ИСПРАВЛЕНО: Работа с изображениями - унифицированные маршруты
        Route::prefix('images')->group(function () {
            // ОСНОВНОЙ МАРШРУТ - для совместимости с фронтендом
            Route::post('/', [AdminController::class, 'uploadImage']);
            // АЛЬТЕРНАТИВНЫЙ МАРШРУТ - для обратной совместимости  
            Route::post('/upload', [AdminController::class, 'uploadImage']);
            // ПОЛУЧЕНИЕ И УДАЛЕНИЕ
            Route::get('/', [AdminController::class, 'getImages']);
            Route::delete('/{filename}', [AdminController::class, 'deleteImage']);
        });
        
        // ТОВАРЫ - полный набор операций
        Route::prefix('products')->group(function () {
            Route::get('/', [ProductController::class, 'index']);           // Список товаров
            Route::post('/', [ProductController::class, 'store']);          // Создание товара
            Route::get('/{product}', [ProductController::class, 'show']);    // Просмотр товара
            Route::put('/{product}', [ProductController::class, 'update']);  // Обновление товара
            Route::delete('/{product}', [ProductController::class, 'destroy']); // Удаление товара
            Route::post('/bulk-delete', [ProductController::class, 'bulkDelete']); // Массовое удаление
        });
        
        // КАТЕГОРИИ - с поддержкой иерархии и дополнительными методами
        Route::prefix('categories')->group(function () {
            Route::get('/', [CategoryController::class, 'index']);          // Плоский список
            Route::get('/tree', [CategoryController::class, 'tree']);       // Иерархическое дерево
            Route::post('/', [CategoryController::class, 'store']);         // Создание категории
            Route::get('/{category}', [CategoryController::class, 'show']); // Просмотр категории
            Route::put('/{category}', [CategoryController::class, 'update']); // Обновление категории
            Route::delete('/{category}', [CategoryController::class, 'destroy']); // Удаление категории
            Route::post('/bulk-delete', [CategoryController::class, 'bulkDelete']); // Массовое удаление
            // НОВОЕ: Получение всех потомков категории
            Route::get('/{category}/descendants', [CategoryController::class, 'getDescendants']);
        });
        
        // ТЕМЫ
        Route::prefix('themes')->group(function () {
            Route::get('/', [ThemeController::class, 'index']);
            Route::post('/', [ThemeController::class, 'store']);
            Route::get('/{theme}', [ThemeController::class, 'show']);
            Route::put('/{theme}', [ThemeController::class, 'update']);
            Route::delete('/{theme}', [ThemeController::class, 'destroy']);
            Route::post('/bulk-delete', [ThemeController::class, 'bulkDelete']);
        });
        
        // АТРИБУТЫ - с полной поддержкой значений
        Route::prefix('attributes')->group(function () {
            Route::get('/', [AttributeController::class, 'index']);
            Route::post('/', [AttributeController::class, 'store']);
            Route::get('/{attribute}', [AttributeController::class, 'show']);
            Route::put('/{attribute}', [AttributeController::class, 'update']);
            Route::delete('/{attribute}', [AttributeController::class, 'destroy']);
            Route::post('/bulk-delete', [AttributeController::class, 'bulkDelete']);
            
            // ЗНАЧЕНИЯ АТРИБУТОВ - вложенные маршруты
            Route::get('/{attribute}/values', [AttributeValueController::class, 'index']);
            Route::post('/{attribute}/values', [AttributeValueController::class, 'store']);
            Route::post('/{attribute}/values/bulk-delete', [AttributeValueController::class, 'bulkDelete']);
            Route::post('/{attribute}/values/reorder', [AttributeValueController::class, 'reorder']);
        });
        
        // ОТДЕЛЬНЫЕ МАРШРУТЫ ДЛЯ ОПЕРАЦИЙ СО ЗНАЧЕНИЯМИ АТРИБУТОВ
        Route::prefix('attribute-values')->group(function () {
            Route::get('/{value}', [AttributeValueController::class, 'show']);
            Route::put('/{value}', [AttributeValueController::class, 'update']);
            Route::delete('/{value}', [AttributeValueController::class, 'destroy']);
        });
        
        // МАППИНГ МАРКЕТПЛЕЙСОВ
        Route::prefix('marketplace-maps')->group(function () {
            Route::get('/', [MarketplaceController::class, 'index']);
            Route::post('/', [MarketplaceController::class, 'store']);
            Route::get('/{marketplaceMap}', [MarketplaceController::class, 'show']);
            Route::put('/{marketplaceMap}', [MarketplaceController::class, 'update']);
            Route::delete('/{marketplaceMap}', [MarketplaceController::class, 'destroy']);
        });
        
        // ПРАВИЛА КВИЗА
        Route::prefix('quiz-rules')->group(function () {
            Route::get('/', [QuizController::class, 'index']);
            Route::post('/', [QuizController::class, 'store']);
            Route::get('/{quizRule}', [QuizController::class, 'show']);
            Route::put('/{quizRule}', [QuizController::class, 'update']);
            Route::delete('/{quizRule}', [QuizController::class, 'destroy']);
        });
    });
});

// СЛУЖЕБНЫЕ МАРШРУТЫ ДЛЯ МАРКЕТПЛЕЙСОВ
Route::prefix('marketplace')->middleware('auth:sanctum')->group(function () {
    Route::post('/sync', [MarketplaceController::class, 'sync']);
    Route::get('/sync-status', [MarketplaceController::class, 'status']);
    Route::post('/sync/{marketplace}', [MarketplaceController::class, 'syncSpecific']);
    Route::get('/config/{marketplace}', [MarketplaceController::class, 'getConfig']);
    Route::put('/config/{marketplace}', [MarketplaceController::class, 'updateConfig']);
});

// WEBHOOK ДЛЯ TELEGRAM
Route::post('/telegram/webhook', [TelegramController::class, 'webhook']);

// ОБСЛУЖИВАНИЕ SPA - для админ-панели
Route::get('/admin/{any}', function () {
    return view('app');
})->where('any', '.*')->middleware('web');

// ДОПОЛНИТЕЛЬНЫЕ СЛУЖЕБНЫЕ МАРШРУТЫ
Route::prefix('system')->middleware('auth:sanctum')->group(function () {
    // Проверка состояния системы
    Route::get('/health', function () {
        return response()->json([
            'status' => 'ok',
            'timestamp' => now(),
            'version' => config('app.version', '1.0.0')
        ]);
    });
    
    // Очистка кеша (если используется)
    Route::post('/cache/clear', function () {
        try {
            \Artisan::call('cache:clear');
            \Artisan::call('config:clear');
            \Artisan::call('view:clear');
            
            return response()->json([
                'message' => 'Кеш успешно очищен'
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка очистки кеша',
                'message' => $e->getMessage()
            ], 500);
        }
    });
});