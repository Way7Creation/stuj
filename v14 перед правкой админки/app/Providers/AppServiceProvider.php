<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Providers/AppServiceProvider.php
| Описание: Основной сервис-провайдер приложения Laravel
|--------------------------------------------------------------------------
*/

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Schema;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Фикс для старых версий MySQL
        Schema::defaultStringLength(191);
        
        // Принудительное использование HTTPS в продакшене
        if (config('app.env') === 'production') {
            \URL::forceScheme('https');
        }
    }
}