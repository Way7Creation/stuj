<?php

namespace App\Http\Controllers;

use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AttributeController extends Controller
{
    /**
     * ИСПРАВЛЕНО: Список всех атрибутов с их значениями
     * GET /api/admin/attributes
     */
    public function index(): JsonResponse
    {
        $attributes = Attribute::with(['values' => function ($query) {
                $query->where('is_active', true)->orderBy('sort_order')->orderBy('value');
            }])
            ->withCount('products')
            ->orderBy('name')
            ->get()
            ->map(function ($attribute) {
                return [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'is_stone' => $attribute->isStone(),
                    'products_count' => $attribute->products_count,
                    'values_count' => $attribute->values->count(),
                    'values' => $attribute->values->map(function ($value) {
                        return [
                            'id' => $value->id,
                            'value' => $value->value,
                            'slug' => $value->slug,
                            'sort_order' => $value->sort_order,
                            'is_active' => $value->is_active,
                            'products_count' => $value->products_count ?? 0
                        ];
                    }),
                    'created_at' => $attribute->created_at,
                    'updated_at' => $attribute->updated_at
                ];
            });
            
        return response()->json($attributes);
    }

    /**
     * ИСПРАВЛЕНО: Создание нового атрибута
     * POST /api/admin/attributes
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:attributes'
        ], [
            'name.required' => 'Название атрибута обязательно',
            'name.unique' => 'Атрибут с таким названием уже существует',
            'name.max' => 'Название не должно превышать 255 символов'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        $attribute = Attribute::create([
            'name' => $request->name
        ]);

        return response()->json([
            'message' => 'Атрибут успешно создан',
            'attribute' => [
                'id' => $attribute->id,
                'name' => $attribute->name,
                'is_stone' => $attribute->isStone(),
                'products_count' => 0,
                'values_count' => 0,
                'values' => [],
                'created_at' => $attribute->created_at,
                'updated_at' => $attribute->updated_at
            ]
        ], 201);
    }

    /**
     * ИСПРАВЛЕНО: Получение атрибута по ID с его значениями
     * GET /api/admin/attributes/{id}
     */
    public function show(Attribute $attribute): JsonResponse
    {
        $attribute->load(['values' => function ($query) {
            $query->orderBy('sort_order')->orderBy('value');
        }]);
        $attribute->loadCount('products');
        
        return response()->json([
            'id' => $attribute->id,
            'name' => $attribute->name,
            'is_stone' => $attribute->isStone(),
            'products_count' => $attribute->products_count,
            'values_count' => $attribute->values->count(),
            'values' => $attribute->values->map(function ($value) {
                return [
                    'id' => $value->id,
                    'value' => $value->value,
                    'slug' => $value->slug,
                    'sort_order' => $value->sort_order,
                    'is_active' => $value->is_active,
                    'products_count' => $value->products_count ?? 0
                ];
            }),
            'created_at' => $attribute->created_at,
            'updated_at' => $attribute->updated_at,
            'products' => $attribute->products()->select('id', 'name', 'slug')->get()
        ]);
    }

    /**
     * ИСПРАВЛЕНО: Обновление атрибута
     * PUT /api/admin/attributes/{id}
     */
    public function update(Request $request, Attribute $attribute): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255|unique:attributes,name,' . $attribute->id
        ], [
            'name.required' => 'Название атрибута обязательно',
            'name.unique' => 'Атрибут с таким названием уже существует',
            'name.max' => 'Название не должно превышать 255 символов'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        $attribute->update([
            'name' => $request->name
        ]);

        // Загружаем обновленные данные
        $attribute->load(['values' => function ($query) {
            $query->where('is_active', true)->orderBy('sort_order')->orderBy('value');
        }]);
        $attribute->loadCount('products');

        return response()->json([
            'message' => 'Атрибут успешно обновлен',
            'attribute' => [
                'id' => $attribute->id,
                'name' => $attribute->name,
                'is_stone' => $attribute->isStone(),
                'products_count' => $attribute->products_count,
                'values_count' => $attribute->values->count(),
                'values' => $attribute->values->map(function ($value) {
                    return [
                        'id' => $value->id,
                        'value' => $value->value,
                        'slug' => $value->slug,
                        'sort_order' => $value->sort_order,
                        'is_active' => $value->is_active,
                        'products_count' => $value->products_count ?? 0
                    ];
                }),
                'created_at' => $attribute->created_at,
                'updated_at' => $attribute->updated_at
            ]
        ]);
    }

    /**
     * ИСПРАВЛЕНО: Удаление атрибута
     * DELETE /api/admin/attributes/{id}
     */
    public function destroy(Attribute $attribute): JsonResponse
    {
        // Проверка наличия товаров с этим атрибутом
        if ($attribute->products()->exists()) {
            return response()->json([
                'error' => 'Невозможно удалить атрибут',
                'message' => 'К этому атрибуту привязаны товары. Сначала уберите атрибут у товаров.'
            ], 409);
        }

        // Проверка наличия значений атрибута
        if ($attribute->values()->exists()) {
            return response()->json([
                'error' => 'Невозможно удалить атрибут',
                'message' => 'У этого атрибута есть значения. Сначала удалите все значения.'
            ], 409);
        }

        // Проверка маппинга маркетплейсов
        if ($attribute->marketplaceMaps()->exists()) {
            return response()->json([
                'error' => 'Невозможно удалить атрибут',
                'message' => 'Этот атрибут используется в маппинге маркетплейсов.'
            ], 409);
        }

        $attribute->delete();

        return response()->json([
            'message' => 'Атрибут успешно удален'
        ]);
    }

    /**
     * НОВОЕ: Массовое удаление атрибутов
     * POST /api/admin/attributes/bulk-delete
     */
    public function bulkDelete(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'ids' => 'required|array|min:1',
            'ids.*' => 'exists:attributes,id'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        $attributes = Attribute::whereIn('id', $request->ids)->get();
        $deleted = 0;
        $errors = [];

        foreach ($attributes as $attribute) {
            if ($attribute->products()->exists()) {
                $errors[] = "Атрибут '{$attribute->name}' используется в товарах";
                continue;
            }

            if ($attribute->values()->exists()) {
                $errors[] = "У атрибута '{$attribute->name}' есть значения";
                continue;
            }

            if ($attribute->marketplaceMaps()->exists()) {
                $errors[] = "Атрибут '{$attribute->name}' используется в маппинге маркетплейсов";
                continue;
            }

            $attribute->delete();
            $deleted++;
        }

        return response()->json([
            'message' => "Удалено атрибутов: {$deleted}",
            'deleted_count' => $deleted,
            'errors' => $errors
        ]);
    }
}