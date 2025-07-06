<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Http/Middleware/AdminMiddleware.php
| Описание: Middleware для проверки доступа к админ-панели
|--------------------------------------------------------------------------
*/

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure(\Illuminate\Http\Request): (\Illuminate\Http\Response|\Illuminate\Http\RedirectResponse)  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next)
    {
        // Проверяем авторизацию через Sanctum
        if (!Auth::guard('sanctum')->check()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'error' => 'Необходима авторизация',
                    'message' => 'Доступ к админ-панели разрешен только авторизованным пользователям'
                ], 401);
            }
            
            return redirect('/admin/login');
        }

        $user = Auth::guard('sanctum')->user();

        // Дополнительные проверки можно добавить здесь
        // Например, проверка роли пользователя, если в будущем будет система ролей
        
        // if (!$user->hasRole('admin')) {
        //     return response()->json([
        //         'error' => 'Недостаточно прав',
        //         'message' => 'У вас нет прав доступа к админ-панели'
        //     ], 403);
        // }

        return $next($request);
    }
}