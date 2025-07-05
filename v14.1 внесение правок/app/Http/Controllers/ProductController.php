<?php

namespace App\Http\Controllers;

use App\Models\Product;
use App\Models\ProductAttribute;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
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
            
            return response()->json([
                'error' => 'Ошибка создания товара',
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
            
            return response()->json([
                'error' => 'Ошибка обновления товара',
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

    /**
     * ИСПРАВЛЕНО: Получение товара с атрибутами
     * GET /api/admin/products/{id}
     */
    public function show(Product $product): JsonResponse
    {
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
    }
}