<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Http/Middleware/AdminMiddleware.php
| Описание: Middleware для проверки прав администратора
|--------------------------------------------------------------------------
*/

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Проверяем авторизацию через Sanctum
        if (!$request->user()) {
            return response()->json([
                'error' => 'Необходима авторизация'
            ], 401);
        }

        // В нашем проекте пока все пользователи - админы
        // В будущем можно добавить роли и проверку роли:
        // if (!$request->user()->hasRole('admin')) {
        //     return response()->json([
        //         'error' => 'Недостаточно прав'
        //     ], 403);
        // }

        return $next($request);
    }
}