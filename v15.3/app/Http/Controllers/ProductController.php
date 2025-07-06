<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductAttribute;
use App\Models\Theme;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    /**
     * ИСПРАВЛЕНО: Добавлен отсутствующий метод index
     * GET /api/admin/products
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Product::query()
                ->with(['theme', 'categories'])
                ->withCount(['categories']);

            // Поиск по названию
            if ($request->filled('search')) {
                $search = $request->get('search');
                $query->where(function($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                      ->orWhere('description', 'like', "%{$search}%");
                });
            }

            // Фильтр по теме
            if ($request->filled('theme_id') && $request->theme_id !== '') {
                $query->where('theme_id', $request->theme_id);
            }

            // Сортировка
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            
            switch ($sortBy) {
                case 'name':
                    $query->orderBy('name', $sortOrder);
                    break;
                case 'price':
                    $query->orderBy('price', $sortOrder);
                    break;
                case 'created_at':
                default:
                    $query->orderBy('created_at', $sortOrder);
                    break;
            }

            // Пагинация
            $perPage = min($request->get('per_page', 15), 50); // Максимум 50
            $products = $query->paginate($perPage);

            // Трансформация данных для ответа
            $products->getCollection()->transform(function ($product) {
                return [
                    'id' => $product->id,
                    'name' => $product->name,
                    'slug' => $product->slug,
                    'description' => Str::limit($product->description, 150),
                    'price' => (float) $product->price,
                    'use_matryoshka' => $product->use_matryoshka,
                    'image_layers' => $product->image_layers,
                    'gallery_images' => $product->gallery_images,
                    'main_image' => $product->main_image,
                    'theme' => $product->theme ? [
                        'id' => $product->theme->id,
                        'name' => $product->theme->name
                    ] : null,
                    'categories' => $product->categories->map(function ($category) {
                        return [
                            'id' => $category->id,
                            'name' => $category->name,
                            'slug' => $category->slug
                        ];
                    }),
                    'categories_count' => $product->categories_count,
                    'created_at' => $product->created_at,
                    'updated_at' => $product->updated_at
                ];
            });

            // Добавляем дополнительные данные для фильтров
            $responseData = $products->toArray();
            $responseData['filters'] = [
                'themes' => Theme::orderBy('name')->get(['id', 'name']),
                'categories' => Category::orderBy('name')->get(['id', 'name', 'slug'])
            ];

            return response()->json($responseData);

        } catch (\Exception $e) {
            \Log::error('Ошибка загрузки товаров: ' . $e->getMessage());
            \Log::error('Stack trace: ' . $e->getTraceAsString());
            
            return response()->json([
                'error' => 'Ошибка загрузки товаров',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ИСПРАВЛЕНО: Создание нового товара с поддержкой атрибутов
     * POST /api/admin/products
     */
    public function store(Request $request): JsonResponse
    {
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

        DB::beginTransaction();
        
        try {
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
                'use_matryoshka' => $request->boolean('use_matryoshka', false),
                'image_layers' => $request->input('image_layers', []),
                'gallery_images' => $request->input('gallery_images', []),
                'theme_id' => $request->theme_id
            ]);
            
            // ИСПРАВЛЕНО: Сохранение категорий
            if ($request->filled('category_ids') && is_array($request->category_ids)) {
                $product->categories()->sync($request->category_ids);
            }
            
            // ИСПРАВЛЕНО: Сохранение атрибутов с их значениями
            if ($request->filled('attribute_values')) {
                $this->saveProductAttributes($product, $request->attribute_values);
            }
            
            DB::commit();
            
            // Загружаем товар с связями для ответа
            $product->load(['theme', 'categories', 'attributes']);
            
            return response()->json([
                'message' => 'Товар успешно создан',
                'product' => $product
            ], 201);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Ошибка создания товара: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Ошибка создания товара',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ИСПРАВЛЕНО: Получение товара с атрибутами
     * GET /api/admin/products/{id}
     */
    public function show(Product $product): JsonResponse
    {
        try {
            $product->load([
                'theme',
                'categories',
                'attributes'
            ]);
            
            // ИСПРАВЛЕНО: Загружаем product_attributes с связями
            $productAttributes = ProductAttribute::where('product_id', $product->id)
                ->with(['attribute', 'attributeValue'])
                ->get();
                
            $product->product_attributes = $productAttributes;
            
            return response()->json($product);
            
        } catch (\Exception $e) {
            \Log::error('Ошибка загрузки товара: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Ошибка загрузки товара',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ИСПРАВЛЕНО: Обновление товара с поддержкой атрибутов
     * PUT /api/admin/products/{id}
     */
    public function update(Request $request, Product $product): JsonResponse
    {
        $validator = Validator::make($request->all(), $this->getValidationRules($product->id), [
            'name.required' => 'Название товара обязательно',
            'name.max' => 'Название не должно превышать 255 символов',
            'description.required' => 'Описание товара обязательно',
            'price.required' => 'Цена товара обязательна',
            'price.numeric' => 'Цена должна быть числом',
            'price.min' => 'Цена не может быть отрицательной'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        DB::beginTransaction();
        
        try {
            // Обновление основной информации
            $updateData = [
                'name' => $request->name,
                'description' => $request->description,
                'price' => $request->price,
                'use_matryoshka' => $request->boolean('use_matryoshka'),
                'image_layers' => $request->input('image_layers', []),
                'gallery_images' => $request->input('gallery_images', [])
            ];
            
            // Обновляем slug только если изменилось название
            if ($request->name !== $product->name) {
                $slug = Str::slug($request->name);
                $originalSlug = $slug;
                $counter = 1;
                
                while (Product::where('slug', $slug)->where('id', '!=', $product->id)->exists()) {
                    $slug = $originalSlug . '-' . $counter;
                    $counter++;
                }
                
                $updateData['slug'] = $slug;
            }
            
            // Обновляем тему если указана
            if ($request->filled('theme_id')) {
                $updateData['theme_id'] = $request->theme_id;
            }
            
            $product->update($updateData);
            
            // ИСПРАВЛЕНО: Обновление категорий
            if ($request->has('category_ids')) {
                $product->categories()->sync($request->category_ids ?? []);
            }
            
            // ИСПРАВЛЕНО: Обновление атрибутов
            if ($request->has('attribute_values')) {
                // Удаляем старые атрибуты
                ProductAttribute::where('product_id', $product->id)->delete();
                
                // Сохраняем новые атрибуты
                if (!empty($request->attribute_values)) {
                    $this->saveProductAttributes($product, $request->attribute_values);
                }
            }
            
            DB::commit();
            
            // Загружаем товар с обновленными связями
            $product->load(['theme', 'categories', 'attributes']);
            
            return response()->json([
                'message' => 'Товар успешно обновлен',
                'product' => $product
            ]);
            
        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Ошибка обновления товара: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Ошибка обновления товара',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ИСПРАВЛЕНО: Добавлен отсутствующий метод удаления
     * DELETE /api/admin/products/{id}
     */
    public function destroy(Product $product): JsonResponse
    {
        try {
            DB::beginTransaction();

            // Удаляем связи с категориями
            $product->categories()->detach();
            
            // Удаляем атрибуты товара
            ProductAttribute::where('product_id', $product->id)->delete();
            
            // Удаляем сам товар
            $product->delete();
            
            DB::commit();

            return response()->json([
                'message' => 'Товар успешно удален'
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Ошибка удаления товара: ' . $e->getMessage());

            return response()->json([
                'error' => 'Ошибка удаления товара',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * ИСПРАВЛЕНО: Добавлен метод массового удаления
     * POST /api/admin/products/bulk-delete
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'integer|exists:products,id'
        ], [
            'ids.required' => 'Необходимо выбрать товары для удаления',
            'ids.array' => 'Неверный формат данных',
            'ids.min' => 'Необходимо выбрать хотя бы один товар'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        try {
            DB::beginTransaction();

            $products = Product::whereIn('id', $request->ids)->get();
            $deletedCount = 0;

            foreach ($products as $product) {
                // Удаляем связи
                $product->categories()->detach();
                ProductAttribute::where('product_id', $product->id)->delete();
                
                // Удаляем товар
                $product->delete();
                $deletedCount++;
            }

            DB::commit();

            return response()->json([
                'message' => "Удалено товаров: {$deletedCount}",
                'deleted_count' => $deletedCount
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Ошибка массового удаления товаров: ' . $e->getMessage());

            return response()->json([
                'error' => 'Ошибка удаления товаров',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * НОВОЕ: Метод сохранения атрибутов товара
     */
    private function saveProductAttributes(Product $product, array $attributeValues): void
    {
        foreach ($attributeValues as $attributeId => $values) {
            // Обработка кастомных значений
            if (strpos($attributeId, 'custom_') === 0) {
                $realAttributeId = str_replace('custom_', '', $attributeId);
                
                if (!empty($values)) {
                    ProductAttribute::create([
                        'product_id' => $product->id,
                        'attribute_id' => $realAttributeId,
                        'attribute_value_id' => null,
                        'custom_value' => $values
                    ]);
                }
                continue;
            }
            
            // Обработка стандартных значений атрибутов
            if (is_array($values)) {
                foreach ($values as $valueId) {
                    if (!empty($valueId)) {
                        ProductAttribute::create([
                            'product_id' => $product->id,
                            'attribute_id' => $attributeId,
                            'attribute_value_id' => $valueId,
                            'custom_value' => null
                        ]);
                    }
                }
            } elseif (!empty($values)) {
                ProductAttribute::create([
                    'product_id' => $product->id,
                    'attribute_id' => $attributeId,
                    'attribute_value_id' => $values,
                    'custom_value' => null
                ]);
            }
        }
    }

    /**
     * ИСПРАВЛЕНО: Правила валидации
     */
    private function getValidationRules(?int $excludeId = null): array
    {
        return [
            'name' => 'required|string|max:255',
            'description' => 'required|string',
            'price' => 'required|numeric|min:0',
            'use_matryoshka' => 'boolean',
            'image_layers' => 'array',
            'image_layers.outer' => 'nullable|string',
            'image_layers.inner' => 'nullable|string',
            'gallery_images' => 'array',
            'gallery_images.*' => 'nullable|string',
            'theme_id' => 'required|exists:themes,id',
            'category_ids' => 'array',
            'category_ids.*' => 'exists:categories,id',
            'attribute_values' => 'array'
        ];
    }
}