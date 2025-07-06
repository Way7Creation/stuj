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
use App\Models\Image;
use Illuminate\Support\Facades\DB;

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
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB
            'type' => 'required|in:product,category,theme',
            'product_id' => 'nullable|exists:products,id'
        ]);
    
        try {
            DB::beginTransaction();
            
            $file = $request->file('image');
            $filename = time() . '_' . uniqid() . '.' . $file->getClientOriginalExtension();
            
            // Сохраняем файл
            $path = $file->storeAs('public/images/products', $filename);
            
            // Определяем порядок сортировки
            $maxOrder = Image::where('product_id', $request->product_id)
                            ->max('sort_order') ?? -1;
            
            // Создаем запись в БД
            $image = Image::create([
                'filename' => $filename,
                'type' => $request->type,
                'product_id' => $request->product_id,
                'sort_order' => $maxOrder + 1,
                'is_main' => false
            ]);
            
            // Если это первое изображение товара - делаем главным
            if ($request->product_id) {
                $productImagesCount = Image::where('product_id', $request->product_id)->count();
                if ($productImagesCount === 1) {
                    $image->setAsMain();
                }
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Изображение загружено',
                'image' => $image,
                'url' => $image->url
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            // Удаляем файл если он был загружен
            if (isset($path)) {
                Storage::delete($path);
            }
            
            \Log::error('Ошибка загрузки изображения: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка загрузки',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получение списка всех изображений
     * GET /api/admin/images
     */
    public function getImages(Request $request)
    {
        try {
            $query = Image::query();
            
            // Фильтр по товару
            if ($request->filled('product_id')) {
                $query->where('product_id', $request->product_id);
            }
            
            // Фильтр по типу
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }
            
            // Сортировка
            $query->orderBy('sort_order', 'asc')
                  ->orderBy('created_at', 'desc');
            
            $images = $query->with('product')->paginate(20);
            
            // Добавляем URL к каждому изображению
            $images->getCollection()->transform(function ($image) {
                $image->url = $image->url; // используем accessor
                return $image;
            });
            
            return response()->json($images);
            
        } catch (\Exception $e) {
            \Log::error('Ошибка получения изображений: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка получения изображений',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    /**
     * Удаление изображения
     * DELETE /api/admin/images/{filename}
     */
    public function deleteImage($id)
    {
        try {
            $image = Image::findOrFail($id);
            
            DB::beginTransaction();
            
            // Путь к файлу
            $path = 'public/images/products/' . $image->filename;
            
            // Проверяем был ли это главное изображение
            $wasMain = $image->is_main;
            $productId = $image->product_id;
            
            // Удаляем файл
            if (Storage::exists($path)) {
                Storage::delete($path);
            }
            
            // Удаляем запись из БД
            $image->delete();
            
            // Если удалили главное изображение - назначаем новое
            if ($wasMain && $productId) {
                $nextImage = Image::where('product_id', $productId)
                                 ->orderBy('sort_order', 'asc')
                                 ->first();
                                 
                if ($nextImage) {
                    $nextImage->setAsMain();
                }
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Изображение удалено'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Ошибка удаления изображения: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка удаления',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * НОВЫЙ МЕТОД: Установить главное изображение
     */
    public function setMainImage(Request $request)
    {
        $request->validate([
            'image_id' => 'required|exists:images,id'
        ]);
    
        try {
            $image = Image::findOrFail($request->image_id);
            $image->setAsMain();
            
            return response()->json([
                'message' => 'Главное изображение установлено'
            ]);
            
        } catch (\Exception $e) {
            \Log::error('Ошибка установки главного изображения: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка',
                'message' => $e->getMessage()
            ], 500);
        }
    }
    
    /**
     * НОВЫЙ МЕТОД: Изменить порядок изображений
     */
    public function reorderImages(Request $request)
    {
        $request->validate([
            'images' => 'required|array',
            'images.*.id' => 'required|exists:images,id',
            'images.*.sort_order' => 'required|integer|min:0'
        ]);
    
        try {
            DB::beginTransaction();
            
            foreach ($request->images as $imageData) {
                Image::where('id', $imageData['id'])
                     ->update(['sort_order' => $imageData['sort_order']]);
            }
            
            DB::commit();
            
            return response()->json([
                'message' => 'Порядок изображений обновлен'
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            
            \Log::error('Ошибка изменения порядка: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка',
                'message' => $e->getMessage()
            ], 500);
        }
    }
}