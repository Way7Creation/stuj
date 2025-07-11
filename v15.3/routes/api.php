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
| API Routes для проекта Стужа (ИСПРАВЛЕННАЯ ВЕРСИЯ v2.3)
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
        
        // Работа с изображениями
        Route::prefix('images')->group(function () {
            Route::post('/', [AdminController::class, 'uploadImage']);
            Route::post('/upload', [AdminController::class, 'uploadImage']); // Альтернативный роут
            Route::get('/', [AdminController::class, 'getImages']);
            Route::delete('/{filename}', [AdminController::class, 'deleteImageByFilename']); // НОВЫЙ МЕТОД для удаления по filename
            Route::delete('/id/{id}', [AdminController::class, 'deleteImage']); // Удаление по ID
            Route::post('/set-main', [AdminController::class, 'setMainImage']);
            Route::post('/reorder', [AdminController::class, 'reorderImages']);
        });
        
        // ТОВАРЫ
        Route::prefix('products')->group(function () {
            Route::get('/', [ProductController::class, 'index']);
            Route::post('/', [ProductController::class, 'store']);
            Route::get('/{product}', [ProductController::class, 'show']);
            Route::put('/{product}', [ProductController::class, 'update']);
            Route::delete('/{product}', [ProductController::class, 'destroy']);
            Route::post('/bulk-delete', [ProductController::class, 'bulkDelete']);
        });
        
        // КАТЕГОРИИ
        Route::prefix('categories')->group(function () {
            Route::get('/', [CategoryController::class, 'index']);
            Route::get('/tree', [CategoryController::class, 'tree']);
            Route::post('/', [CategoryController::class, 'store']);
            Route::get('/{category}', [CategoryController::class, 'show']);
            Route::put('/{category}', [CategoryController::class, 'update']);
            Route::delete('/{category}', [CategoryController::class, 'destroy']);
            Route::post('/bulk-delete', [CategoryController::class, 'bulkDelete']);
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
        
        // АТРИБУТЫ
        Route::prefix('attributes')->group(function () {
            Route::get('/', [AttributeController::class, 'index']);
            Route::post('/', [AttributeController::class, 'store']);
            Route::get('/{attribute}', [AttributeController::class, 'show']);
            Route::put('/{attribute}', [AttributeController::class, 'update']);
            Route::delete('/{attribute}', [AttributeController::class, 'destroy']);
            Route::post('/bulk-delete', [AttributeController::class, 'bulkDelete']);
            
            // Значения атрибутов
            Route::get('/{attribute}/values', [AttributeValueController::class, 'index']);
            Route::post('/{attribute}/values', [AttributeValueController::class, 'store']);
            Route::post('/{attribute}/values/bulk-delete', [AttributeValueController::class, 'bulkDelete']);
            Route::post('/{attribute}/values/reorder', [AttributeValueController::class, 'reorder']);
        });
        
        // Значения атрибутов - отдельные маршруты
        Route::prefix('attribute-values')->group(function () {
            Route::get('/{value}', [AttributeValueController::class, 'show']);
            Route::put('/{value}', [AttributeValueController::class, 'update']);
            Route::delete('/{value}', [AttributeValueController::class, 'destroy']);
        });
        
        // МАППИНГ МАРКЕТПЛЕЙСОВ
        Route::prefix('marketplace-maps')->group(function () {
            Route::get('/', [MarketplaceController::class, 'indexMaps']);
            Route::post('/', [MarketplaceController::class, 'storeMap']);
            Route::get('/{marketplaceMap}', [MarketplaceController::class, 'showMap']);
            Route::put('/{marketplaceMap}', [MarketplaceController::class, 'updateMap']);
            Route::delete('/{marketplaceMap}', [MarketplaceController::class, 'destroyMap']);
            
            // Загрузка категорий и атрибутов с маркетплейсов
            Route::get('/load-categories/{marketplace}', [MarketplaceController::class, 'loadCategories']);
            Route::get('/load-attributes/{marketplace}/{categoryId?}', [MarketplaceController::class, 'loadAttributes']);
        });
        
        // ПРАВИЛА КВИЗА
        Route::prefix('quiz-rules')->group(function () {
            Route::get('/', [QuizController::class, 'index']);
            Route::get('/stones', [QuizController::class, 'stones']); // ДОБАВЛЕН маршрут для получения камней
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
    
    // Тестирование подключения
    Route::post('/test-connection/{marketplace}', [MarketplaceController::class, 'testConnection']);
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
    
    // Очистка кеша
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