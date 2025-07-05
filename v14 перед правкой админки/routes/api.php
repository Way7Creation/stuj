<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ThemeController;
use App\Http\Controllers\AttributeController;
use App\Http\Controllers\AttributeValueController; // НОВЫЙ
use App\Http\Controllers\QuizController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\TelegramController;

/*
|--------------------------------------------------------------------------
| API Routes для проекта Стужа (ОБНОВЛЕННАЯ ВЕРСИЯ v2.0)
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
       
        // Работа с изображениями
        Route::prefix('images')->group(function () {
            Route::post('upload', [AdminController::class, 'uploadImage']);
            Route::get('/', [AdminController::class, 'getImages']);
            Route::delete('{filename}', [AdminController::class, 'deleteImage']);
        });
        
        // Админ
        Route::post('/logout', [AdminController::class, 'logout']);
        Route::get('/me', [AdminController::class, 'me']);
        Route::put('/change-password', [AdminController::class, 'changePassword']);
        Route::get('/stats', [AdminController::class, 'getStats']);
        
        // Товары
        Route::apiResource('products', ProductController::class);
        Route::post('products/bulk-delete', [ProductController::class, 'bulkDelete']);
        
        // КАТЕГОРИИ (ОБНОВЛЕНО С ПОДДЕРЖКОЙ ПОДКАТЕГОРИЙ)
        Route::prefix('categories')->group(function () {
            Route::get('/', [CategoryController::class, 'index']);
            Route::get('/tree', [CategoryController::class, 'tree']); // НОВОЕ: Дерево категорий
            Route::post('/', [CategoryController::class, 'store']);
            Route::get('/{category}', [CategoryController::class, 'show']);
            Route::put('/{category}', [CategoryController::class, 'update']);
            Route::delete('/{category}', [CategoryController::class, 'destroy']);
            Route::post('/bulk-delete', [CategoryController::class, 'bulkDelete']);
        });
        
        // Темы
        Route::apiResource('themes', ThemeController::class);
        Route::post('themes/bulk-delete', [ThemeController::class, 'bulkDelete']);
        
        // АТРИБУТЫ (ОБНОВЛЕНО С ПОДДЕРЖКОЙ ЗНАЧЕНИЙ)
        Route::prefix('attributes')->group(function () {
            Route::get('/', [AttributeController::class, 'index']);
            Route::post('/', [AttributeController::class, 'store']);
            Route::get('/{attribute}', [AttributeController::class, 'show']);
            Route::put('/{attribute}', [AttributeController::class, 'update']);
            Route::delete('/{attribute}', [AttributeController::class, 'destroy']);
            Route::post('/bulk-delete', [AttributeController::class, 'bulkDelete']);
            
            // ЗНАЧЕНИЯ АТРИБУТОВ (НОВОЕ)
            Route::get('/{attribute}/values', [AttributeValueController::class, 'index']);
            Route::post('/{attribute}/values', [AttributeValueController::class, 'store']);
        });
        
        // ОТДЕЛЬНЫЕ МАРШРУТЫ ДЛЯ ЗНАЧЕНИЙ АТРИБУТОВ
        Route::delete('/attribute-values/{value}', [AttributeValueController::class, 'destroy']);
        
        // Маппинг маркетплейсов
        Route::apiResource('marketplace_maps', MarketplaceController::class);
        
        // Правила квиза
        Route::apiResource('quiz_rules', QuizController::class);
    });
});

// Служебные маршруты
Route::prefix('marketplace')->middleware('auth:sanctum')->group(function () {
    Route::post('/sync', [MarketplaceController::class, 'sync']);
    Route::get('/sync-status', [MarketplaceController::class, 'status']);
    Route::post('/sync/{marketplace}', [MarketplaceController::class, 'syncSpecific']);
    Route::get('/config/{marketplace}', [MarketplaceController::class, 'getConfig']);
    Route::put('/config/{marketplace}', [MarketplaceController::class, 'updateConfig']);
});

// Telegram webhook
Route::post('/telegram/webhook', [TelegramController::class, 'webhook']);

// Роуты для Vue SPA (catch-all) - ТОЛЬКО для админки
Route::get('/admin/{any}', function () {
    return view('app');
})->where('any', '.*')->middleware('web');