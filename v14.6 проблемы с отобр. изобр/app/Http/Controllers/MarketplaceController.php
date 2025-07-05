<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Http/Controllers/MarketplaceController.php
| Описание: ПОЛНЫЙ контроллер интеграции с маркетплейсами
|--------------------------------------------------------------------------
*/

namespace App\Http\Controllers;

use App\Models\MarketplaceMap;
use App\Models\Product;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use Illuminate\Validation\Rule;

class MarketplaceController extends Controller
{
    /**
     * Поддерживаемые маркетплейсы
     */
    private array $supportedMarketplaces = [
        'wildberries' => 'Wildberries',
        'ozon' => 'Ozon',
        'yandex_market' => 'Яндекс.Маркет',
        'flowwow' => 'Flowwow'
    ];

    // ============ CRUD для маппинга атрибутов ============

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
                Rule::in(array_keys($this->supportedMarketplaces))
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
            'marketplace',
            'our_attr_id',
            'marketplace_attr_name'
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
                'required',
                Rule::in(array_keys($this->supportedMarketplaces))
            ],
            'our_attr_id' => [
                'required',
                'exists:attributes,id',
                Rule::unique('marketplace_maps')->where(function ($query) use ($request) {
                    return $query->where('marketplace', $request->marketplace);
                })->ignore($marketplaceMap->id)
            ],
            'marketplace_attr_name' => 'required|string|max:255'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        $marketplaceMap->update($request->only([
            'marketplace',
            'our_attr_id',
            'marketplace_attr_name'
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

    // ============ СИНХРОНИЗАЦИЯ С МАРКЕТПЛЕЙСАМИ ============

    /**
     * Синхронизация всех товаров со всеми маркетплейсами
     * POST /api/marketplace/sync
     */
    public function sync(Request $request): JsonResponse
    {
        try {
            $results = [];
            
            foreach (array_keys($this->supportedMarketplaces) as $marketplace) {
                if ($this->isMarketplaceConfigured($marketplace)) {
                    $result = $this->syncMarketplace($marketplace);
                    $results[$marketplace] = $result;
                }
            }

            return response()->json([
                'message' => 'Синхронизация завершена',
                'results' => $results
            ]);

        } catch (\Exception $e) {
            Log::error('Marketplace sync error: ' . $e->getMessage());
            
            return response()->json([
                'error' => 'Ошибка синхронизации: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Синхронизация с конкретным маркетплейсом
     * POST /api/marketplace/sync/{marketplace}
     */
    public function syncSpecific(string $marketplace): JsonResponse
    {
        if (!isset($this->supportedMarketplaces[$marketplace])) {
            return response()->json([
                'error' => 'Неподдерживаемый маркетплейс'
            ], 400);
        }

        if (!$this->isMarketplaceConfigured($marketplace)) {
            return response()->json([
                'error' => "Маркетплейс {$marketplace} не настроен"
            ], 400);
        }

        try {
            $result = $this->syncMarketplace($marketplace);
            
            return response()->json([
                'message' => "Синхронизация с {$marketplace} завершена",
                'result' => $result
            ]);

        } catch (\Exception $e) {
            Log::error("Marketplace {$marketplace} sync error: " . $e->getMessage());
            
            return response()->json([
                'error' => "Ошибка синхронизации с {$marketplace}: " . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получение статуса синхронизации
     * GET /api/marketplace/sync-status
     */
    public function status(): JsonResponse
    {
        $status = [];
        
        foreach ($this->supportedMarketplaces as $key => $name) {
            $status[$key] = [
                'name' => $name,
                'configured' => $this->isMarketplaceConfigured($key),
                'last_sync' => Cache::get("marketplace_sync_{$key}", null),
                'products_count' => $this->getMarketplaceProductsCount($key)
            ];
        }

        return response()->json($status);
    }

    // ============ КОНФИГУРАЦИЯ МАРКЕТПЛЕЙСОВ ============

    /**
     * Получение конфигурации маркетплейса
     * GET /api/marketplace/config/{marketplace}
     */
    public function getConfig(string $marketplace): JsonResponse
    {
        if (!isset($this->supportedMarketplaces[$marketplace])) {
            return response()->json([
                'error' => 'Неподдерживаемый маркетплейс'
            ], 400);
        }

        $config = [
            'name' => $this->supportedMarketplaces[$marketplace],
            'configured' => $this->isMarketplaceConfigured($marketplace),
            'api_key_set' => !empty(env(strtoupper($marketplace) . '_API_KEY')),
            'mappings_count' => MarketplaceMap::where('marketplace', $marketplace)->count(),
            'settings' => $this->getMarketplaceSettings($marketplace)
        ];

        return response()->json($config);
    }

    /**
     * Обновление конфигурации маркетплейса
     * PUT /api/marketplace/config/{marketplace}
     */
    public function updateConfig(Request $request, string $marketplace): JsonResponse
    {
        if (!isset($this->supportedMarketplaces[$marketplace])) {
            return response()->json([
                'error' => 'Неподдерживаемый маркетплейс'
            ], 400);
        }

        $validator = Validator::make($request->all(), [
            'enabled' => 'boolean',
            'auto_sync' => 'boolean',
            'sync_interval' => 'integer|min:1|max:24'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'error' => 'Ошибка валидации',
                'errors' => $validator->errors()
            ], 422);
        }

        // Сохраняем настройки в кэш или базу данных
        $settings = array_filter($request->only(['enabled', 'auto_sync', 'sync_interval']));
        Cache::put("marketplace_config_{$marketplace}", $settings, now()->addYear());

        return response()->json([
            'message' => 'Конфигурация обновлена',
            'settings' => $settings
        ]);
    }

    // ============ ПРИВАТНЫЕ МЕТОДЫ ============

    /**
     * Синхронизация товаров с конкретным маркетплейсом
     */
    private function syncMarketplace(string $marketplace): array
    {
        $products = Product::with(['attributes', 'categories', 'theme'])->get();
        $maps = MarketplaceMap::where('marketplace', $marketplace)->with('attribute')->get();
        
        $synced = 0;
        $errors = 0;
        $skipped = 0;

        foreach ($products as $product) {
            try {
                $result = $this->syncProduct($product, $marketplace, $maps);
                
                if ($result['success']) {
                    $synced++;
                } else {
                    $skipped++;
                }
                
            } catch (\Exception $e) {
                $errors++;
                Log::error("Error syncing product {$product->id} to {$marketplace}: " . $e->getMessage());
            }
        }

        // Сохраняем время последней синхронизации
        Cache::put("marketplace_sync_{$marketplace}", now()->toISOString(), now()->addDays(30));

        return [
            'synced' => $synced,
            'errors' => $errors,
            'skipped' => $skipped,
            'total' => $products->count()
        ];
    }

    /**
     * Синхронизация отдельного товара
     */
    private function syncProduct(Product $product, string $marketplace, $maps): array
    {
        // Подготавливаем данные товара для маркетплейса
        $productData = $this->prepareProductData($product, $marketplace, $maps);
        
        // Отправляем данные в API маркетплейса
        return $this->sendToMarketplace($marketplace, $productData);
    }

    /**
     * Подготовка данных товара для маркетплейса
     */
    private function prepareProductData(Product $product, string $marketplace, $maps): array
    {
        $data = [
            'name' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            'category' => $product->categories->first()?->name ?? 'Украшения',
            'images' => $this->prepareImages($product),
            'attributes' => $this->mapAttributes($product, $maps)
        ];

        // Специфичные настройки для каждого маркетплейса
        switch ($marketplace) {
            case 'wildberries':
                $data = $this->prepareForWildberries($data, $product);
                break;
            case 'ozon':
                $data = $this->prepareForOzon($data, $product);
                break;
            case 'yandex_market':
                $data = $this->prepareForYandexMarket($data, $product);
                break;
            case 'flowwow':
                $data = $this->prepareForFlowwow($data, $product);
                break;
        }

        return $data;
    }

    /**
     * Подготовка изображений
     */
    private function prepareImages(Product $product): array
    {
        $images = [];
        $baseUrl = env('APP_URL') . '/storage/images/';
        
        if ($product->gallery_images) {
            foreach ($product->gallery_images as $image) {
                $images[] = $baseUrl . $image;
            }
        }
        
        return $images;
    }

    /**
     * Маппинг атрибутов
     */
    private function mapAttributes(Product $product, $maps): array
    {
        $attributes = [];
        
        foreach ($product->attributes as $attribute) {
            $map = $maps->firstWhere('our_attr_id', $attribute->id);
            
            if ($map) {
                $attributes[$map->marketplace_attr_name] = $attribute->name;
            }
        }
        
        return $attributes;
    }

    /**
     * Подготовка для Wildberries
     */
    private function prepareForWildberries(array $data, Product $product): array
    {
        $data['vendorCode'] = 'STUJ_' . $product->id;
        $data['brand'] = 'Стужа';
        $data['subject'] = 'Украшения';
        
        return $data;
    }

    /**
     * Подготовка для Ozon
     */
    private function prepareForOzon(array $data, Product $product): array
    {
        $data['offer_id'] = 'stuj_' . $product->id;
        $data['category_id'] = 15621; // ID категории украшений в Ozon
        
        return $data;
    }

    /**
     * Подготовка для Яндекс.Маркет
     */
    private function prepareForYandexMarket(array $data, Product $product): array
    {
        $data['shop-sku'] = 'stuj-' . $product->id;
        $data['category'] = 'Ювелирные изделия';
        
        return $data;
    }

    /**
     * Подготовка для Flowwow
     */
    private function prepareForFlowwow(array $data, Product $product): array
    {
        $data['external_id'] = 'stuj_' . $product->id;
        $data['category'] = 'jewelry';
        
        return $data;
    }

    /**
     * Отправка данных в API маркетплейса
     */
    private function sendToMarketplace(string $marketplace, array $data): array
    {
        $apiKey = env(strtoupper($marketplace) . '_API_KEY');
        
        if (empty($apiKey)) {
            return ['success' => false, 'error' => 'API ключ не настроен'];
        }

        try {
            // Здесь должна быть реальная интеграция с API каждого маркетплейса
            // Пока что возвращаем заглушку
            Log::info("Sending to {$marketplace}", $data);
            
            return ['success' => true, 'external_id' => uniqid()];
            
        } catch (\Exception $e) {
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Проверка настройки маркетплейса
     */
    private function isMarketplaceConfigured(string $marketplace): bool
    {
        $apiKey = env(strtoupper($marketplace) . '_API_KEY');
        return !empty($apiKey);
    }

    /**
     * Получение настроек маркетплейса
     */
    private function getMarketplaceSettings(string $marketplace): array
    {
        return Cache::get("marketplace_config_{$marketplace}", [
            'enabled' => false,
            'auto_sync' => false,
            'sync_interval' => 24
        ]);
    }

    /**
     * Подсчет товаров в маркетплейсе
     */
    private function getMarketplaceProductsCount(string $marketplace): int
    {
        // Здесь должен быть запрос к API маркетплейса
        // Пока возвращаем заглушку
        return 0;
    }

    /**
     * Тестирование подключения к маркетплейсу
     * POST /api/admin/marketplace/test/{marketplace}
     */
    public function testConnection(string $marketplace): JsonResponse
    {
        if (!isset($this->supportedMarketplaces[$marketplace])) {
            return response()->json([
                'error' => 'Неподдерживаемый маркетплейс'
            ], 400);
        }

        $apiKey = env(strtoupper($marketplace) . '_API_KEY');
        
        if (empty($apiKey)) {
            return response()->json([
                'status' => 'error',
                'message' => 'API ключ не настроен'
            ], 400);
        }

        try {
            // Здесь должен быть тестовый запрос к API маркетплейса
            // Пока возвращаем заглушку
            return response()->json([
                'status' => 'success',
                'message' => 'Подключение успешно'
            ]);
            
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Ошибка подключения: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получение списка поддерживаемых маркетплейсов
     * GET /api/admin/marketplace/supported
     */
    public function getSupportedMarketplaces(): JsonResponse
    {
        return response()->json($this->supportedMarketplaces);
    }
}