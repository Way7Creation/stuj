<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Http/Controllers/ProductController.php
| Описание: CRUD контроллер для управления товарами (админка)
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * Список товаров для админки
     * GET /api/admin/products
     */
    public function index(Request $request): JsonResponse
    {
        $query = Product::with(['theme', 'categories', 'attributes']);

        // Поиск
        if ($request->filled('search')) {
            $search = $request->get('search');
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Фильтр по теме
        if ($request->filled('theme_id')) {
            $query->where('theme_id', $request->get('theme_id'));
        }

        // Сортировка
        $sortBy = $request->get('sort_by', 'created_at');
        $sortOrder = $request->get('sort_order', 'desc');
        $query->orderBy($sortBy, $sortOrder);

        // Пагинация
        $perPage = min($request->get('per_page', 15), 100);
        $products = $query->paginate($perPage);

        return response()->json($products);
    }

    /**
     * Создание нового товара
     * POST /api/admin/products
     */
    public function store(Request $request): JsonResponse
    {
        // ИСПРАВЛЕНО: Используем общий метод валидации
        $validator = Validator::make($request->all(), $this->getValidationRules(), [
            'name.required' => 'Название товара обязательно',
            'name.max' => 'Название не должно превышать 255 символов',
            'description.required' => 'Описание товара обязательно',
            'price.required' => 'Цена товара обязательна',
            'price.numeric' => 'Цена должна быть числом',
            'price.min' => 'Цена не может быть отрицательной',
            'theme_id.required' => 'Тема товара обязательна',
            'theme_id.exists' => 'Выбранная тема не существует'
        ]);
    
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }
    
        // Создание слага
        $slug = Str::slug($request->name);
        $originalSlug = $slug;
        $counter = 1;
    
        // Проверка уникальности слага
        while (Product::where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }
    
        // Создание товара
        $product = Product::create([
            'name' => $request->name,
            'slug' => $slug,
            'description' => $request->description,
            'price' => $request->price,
            'theme_id' => $request->theme_id,
            'use_matryoshka' => $request->boolean('use_matryoshka'),
            'image_layers' => $request->image_layers,
            'gallery_images' => $request->gallery_images ?? []
        ]);
    
        // Привязка категорий
        if ($request->filled('category_ids')) {
            $product->categories()->sync($request->category_ids);
        }
    
        // Привязка атрибутов
        if ($request->filled('attribute_ids')) {
            $product->attributes()->sync($request->attribute_ids);
        }
    
        // Загрузка связанных данных
        $product->load(['theme', 'categories', 'attributes']);
    
        return response()->json([
            'message' => 'Товар успешно создан',
            'product' => $product
        ], 201);
    }


    private function getValidationRules(bool $isUpdate = false): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'description' => 'required|string', 
            'price' => 'required|numeric|min:0',
            'theme_id' => 'required|exists:themes,id',
            'use_matryoshka' => 'boolean',
            'image_layers' => 'nullable|array',
            'image_layers.outer' => 'nullable|string',
            'image_layers.inner' => 'nullable|string',
            'gallery_images' => 'nullable|array',
            'gallery_images.*' => 'string',
            'category_ids' => 'nullable|array',
            'category_ids.*' => 'exists:categories,id',
            'attribute_ids' => 'nullable|array',
            'attribute_ids.*' => 'exists:attributes,id'
        ];
        
        if ($isUpdate) {
            // Для обновления делаем поля опциональными
            foreach (['name', 'description', 'price', 'theme_id'] as $field) {
                if (isset($rules[$field])) {
                    $rules[$field] = 'sometimes|' . $rules[$field];
                }
            }
        }
        
        return $rules;
    }
    
    
    /**
     * Детали товара
     * GET /api/admin/products/{id}
     */
    public function show(Product $product): JsonResponse
    {
        $product->load(['theme', 'categories', 'attributes']);
        return response()->json($product);
    }

    /**
     * Обновление товара
     * PUT /api/admin/products/{id}
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        // ИСПРАВЛЕНО: Используем общий метод валидации для обновления
        $validator = Validator::make($request->all(), $this->getValidationRules(true));
    
        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }
    
        // Обновление слага, если изменилось название
        if ($request->filled('name') && $request->name !== $product->name) {
            $slug = Str::slug($request->name);
            $originalSlug = $slug;
            $counter = 1;
    
            while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
    
            $product->slug = $slug;
        }
    
        // Обновление основных полей
        $product->fill($request->only([
            'name', 'description', 'price', 'theme_id', 'use_matryoshka'
        ]));
    
        // Обновление JSON полей
        if ($request->has('image_layers')) {
            $product->image_layers = $request->image_layers;
        }
    
        if ($request->has('gallery_images')) {
            $product->gallery_images = $request->gallery_images;
        }
    
        $product->save();
    
        // Обновление категорий
        if ($request->has('category_ids')) {
            $product->categories()->sync($request->category_ids ?? []);
        }
    
        // Обновление атрибутов
        if ($request->has('attribute_ids')) {
            $product->attributes()->sync($request->attribute_ids ?? []);
        }
    
        // Загрузка обновленных данных
        $product->load(['theme', 'categories', 'attributes']);
    
        return response()->json([
            'message' => 'Товар успешно обновлен',
            'product' => $product
        ]);
    }

    /**
     * Удаление товара
     * DELETE /api/admin/products/{id}
     */
    public function destroy(Product $product): JsonResponse
    {
        $productName = $product->name;
        
        // Удаление связей (автоматически через onDelete cascade в миграциях)
        $product->delete();

        return response()->json([
            'message' => "Товар '{$productName}' успешно удален"
        ]);
    }

    /**
     * Массовое удаление товаров
     * POST /api/admin/products/bulk-delete
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:products,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        $deletedCount = Product::whereIn('id', $request->ids)->delete();

        return response()->json([
            'message' => "Удалено товаров: {$deletedCount}"
        ]);
    }
}