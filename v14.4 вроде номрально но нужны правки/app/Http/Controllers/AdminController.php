<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Http/Controllers/AdminController.php
| Описание: ПОЛНЫЙ контроллер админки с загрузкой изображений
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Product;
use App\Models\Category;
use App\Models\Theme;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Illuminate\Http\UploadedFile;

class AdminController extends Controller
{
    /**
     * Вход в админку
     * POST /api/admin/login
     */
    public function login(Request $request): JsonResponse
    {
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

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json([
                'error' => 'Неверные учетные данные'
            ], 401);
        }

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
     * Получение данных текущего пользователя
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
     * Статистика для главной страницы админки
     * GET /api/admin/stats
     */
    public function getStats(): JsonResponse
    {
        $stats = [
            'products_count' => Product::count(),
            'categories_count' => Category::count(),
            'themes_count' => Theme::count(),
            'attributes_count' => Attribute::count(),
            'recent_products' => Product::with(['theme', 'categories'])
                ->latest()
                ->take(5)
                ->get()
                ->map(function ($product) {
                    return [
                        'id' => $product->id,
                        'name' => $product->name,
                        'price' => $product->price,
                        'theme' => $product->theme?->name,
                        'categories' => $product->categories->pluck('name')->join(', '),
                        'created_at' => $product->created_at?->format('d.m.Y H:i')
                    ];
                })
        ];

        return response()->json($stats);
    }

    /**
     * Изменение пароля
     * PUT /api/admin/change-password
     */
    public function changePassword(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'current_password' => 'required',
            'new_password' => 'required|string|min:6|confirmed'
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

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'error' => 'Неверный текущий пароль'
            ], 422);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Пароль успешно изменен'
        ]);
    }

    /**
     * Загрузка одного изображения
     * POST /api/admin/images
     */
    public function uploadImage(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:10240', // 10MB
            'type' => 'nullable|in:product,matryoshka_outer,matryoshka_inner',
        ], [
            'image.required' => 'Изображение обязательно',
            'image.image' => 'Файл должен быть изображением',
            'image.mimes' => 'Допустимые форматы: JPEG, PNG, JPG, GIF, WebP',
            'image.max' => 'Размер файла не должен превышать 10MB'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            $image = $request->file('image');
            $type = $request->get('type', 'product');
            
            // Определение папки в зависимости от типа
            $folder = match ($type) {
                'product' => 'images/products',
                'matryoshka_outer' => 'images/matryoshka/outer', 
                'matryoshka_inner' => 'images/matryoshka/inner',
                default => 'images/products'
            };

            // Создание папок если не существуют
            Storage::disk('public')->makeDirectory($folder);
            Storage::disk('public')->makeDirectory($folder . '/thumbs');

            // Генерация уникального имени файла
            $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
            
            // Сохранение основного изображения
            $mainPath = $folder . '/' . $filename;
            $success = Storage::disk('public')->put($mainPath, file_get_contents($image));
            
            if (!$success) {
                throw new \Exception('Не удалось сохранить файл');
            }

            // Создание миниатюры (копия основного файла - можно улучшить с помощью Intervention Image)
            $thumbnailPath = $folder . '/thumbs/' . $filename;
            Storage::disk('public')->put($thumbnailPath, file_get_contents($image));

            // Генерация URL-ов
            $baseUrl = rtrim(config('app.url'), '/');
            $url = $baseUrl . '/storage/' . $mainPath;
            $thumbnailUrl = $baseUrl . '/storage/' . $thumbnailPath;

            return response()->json([
                'message' => 'Изображение загружено успешно',
                'filename' => $filename,
                'url' => $url,
                'thumbnail_url' => $thumbnailUrl,
                'path' => '/storage/' . $mainPath,
                'size' => $image->getSize(),
                'type' => $type
            ], 201);

        } catch (\Exception $e) {
            Log::error('Ошибка загрузки изображения: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Ошибка загрузки изображения',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получение списка всех изображений
     * GET /api/admin/images
     */
    public function getImages(): JsonResponse
    {
        try {
            $images = [];
            $folders = ['images/products', 'images/matryoshka/outer', 'images/matryoshka/inner'];
            
            foreach ($folders as $folder) {
                if (Storage::disk('public')->exists($folder)) {
                    $files = Storage::disk('public')->files($folder);
                    
                    foreach ($files as $file) {
                        $filename = basename($file);
                        $baseUrl = rtrim(config('app.url'), '/');
                        
                        $images[] = [
                            'filename' => $filename,
                            'url' => $baseUrl . '/storage/' . $file,
                            'thumbnail_url' => $baseUrl . '/storage/' . $folder . '/thumbs/' . $filename,
                            'path' => '/storage/' . $file,
                            'folder' => $folder,
                            'size' => Storage::disk('public')->size($file),
                            'created_at' => date('c', Storage::disk('public')->lastModified($file))
                        ];
                    }
                }
            }

            // Сортируем по дате создания (новые первые)
            usort($images, function($a, $b) {
                return strtotime($b['created_at']) - strtotime($a['created_at']);
            });

            return response()->json([
                'data' => $images,
                'total' => count($images)
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка получения списка изображений: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Ошибка получения списка изображений',
                'data' => []
            ], 500);
        }
    }

    /**
     * Удаление изображения
     * DELETE /api/admin/images/{filename}
     */
    public function deleteImage(string $filename): JsonResponse
    {
        try {
            $deleted = false;
            $folders = ['images/products', 'images/matryoshka/outer', 'images/matryoshka/inner'];
            
            foreach ($folders as $folder) {
                $mainPath = $folder . '/' . $filename;
                $thumbPath = $folder . '/thumbs/' . $filename;
                
                if (Storage::disk('public')->exists($mainPath)) {
                    Storage::disk('public')->delete($mainPath);
                    
                    // Удаляем миниатюру если существует
                    if (Storage::disk('public')->exists($thumbPath)) {
                        Storage::disk('public')->delete($thumbPath);
                    }
                    
                    $deleted = true;
                    break;
                }
            }

            if (!$deleted) {
                return response()->json([
                    'error' => 'Изображение не найдено'
                ], 404);
            }

            return response()->json([
                'message' => 'Изображение успешно удалено'
            ]);

        } catch (\Exception $e) {
            Log::error('Ошибка удаления изображения: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Ошибка удаления изображения'
            ], 500);
        }
    }
}