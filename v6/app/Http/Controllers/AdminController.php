<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Http/Controllers/AdminController.php
| Описание: Контроллер для авторизации администратора
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class AdminController extends Controller
{
    /**
     * Вход в админку
     * POST /api/admin/login
     */
    public function login(Request $request): JsonResponse
    {
        // Валидация данных
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string|min:6'
        ], [
            'email.required' => 'Email обязателен',
            'email.email' => 'Некорректный email',
            'password.required' => 'Пароль обязателен',
            'password.min' => 'Пароль должен быть не менее 6 символов'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        // Поиск пользователя
        $user = User::where('email', $request->email)->first();

        // Проверка пользователя и пароля
        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'Неверные учетные данные'
            ], 401);
        }

        // Создание токена
        $token = $user->createToken('admin-token')->plainTextToken;

        return response()->json([
            'message' => 'Успешный вход',
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email
            ],
            'token' => $token
        ]);
    }

    /**
     * Выход из админки
     * POST /api/admin/logout
     */
    public function logout(Request $request): JsonResponse
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'message' => 'Успешный выход'
        ]);
    }

    /**
     * Проверка авторизации
     * GET /api/admin/me
     */
    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => [
                'id' => $request->user()->id,
                'name' => $request->user()->name,
                'email' => $request->user()->email
            ]
        ]);
    }

    /**
     * Смена пароля
     * POST /api/admin/change-password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed'
        ], [
            'current_password.required' => 'Текущий пароль обязателен',
            'new_password.required' => 'Новый пароль обязателен',
            'new_password.min' => 'Новый пароль должен быть не менее 6 символов',
            'new_password.confirmed' => 'Подтверждение пароля не совпадает'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        // Проверка текущего пароля
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'error' => 'Неверный текущий пароль'
            ], 401);
        }

        // Обновление пароля
        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        // Удаление всех токенов (принудительный logout)
        $user->tokens()->delete();

        return response()->json([
            'message' => 'Пароль успешно изменен. Войдите заново.'
        ]);
    }
}