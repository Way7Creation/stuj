<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Http/Controllers/AttributeValueController.php
| Описание: НОВЫЙ CRUD контроллер для значений атрибутов
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers;

use App\Models\Attribute;
use App\Models\AttributeValue;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class AttributeValueController extends Controller
{
    /**
     * Получить все значения для конкретного атрибута
     * GET /api/admin/attributes/{attribute}/values
     */
    public function index(Attribute $attribute): JsonResponse
    {
        $values = $attribute->values()
            ->withCount('productAttributes')
            ->orderBy('sort_order')
            ->orderBy('value')
            ->get()
            ->map(function ($value) {
                return [
                    'id' => $value->id,
                    'attribute_id' => $value->attribute_id,
                    'value' => $value->value,
                    'slug' => $value->slug,
                    'sort_order' => $value->sort_order,
                    'is_active' => $value->is_active,
                    'products_count' => $value->product_attributes_count,
                    'created_at' => $value->created_at,
                    'updated_at' => $value->updated_at
                ];
            });

        return response()->json([
            'attribute' => [
                'id' => $attribute->id,
                'name' => $attribute->name,
                'is_stone' => $attribute->isStone()
            ],
            'values' => $values
        ]);
    }

    /**
     * Создание нового значения атрибута
     * POST /api/admin/attributes/{attribute}/values
     */
    public function store(Request $request, Attribute $attribute): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean'
        ], [
            'value.required' => 'Значение атрибута обязательно',
            'value.max' => 'Значение не должно превышать 255 символов',
            'sort_order.min' => 'Порядок сортировки не может быть отрицательным'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        // Проверка уникальности значения для данного атрибута
        if ($attribute->values()->where('value', $request->value)->exists()) {
            return response()->json([
                'error' => 'Значение уже существует',
                'message' => 'Такое значение уже добавлено для этого атрибута'
            ], 422);
        }

        // Генерация слага если не указан
        $slug = $request->slug ?: Str::slug($request->value);
        $originalSlug = $slug;
        $counter = 1;

        // Проверка уникальности слага в рамках атрибута
        while ($attribute->values()->where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $counter;
            $counter++;
        }

        // Определение порядка сортировки
        $sortOrder = $request->sort_order;
        if (is_null($sortOrder)) {
            $maxOrder = $attribute->values()->max('sort_order') ?? 0;
            $sortOrder = $maxOrder + 10;
        }

        $value = AttributeValue::create([
            'attribute_id' => $attribute->id,
            'value' => $request->value,
            'slug' => $slug,
            'sort_order' => $sortOrder,
            'is_active' => $request->is_active ?? true
        ]);

        return response()->json([
            'message' => 'Значение атрибута успешно создано',
            'value' => [
                'id' => $value->id,
                'attribute_id' => $value->attribute_id,
                'value' => $value->value,
                'slug' => $value->slug,
                'sort_order' => $value->sort_order,
                'is_active' => $value->is_active,
                'products_count' => 0,
                'created_at' => $value->created_at,
                'updated_at' => $value->updated_at
            ]
        ], 201);
    }

    /**
     * Получение значения по ID
     * GET /api/admin/attribute-values/{value}
     */
    public function show(AttributeValue $value): JsonResponse
    {
        $value->load('attribute');
        $value->loadCount('productAttributes');

        return response()->json([
            'id' => $value->id,
            'attribute_id' => $value->attribute_id,
            'attribute' => [
                'id' => $value->attribute->id,
                'name' => $value->attribute->name,
                'is_stone' => $value->attribute->isStone()
            ],
            'value' => $value->value,
            'slug' => $value->slug,
            'sort_order' => $value->sort_order,
            'is_active' => $value->is_active,
            'products_count' => $value->product_attributes_count,
            'created_at' => $value->created_at,
            'updated_at' => $value->updated_at
        ]);
    }

    /**
     * Обновление значения атрибута
     * PUT /api/admin/attribute-values/{value}
     */
    public function update(Request $request, AttributeValue $value): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'value' => 'required|string|max:255',
            'slug' => 'nullable|string|max:255',
            'sort_order' => 'nullable|integer|min:0',
            'is_active' => 'nullable|boolean'
        ], [
            'value.required' => 'Значение атрибута обязательно',
            'value.max' => 'Значение не должно превышать 255 символов',
            'sort_order.min' => 'Порядок сортировки не может быть отрицательным'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        // Проверка уникальности значения для данного атрибута (исключая текущую запись)
        if ($value->attribute->values()
            ->where('value', $request->value)
            ->where('id', '!=', $value->id)
            ->exists()) {
            return response()->json([
                'error' => 'Значение уже существует',
                'message' => 'Такое значение уже добавлено для этого атрибута'
            ], 422);
        }

        // Обновление слага если изменилось значение
        $slug = $value->slug;
        if ($request->filled('slug')) {
            $slug = $request->slug;
        } elseif ($request->value !== $value->value) {
            $slug = Str::slug($request->value);
            $originalSlug = $slug;
            $counter = 1;

            while ($value->attribute->values()
                ->where('slug', $slug)
                ->where('id', '!=', $value->id)
                ->exists()) {
                $slug = $originalSlug . '-' . $counter;
                $counter++;
            }
        }

        $value->update([
            'value' => $request->value,
            'slug' => $slug,
            'sort_order' => $request->sort_order ?? $value->sort_order,
            'is_active' => $request->is_active ?? $value->is_active
        ]);

        $value->load('attribute');
        $value->loadCount('productAttributes');

        return response()->json([
            'message' => 'Значение атрибута успешно обновлено',
            'value' => [
                'id' => $value->id,
                'attribute_id' => $value->attribute_id,
                'attribute' => [
                    'id' => $value->attribute->id,
                    'name' => $value->attribute->name,
                    'is_stone' => $value->attribute->isStone()
                ],
                'value' => $value->value,
                'slug' => $value->slug,
                'sort_order' => $value->sort_order,
                'is_active' => $value->is_active,
                'products_count' => $value->product_attributes_count,
                'created_at' => $value->created_at,
                'updated_at' => $value->updated_at
            ]
        ]);
    }

    /**
     * Удаление значения атрибута
     * DELETE /api/admin/attribute-values/{value}
     */
    public function destroy(AttributeValue $value): JsonResponse
    {
        // Проверка использования значения в товарах
        if ($value->productAttributes()->exists()) {
            return response()->json([
                'error' => 'Невозможно удалить значение',
                'message' => 'Это значение используется в товарах. Сначала измените значения у товаров.'
            ], 409);
        }

        $value->delete();

        return response()->json([
            'message' => 'Значение атрибута успешно удалено'
        ]);
    }

    /**
     * Bulk операции со значениями
     * POST /api/admin/attributes/{attribute}/values/bulk-delete
     */
    public function bulkDelete(Request $request, Attribute $attribute): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:attribute_values,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        $values = AttributeValue::whereIn('id', $request->ids)
            ->where('attribute_id', $attribute->id)
            ->get();

        $deleted = 0;
        $errors = [];

        foreach ($values as $value) {
            if ($value->productAttributes()->exists()) {
                $errors[] = "Значение '{$value->value}' используется в товарах";
                continue;
            }

            $value->delete();
            $deleted++;
        }

        return response()->json([
            'message' => "Удалено значений: {$deleted}",
            'deleted_count' => $deleted,
            'errors' => $errors
        ]);
    }

    /**
     * Изменение порядка сортировки значений
     * POST /api/admin/attributes/{attribute}/values/reorder
     */
    public function reorder(Request $request, Attribute $attribute): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'values' => 'required|array',
            'values.*.id' => 'required|exists:attribute_values,id',
            'values.*.sort_order' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        foreach ($request->values as $valueData) {
            AttributeValue::where('id', $valueData['id'])
                ->where('attribute_id', $attribute->id)
                ->update(['sort_order' => $valueData['sort_order']]);
        }

        return response()->json([
            'message' => 'Порядок сортировки успешно обновлен'
        ]);
    }
}