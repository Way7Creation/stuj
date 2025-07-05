#!/usr/bin/env php
<?php

/**
 * Скрипт синхронизации товаров с маркетплейсами
 * Запускается через cron каждый час
 * 
 * Использование:
 * php /var/www/www-root/data/www/stuj.ru/cron/syncMarketplaces.php
 */

// Определение пути к Laravel
$basePath = dirname(__DIR__);

// Загрузка автолоадера
require $basePath . '/vendor/autoload.php';

// Загрузка приложения Laravel
$app = require_once $basePath . '/bootstrap/app.php';

// Загрузка kernel
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Использование необходимых классов
use App\Http\Controllers\MarketplaceController;
use Illuminate\Support\Facades\Log;

try {
    // Логирование начала синхронизации
    Log::info('Marketplace sync started at ' . now()->format('Y-m-d H:i:s'));
    
    // Создание экземпляра контроллера
    $controller = app(MarketplaceController::class);
    
    // Запуск синхронизации
    $result = $controller->sync();
    
    // Проверка результата
    if ($result) {
        Log::info('Marketplace sync completed successfully');
        echo "Синхронизация выполнена успешно\n";
    } else {
        Log::error('Marketplace sync failed');
        echo "Ошибка синхронизации\n";
        exit(1);
    }
    
} catch (Exception $e) {
    // Логирование ошибки
    Log::error('Marketplace sync error: ' . $e->getMessage());
    Log::error($e->getTraceAsString());
    
    echo "Критическая ошибка: " . $e->getMessage() . "\n";
    exit(1);
}

// Успешное завершение
exit(0);