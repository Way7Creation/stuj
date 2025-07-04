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
    
    public function uploadImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120' // 5MB max
        ], [
            'image.required' => 'Изображение обязательно',
            'image.image' => 'Файл должен быть изображением',
            'image.mimes' => 'Поддерживаемые форматы: JPEG, PNG, JPG, GIF, WebP',
            'image.max' => 'Максимальный размер файла: 5MB'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $image = $request->file('image');
            
            // Создание уникального имени файла
            $filename = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            
            // Создание папки если её нет
            $uploadPath = 'storage/images/products';
            if (!file_exists(public_path($uploadPath))) {
                mkdir(public_path($uploadPath), 0755, true);
            }
            
            // Перемещение файла
            $image->move(public_path($uploadPath), $filename);
            
            // URL для доступа к файлу
            $url = '/' . $uploadPath . '/' . $filename;
            
            return response()->json([
                'message' => 'Изображение успешно загружено',
                'url' => $url,
                'filename' => $filename
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка загрузки файла',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получение списка загруженных изображений
     * GET /api/admin/images
     */
    public function getImages(Request $request): JsonResponse
    {
        try {
            $uploadPath = public_path('storage/images/products');
            
            if (!is_dir($uploadPath)) {
                return response()->json([
                    'images' => []
                ]);
            }
            
            $files = array_diff(scandir($uploadPath), ['.', '..']);
            $images = [];
            
            foreach ($files as $file) {
                $filePath = $uploadPath . '/' . $file;
                if (is_file($filePath) && in_array(strtolower(pathinfo($file, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'gif', 'webp'])) {
                    $images[] = [
                        'filename' => $file,
                        'url' => '/storage/images/products/' . $file,
                        'size' => filesize($filePath),
                        'created' => date('Y-m-d H:i:s', filemtime($filePath))
                    ];
                }
            }
            
            // Сортировка по дате создания (новые первыми)
            usort($images, function($a, $b) {
                return strtotime($b['created']) - strtotime($a['created']);
            });
            
            return response()->json([
                'images' => $images
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Ошибка получения изображений',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Смена пароля администратора
     * PUT /api/admin/change-password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'error' => 'Неверный текущий пароль'
            ], 401);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Пароль успешно изменен'
        ]);
    }

    /**
     * Получение информации о текущем пользователе
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
}