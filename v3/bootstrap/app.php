<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/bootstrap/app.php
| Описание: Точка входа Laravel приложения с загрузкой внешней конфигурации
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Загрузка внешней конфигурации из /etc/stuj/.env
|--------------------------------------------------------------------------
| КРИТИЧЕСКИ ВАЖНО: Конфигурация хранится вне корня сайта для безопасности
*/
if (file_exists('/etc/stuj/.env')) {
    $dotenv = Dotenv\Dotenv::createImmutable('/etc/stuj');
    $dotenv->load();
}

/*
|--------------------------------------------------------------------------
| Create The Application
|--------------------------------------------------------------------------
*/

$app = new Illuminate\Foundation\Application(
    $_ENV['APP_BASE_PATH'] ?? dirname(__DIR__)
);

/*
|--------------------------------------------------------------------------
| Bind Important Interfaces
|--------------------------------------------------------------------------
*/

$app->singleton(
    Illuminate\Contracts\Http\Kernel::class,
    App\Http\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Console\Kernel::class,
    App\Console\Kernel::class
);

$app->singleton(
    Illuminate\Contracts\Debug\ExceptionHandler::class,
    App\Exceptions\Handler::class
);

/*
|--------------------------------------------------------------------------
| Return The Application
|--------------------------------------------------------------------------
*/

return $app;