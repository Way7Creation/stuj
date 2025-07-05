<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Http/Controllers/MarketplaceMapController.php
| Описание: CRUD контроллер для управления маппингом атрибутов маркетплейсов
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers;

use App\Models\MarketplaceMap;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class MarketplaceController extends Controller
{
    /**
     * Список всех маппингов
     * GET /api/admin/marketplace_maps
     */
    public function index(Request $request): JsonResponse
    {
        $query = MarketplaceMap::with('attribute');
        
        // Фильтр по маркетплейсу
        if ($request->filled('marketplace')) {
            $query->where('marketplace', $request->marketplace);
        }
        
        $maps = $query->orderBy('marketplace')
                     ->orderBy('marketplace_attr_name')
                     ->get();
        
        return response()->json($maps);
    }

    /**
     * Создание нового маппинга
     * POST /api/admin/marketplace_maps
     */
    public function store(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'marketplace' => [
                'required',
                Rule::in(['wildberries', 'ozon', 'yandex_market', 'flowwow'])
            ],
            'our_attr_id' => [
                'required',
                'exists:attributes,id',
                Rule::unique('marketplace_maps')->where(function ($query) use ($request) {
                    return $query->where('marketplace', $request->marketplace);
                })
            ],
            'marketplace_attr_name' => 'required|string|max:255'
        ], [
            'marketplace.required' => 'Маркетплейс обязателен',
            'marketplace.in' => 'Недопустимый маркетплейс',
            'our_attr_id.required' => 'Атрибут обязателен',
            'our_attr_id.exists' => 'Атрибут не найден',
            'our_attr_id.unique' => 'Маппинг для этого атрибута уже существует',
            'marketplace_attr_name.required' => 'Название атрибута в маркетплейсе обязательно'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        $map = MarketplaceMap::create($request->only([
            'marketplace', 'our_attr_id', 'marketplace_attr_name'
        ]));

        $map->load('attribute');

        return response()->json([
            'message' => 'Маппинг успешно создан',
            'map' => $map
        ], 201);
    }

    /**
     * Получение маппинга по ID
     * GET /api/admin/marketplace_maps/{id}
     */
    public function show(MarketplaceMap $marketplaceMap): JsonResponse
    {
        $marketplaceMap->load('attribute');
        return response()->json($marketplaceMap);
    }

    /**
     * Обновление маппинга
     * PUT /api/admin/marketplace_maps/{id}
     */
    public function update(Request $request, MarketplaceMap $marketplaceMap): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'marketplace' => [
                'sometimes',
                'required',
                Rule::in(['wildberries', 'ozon', 'yandex_market', 'flowwow'])
            ],
            'our_attr_id' => [
                'sometimes',
                'required',
                'exists:attributes,id',
                Rule::unique('marketplace_maps')->where(function ($query) use ($request, $marketplaceMap) {
                    return $query->where('marketplace', $request->marketplace ?? $marketplaceMap->marketplace)
                                 ->where('id', '!=', $marketplaceMap->id);
                })
            ],
            'marketplace_attr_name' => 'sometimes|required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        $marketplaceMap->update($request->only([
            'marketplace', 'our_attr_id', 'marketplace_attr_name'
        ]));

        $marketplaceMap->load('attribute');

        return response()->json([
            'message' => 'Маппинг успешно обновлен',
            'map' => $marketplaceMap
        ]);
    }

    /**
     * Удаление маппинга
     * DELETE /api/admin/marketplace_maps/{id}
     */
    public function destroy(MarketplaceMap $marketplaceMap): JsonResponse
    {
        $marketplaceMap->delete();

        return response()->json([
            'message' => 'Маппинг успешно удален'
        ]);
    }

    /**
     * Получение списка маркетплейсов
     * GET /api/admin/marketplace_maps/marketplaces
     */
    public function marketplaces(): JsonResponse
    {
        return response()->json([
            ['value' => 'wildberries', 'name' => 'Wildberries'],
            ['value' => 'ozon', 'name' => 'Ozon'],
            ['value' => 'yandex_market', 'name' => 'Яндекс.Маркет'],
            ['value' => 'flowwow', 'name' => 'Flowwow']
        ]);
    }
}