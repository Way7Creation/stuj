<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Http/Middleware/VerifyCsrfToken.php
| Описание: Middleware для защиты от CSRF атак
|--------------------------------------------------------------------------
*/

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        'api/telegram/webhook', // Webhook для Telegram бота
    ];
}