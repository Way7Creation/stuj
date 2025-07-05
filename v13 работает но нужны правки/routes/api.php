<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\TelegramController;

/*
|--------------------------------------------------------------------------
| API Routes для проекта Стужа (УЛУЧШЕННАЯ ВЕРСИЯ)
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
       
        // Работа с изображениями (УЛУЧШЕННАЯ)
        Route::prefix('images')->group(function () {
            Route::post('upload', [AdminController::class, 'uploadImage']);
            Route::get('/', [AdminController::class, 'getImages']);
            Route::delete('{filename}', [AdminController::class, 'deleteImage']);
        });
        
        // Админ (ДОПОЛНЕННАЯ)
        Route::post('/logout', [AdminController::class, 'logout']);
        Route::get('/me', [AdminController::class, 'me']);
        Route::put('/change-password', [AdminController::class, 'changePassword']);
        Route::get('/stats', [AdminController::class, 'getStats']); // НОВОЕ
        
        // Товары
        Route::apiResource('products', ProductController::class);
        Route::post('products/bulk-delete', [ProductController::class, 'bulkDelete']);
        
        // Категории
        Route::apiResource('categories', CategoryController::class);
        Route::post('categories/bulk-delete', [CategoryController::class, 'bulkDelete']);
        
        // Темы
        Route::apiResource('themes', ThemeController::class);
        Route::post('themes/bulk-delete', [ThemeController::class, 'bulkDelete']);
        
        // Атрибуты
        Route::apiResource('attributes', AttributeController::class);
        Route::post('attributes/bulk-delete', [AttributeController::class, 'bulkDelete']);
        
        // Маппинг маркетплейсов
        Route::apiResource('marketplace_maps', MarketplaceController::class);
        
        // Правила квиза
        Route::apiResource('quiz_rules', QuizController::class);
    });
});

// Служебные маршруты (РАСШИРЕННЫЕ)
Route::prefix('marketplace')->middleware('auth:sanctum')->group(function () {
    Route::post('/sync', [MarketplaceController::class, 'sync']);
    Route::get('/sync-status', [MarketplaceController::class, 'status']);
    Route::post('/sync/{marketplace}', [MarketplaceController::class, 'syncSpecific']); // НОВОЕ
    Route::get('/config/{marketplace}', [MarketplaceController::class, 'getConfig']); // НОВОЕ
    Route::put('/config/{marketplace}', [MarketplaceController::class, 'updateConfig']); // НОВОЕ
});

// Роуты для Vue SPA (catch-all)
Route::get('/admin/{any}', function () {
    return view('app'); // или какой у вас view для Vue приложения
})->where('any', '.*')->middleware('web');

// Telegram webhook
Route::post('/telegram/webhook', [TelegramController::class, 'webhook']);