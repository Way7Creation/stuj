<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PublicController;
use App\Http\Controllers\ProductController;
use App\Http\Controllers\AdminController;
use App\Http\Controllers\MarketplaceController;
use App\Http\Controllers\QuizController;
use App\Http\Controllers\TelegramController;

/*
|--------------------------------------------------------------------------
| API Routes (ОБЯЗАТЕЛЬНЫЕ СОГЛАСНО ДОКУМЕНТАЦИИ)
|--------------------------------------------------------------------------
|
| Здесь регистрируются API маршруты для приложения. Эти маршруты
| загружаются RouteServiceProvider и назначаются группе "api".
|
*/

// ===== ПУБЛИЧНЫЕ API =====
// Список товаров с фильтрами ?cat=&theme=&attr=
Route::get('/catalog', [PublicController::class, 'catalog']);

// Детали товара
Route::get('/product/{slug}', [PublicController::class, 'product']);

// Квиз подбора {day,month,year,hour}
Route::post('/quiz', [QuizController::class, 'calculate']);

// ===== АДМИН API =====
Route::prefix('admin')->group(function () {
    // Авторизация
    Route::post('/login', [AdminController::class, 'login']);
    
    // Защищенные маршруты (требуют аутентификации)
    Route::middleware('auth:sanctum')->group(function () {
        // === Товары ===
        Route::get('/products', [ProductController::class, 'index']);
        Route::post('/products', [ProductController::class, 'store']);
        Route::put('/products/{id}', [ProductController::class, 'update']);
        Route::delete('/products/{id}', [ProductController::class, 'destroy']);
        
        // === Категории ===
        Route::get('/categories', [AdminController::class, 'categoriesIndex']);
        Route::post('/categories', [AdminController::class, 'categoriesStore']);
        Route::put('/categories/{id}', [AdminController::class, 'categoriesUpdate']);
        Route::delete('/categories/{id}', [AdminController::class, 'categoriesDestroy']);
        
        // === Темы ===
        Route::get('/themes', [AdminController::class, 'themesIndex']);
        Route::post('/themes', [AdminController::class, 'themesStore']);
        Route::put('/themes/{id}', [AdminController::class, 'themesUpdate']);
        Route::delete('/themes/{id}', [AdminController::class, 'themesDestroy']);
        
        // === Атрибуты ===
        Route::get('/attributes', [AdminController::class, 'attributesIndex']);
        Route::post('/attributes', [AdminController::class, 'attributesStore']);
        Route::put('/attributes/{id}', [AdminController::class, 'attributesUpdate']);
        Route::delete('/attributes/{id}', [AdminController::class, 'attributesDestroy']);
        
        // === Маппинг маркетплейсов ===
        Route::get('/marketplace_maps', [MarketplaceController::class, 'mapsIndex']);
        Route::post('/marketplace_maps', [MarketplaceController::class, 'mapsStore']);
        Route::put('/marketplace_maps/{id}', [MarketplaceController::class, 'mapsUpdate']);
        Route::delete('/marketplace_maps/{id}', [MarketplaceController::class, 'mapsDestroy']);
        
        // === Правила квиза ===
        Route::get('/quiz_rules', [QuizController::class, 'rulesIndex']);
        Route::post('/quiz_rules', [QuizController::class, 'rulesStore']);
        Route::put('/quiz_rules/{id}', [QuizController::class, 'rulesUpdate']);
        Route::delete('/quiz_rules/{id}', [QuizController::class, 'rulesDestroy']);
    });
});

// ===== СЛУЖЕБНЫЕ API =====
// Синхронизация с маркетплейсами
Route::post('/marketplace/sync', [MarketplaceController::class, 'sync'])
    ->middleware('auth:sanctum');

// ===== TELEGRAM WEBHOOK =====
Route::post('/telegram/webhook', [TelegramController::class, 'webhook']);