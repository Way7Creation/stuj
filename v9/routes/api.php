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
| API Routes для проекта Стужа
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
        Route::post('images/upload', [AdminController::class, 'uploadImage']);
        Route::get('images', [AdminController::class, 'getImages']);
        
        // Админ
        Route::post('/logout', [AdminController::class, 'logout']);
        Route::get('/me', [AdminController::class, 'me']);
        Route::put('/change-password', [AdminController::class, 'changePassword']);
        
        // Товары
        Route::apiResource('products', ProductController::class);
        Route::post('products/bulk-delete', [ProductController::class, 'bulkDelete']);
        
        // Категории
        Route::apiResource('categories', CategoryController::class);
        
        // Темы
        Route::apiResource('themes', ThemeController::class);
        
        // Атрибуты
        Route::apiResource('attributes', AttributeController::class);
        
        // Маппинг маркетплейсов (ИСПРАВЛЕНО: MarketplaceController вместо MarketplaceMapController)
        Route::apiResource('marketplace_maps', MarketplaceController::class);
        
        // Правила квиза (ИСПРАВЛЕНО: QuizController вместо QuizRuleController)
        Route::apiResource('quiz_rules', QuizController::class);
    });
});

// Служебные маршруты
Route::prefix('marketplace')->middleware('auth:sanctum')->group(function () {
    Route::post('/sync', [MarketplaceController::class, 'sync']);
    Route::get('/sync-status', [MarketplaceController::class, 'status']);
});

Route::get('/admin/{any}', function () {
    return view('app'); // или какой у вас view для Vue приложения
})->where('any', '.*')->middleware('web');

// Telegram webhook
Route::post('/telegram/webhook', [TelegramController::class, 'webhook']);