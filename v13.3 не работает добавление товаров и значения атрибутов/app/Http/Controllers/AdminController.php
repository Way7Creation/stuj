<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Http/Controllers/AdminController.php
| Описание: УЛУЧШЕННЫЙ контроллер админки с полным функционалом
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Http\UploadedFile;
use Intervention\Image\Facades\Image;


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
                'email' => $request->user()->email,
                'created_at' => $request->user()->created_at
            ]
        ]);
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
            ], 400);
        }

        $user->update([
            'password' => Hash::make($request->new_password)
        ]);

        return response()->json([
            'message' => 'Пароль успешно изменен'
        ]);
    }
    
    private function getFolderByType(string $type): string
    {
        return match($type) {
            'matryoshka_outer' => 'images/matryoshka/outer',
            'matryoshka_inner' => 'images/matryoshka/inner', 
            default => 'images/products'
        };
    }

    /**
     * Загрузка изображений с созданием миниатюр (ИСПРАВЛЕННАЯ ВЕРСИЯ)
     * POST /api/admin/images/upload
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
            
            // ===== ИСПРАВЛЕНИЕ 1: Унифицированные пути =====
            $folder = match ($type) {
                'product' => 'images/products',
                'matryoshka_outer' => 'images/matryoshka/outer', 
                'matryoshka_inner' => 'images/matryoshka/inner',
                default => 'images/products'
            };
    
            // Создание папок если не существуют
            Storage::disk('public')->makeDirectory($folder);
            Storage::disk('public')->makeDirectory($folder . '/thumbs');
    
            // ===== ИСПРАВЛЕНИЕ 2: Правильные пути сохранения =====
            $filename = time() . '_' . Str::random(10) . '.' . $image->getClientOriginalExtension();
            
            // Основной файл
            $mainPath = $folder . '/' . $filename;
            Storage::disk('public')->put($mainPath, $processedImage->stream());
    
            // Миниатюра  
            $thumbnailPath = $folder . '/thumbs/' . $filename;
            Storage::disk('public')->put($thumbnailPath, $thumbnailImage->stream());
    
            // ===== ИСПРАВЛЕНИЕ 3: Правильные URL-ы =====
            $baseUrl = rtrim(config('app.url'), '/');
            $url = $baseUrl . '/storage/' . $mainPath;
            $thumbnailUrl = $baseUrl . '/storage/' . $thumbnailPath;
    
            return response()->json([
                'message' => 'Изображение успешно загружено',
                'url' => $url,
                'thumbnail_url' => $thumbnailUrl,
                'filename' => $filename,
                'path' => $mainPath,
                'type' => $type,
                'size' => Storage::disk('public')->size($mainPath)
            ], 201);
    
        } catch (\Exception $e) {
            Log::error('Ошибка загрузки изображения: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка загрузки изображения: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получение списка изображений (УЛУЧШЕННАЯ ВЕРСИЯ)
     * GET /api/admin/images?type=product&page=1&per_page=20
     */
    public function getImages(Request $request): JsonResponse
    {
        try {
            $type = $request->get('type', 'product');
            $page = max(1, (int) $request->get('page', 1));
            $perPage = min(50, max(10, (int) $request->get('per_page', 20)));
    
            // ===== ИСПРАВЛЕНИЕ 4: Те же пути что и при сохранении =====
            $folder = match ($type) {
                'product' => 'images/products',
                'matryoshka_outer' => 'images/matryoshka/outer',
                'matryoshka_inner' => 'images/matryoshka/inner', 
                default => 'images/products'
            };
    
            // Получение файлов
            $allFiles = collect(Storage::disk('public')->files($folder))
                ->filter(function ($file) {
                    $extension = strtolower(pathinfo($file, PATHINFO_EXTENSION));
                    return in_array($extension, ['jpg', 'jpeg', 'png', 'gif', 'webp']);
                })
                ->map(function ($file) use ($folder) {
                    $filename = basename($file);
                    
                    // ===== ИСПРАВЛЕНИЕ 5: Правильная генерация URL-ов =====
                    $baseUrl = rtrim(config('app.url'), '/');
                    $url = $baseUrl . '/storage/' . $file;
                    
                    // Проверка миниатюры
                    $thumbnailPath = $folder . '/thumbs/' . $filename;
                    $thumbnailUrl = Storage::disk('public')->exists($thumbnailPath) 
                        ? $baseUrl . '/storage/' . $thumbnailPath
                        : $url;
    
                    return [
                        'filename' => $filename,
                        'path' => $file,
                        'url' => $url,
                        'thumbnail_url' => $thumbnailUrl,
                        'size' => Storage::disk('public')->size($file),
                        'size_human' => $this->formatBytes(Storage::disk('public')->size($file)),
                        'last_modified' => date('Y-m-d H:i:s', Storage::disk('public')->lastModified($file))
                    ];
                })
                ->sortByDesc('last_modified')
                ->values();
    
            // Пагинация
            $total = $allFiles->count();
            $offset = ($page - 1) * $perPage;
            $paginatedFiles = $allFiles->slice($offset, $perPage)->values();
    
            return response()->json([
                'images' => $paginatedFiles,
                'pagination' => [
                    'current_page' => $page,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage),
                    'from' => $total > 0 ? $offset + 1 : 0,
                    'to' => min($offset + $perPage, $total)
                ],
                'type' => $type
            ]);
    
        } catch (\Exception $e) {
            Log::error('Ошибка получения изображений: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка получения изображений: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Удаление изображения
     * DELETE /api/admin/images/{filename}
     */
    public function deleteImage(Request $request, string $filename): JsonResponse
    {
        $validator = Validator::make(['type' => $request->get('type')], [
            'type' => 'required|in:product,matryoshka_outer,matryoshka_inner'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Тип изображения обязателен'
            ], 422);
        }
    
        try {
            $type = $request->get('type');
            
            // Определение папки через новый метод
            $folder = $this->getFolderByType($type);
    
            // Пути к основному файлу и миниатюре
            $mainPath = $folder . '/' . $filename;
            $thumbnailPath = $folder . '/thumbs/' . $filename;
    
            // Проверка существования основного файла
            if (!Storage::disk('public')->exists($mainPath)) {
                return response()->json([
                    'error' => 'Изображение не найдено'
                ], 404);
            }
    
            // Удаление основного файла
            Storage::disk('public')->delete($mainPath);
    
            // Удаление миниатюры если существует
            if (Storage::disk('public')->exists($thumbnailPath)) {
                Storage::disk('public')->delete($thumbnailPath);
            }
    
            return response()->json([
                'message' => 'Изображение и миниатюра успешно удалены'
            ]);
    
        } catch (\Exception $e) {
            Log::error('Ошибка удаления изображения: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка удаления изображения: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получение статистики системы
     * GET /api/admin/stats
     */
    public function getStats(): JsonResponse
    {
        try {
            $stats = [
                'products' => [
                    'total' => \App\Models\Product::count(),
                    'matryoshka' => \App\Models\Product::where('use_matryoshka', true)->count(),
                    'regular' => \App\Models\Product::where('use_matryoshka', false)->count()
                ],
                'categories' => \App\Models\Category::count(),
                'themes' => \App\Models\Theme::count(),
                'attributes' => \App\Models\Attribute::count(),
                'images' => [
                    'products' => count(Storage::disk('public')->files('images/products')),
                    'matryoshka_outer' => count(Storage::disk('public')->files('images/matryoshka/outer')),
                    'matryoshka_inner' => count(Storage::disk('public')->files('images/matryoshka/inner'))
                ],
                'storage' => [
                    'total_size' => $this->getDirectorySize('images'),
                    'total_size_human' => $this->formatBytes($this->getDirectorySize('images'))
                ]
            ];

            return response()->json($stats);

        } catch (\Exception $e) {
            Log::error('Ошибка получения статистики: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка получения статистики'
            ], 500);
        }
    }

    /**
     * Вспомогательные методы
     */
    private function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = ['B', 'KB', 'MB', 'GB', 'TB'];

        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }

        return round($bytes, $precision) . ' ' . $units[$i];
    }

    private function getDirectorySize(string $directory): int
    {
        $size = 0;
        $files = Storage::disk('public')->allFiles($directory);
        
        foreach ($files as $file) {
            $size += Storage::disk('public')->size($file);
        }

        return $size;
    }
}