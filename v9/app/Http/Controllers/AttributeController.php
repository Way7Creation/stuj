<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Http/Controllers/AttributeController.php
| Описание: CRUD контроллер для управления атрибутами (админка)
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers;

use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;

class AttributeController extends Controller
{
    /**
     * Список всех атрибутов
     * GET /api/admin/attributes
     */
    public function index(): JsonResponse
    {
        $attributes = Attribute::withCount('products')
            ->orderBy('name')
            ->get()
            ->map(function ($attribute) {
                return [
                    'id' => $attribute->id,
                    'name' => $attribute->name,
                    'is_stone' => $attribute->isStone(),
                    'products_count' => $attribute->products_count,
                    'created_at' => $attribute->created_at,
                    'updated_at' => $attribute->updated_at
                ];
            });
            
        return response()->json($attributes);
    }

    /**
     * Создание нового атрибута
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
                'created_at' => $attribute->created_at,
                'updated_at' => $attribute->updated_at
            ]
        ], 201);
    }

    /**
     * Получение атрибута по ID
     * GET /api/admin/attributes/{id}
     */
    public function show(Attribute $attribute): JsonResponse
    {
        $attribute->loadCount('products');
        
        return response()->json([
            'id' => $attribute->id,
            'name' => $attribute->name,
            'is_stone' => $attribute->isStone(),
            'products_count' => $attribute->products_count,
            'created_at' => $attribute->created_at,
            'updated_at' => $attribute->updated_at,
            'products' => $attribute->products()->select('id', 'name', 'slug')->get()
        ]);
    }

    /**
     * Обновление атрибута
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

        return response()->json([
            'message' => 'Атрибут успешно обновлен',
            'attribute' => [
                'id' => $attribute->id,
                'name' => $attribute->name,
                'is_stone' => $attribute->isStone(),
                'created_at' => $attribute->created_at,
                'updated_at' => $attribute->updated_at
            ]
        ]);
    }

    /**
     * Удаление атрибута
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

        // Проверка маппинга маркетплейсов
        if ($attribute->marketplaceMaps()->exists()) {
            return response()->json([
                'error' => 'Невозможно удалить атрибут',
                'message' => 'Этот атрибут используется в маппинге маркетплейсов.'
            ], 409);
        }

        $attributeName = $attribute->name;
        $attribute->delete();

        return response()->json([
            'message' => "Атрибут '{$attributeName}' успешно удален"
        ]);
    }

    /**
     * Получение списка камней для квиза
     * GET /api/admin/attributes/stones
     */
    public function stones(): JsonResponse
    {
        $stones = Attribute::all()
            ->filter(function ($attribute) {
                return $attribute->isStone();
            })
            ->values()
            ->map(function ($attribute) {
                return [
                    'id' => $attribute->id,
                    'name' => $attribute->name
                ];
            });

        return response()->json($stones);
    }
}