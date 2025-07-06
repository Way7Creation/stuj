<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceMap;
use App\Models\Product;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MarketplaceController extends Controller
{
    // Конфигурация маркетплейсов
    private $marketplaces = [
        'wildberries' => [
            'name' => 'Wildberries',
            'api_url' => 'https://suppliers-api.wildberries.ru',
            'requires' => ['api_key']
        ],
        'ozon' => [
            'name' => 'Ozon',
            'api_url' => 'https://api-seller.ozon.ru',
            'requires' => ['client_id', 'api_key']
        ],
        'yandex_market' => [
            'name' => 'Яндекс.Маркет',
            'api_url' => 'https://api.partner.market.yandex.ru',
            'requires' => ['oauth_token', 'campaign_id']
        ],
        'flowwow' => [
            'name' => 'Flowwow',
            'api_url' => 'https://api.flowwow.com',
            'requires' => ['api_key']
        ]
    ];

    /**
     * Получить список маркетплейсов и их настройки
     */
    public function index()
    {
        try {
            $settings = [];
            
            foreach ($this->marketplaces as $key => $marketplace) {
                $settings[] = [
                    'id' => $key,
                    'name' => $marketplace['name'],
                    'enabled' => $this->isMarketplaceEnabled($key),
                    'configured' => $this->isMarketplaceConfigured($key),
                    'last_sync' => $this->getLastSyncTime($key),
                    'products_count' => $this->getSyncedProductsCount($key)
                ];
            }
            
            return response()->json([
                'marketplaces' => $settings
            ]);
            
        } catch (\Exception $e) {
            Log::error('Ошибка получения маркетплейсов: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка получения данных'
            ], 500);
        }
    }

    /**
     * Получить маппинг атрибутов для маркетплейса
     */
    public function getMappings($marketplace)
    {
        try {
            if (!isset($this->marketplaces[$marketplace])) {
                return response()->json([
                    'error' => 'Неизвестный маркетплейс'
                ], 404);
            }
            
            $mappings = MarketplaceMap::where('marketplace', $marketplace)
                ->with('attribute')
                ->get();
            
            // Получаем все наши атрибуты
            $ourAttributes = Attribute::all();
            
            // Получаем атрибуты маркетплейса
            $marketplaceAttributes = $this->getMarketplaceAttributes($marketplace);
            
            return response()->json([
                'mappings' => $mappings,
                'our_attributes' => $ourAttributes,
                'marketplace_attributes' => $marketplaceAttributes
            ]);
            
        } catch (\Exception $e) {
            Log::error('Ошибка получения маппинга: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка получения данных'
            ], 500);
        }
    }

    /**
     * Сохранить маппинг атрибутов
     */
    public function saveMapping(Request $request)
    {
        $request->validate([
            'marketplace' => 'required|in:wildberries,ozon,yandex_market,flowwow',
            'our_attr_id' => 'required|exists:attributes,id',
            'marketplace_attr_name' => 'required|string'
        ]);

        try {
            $mapping = MarketplaceMap::updateOrCreate(
                [
                    'marketplace' => $request->marketplace,
                    'our_attr_id' => $request->our_attr_id
                ],
                [
                    'marketplace_attr_name' => $request->marketplace_attr_name
                ]
            );
            
            return response()->json([
                'message' => 'Маппинг сохранен',
                'mapping' => $mapping->load('attribute')
            ]);
            
        } catch (\Exception $e) {
            Log::error('Ошибка сохранения маппинга: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка сохранения'
            ], 500);
        }
    }

    /**
     * Удалить маппинг
     */
    public function deleteMapping($id)
    {
        try {
            $mapping = MarketplaceMap::findOrFail($id);
            $mapping->delete();
            
            return response()->json([
                'message' => 'Маппинг удален'
            ]);
            
        } catch (\Exception $e) {
            Log::error('Ошибка удаления маппинга: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка удаления'
            ], 500);
        }
    }

    /**
     * Синхронизация товаров с маркетплейсом
     */
    public function sync(Request $request)
    {
        $request->validate([
            'marketplace' => 'required|in:wildberries,ozon,yandex_market,flowwow',
            'product_ids' => 'nullable|array',
            'product_ids.*' => 'exists:products,id'
        ]);

        try {
            $marketplace = $request->marketplace;
            
            // Проверяем настройки
            if (!$this->isMarketplaceConfigured($marketplace)) {
                return response()->json([
                    'error' => 'Маркетплейс не настроен'
                ], 400);
            }
            
            // Получаем товары для синхронизации
            $query = Product::with(['theme', 'categories', 'attributes']);
            if ($request->filled('product_ids')) {
                $query->whereIn('id', $request->product_ids);
            }
            $products = $query->get();
            
            // Синхронизируем каждый товар
            $results = [
                'success' => 0,
                'failed' => 0,
                'errors' => []
            ];
            
            foreach ($products as $product) {
                try {
                    $this->syncProduct($marketplace, $product);
                    $results['success']++;
                } catch (\Exception $e) {
                    $results['failed']++;
                    $results['errors'][] = [
                        'product_id' => $product->id,
                        'product_name' => $product->name,
                        'error' => $e->getMessage()
                    ];
                }
            }
            
            // Обновляем время последней синхронизации
            $this->updateLastSyncTime($marketplace);
            
            return response()->json([
                'message' => 'Синхронизация завершена',
                'results' => $results
            ]);
            
        } catch (\Exception $e) {
            Log::error('Ошибка синхронизации: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка синхронизации',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Получить статистику синхронизации
     */
    public function getSyncStats($marketplace)
    {
        try {
            if (!isset($this->marketplaces[$marketplace])) {
                return response()->json([
                    'error' => 'Неизвестный маркетплейс'
                ], 404);
            }
            
            // Здесь можно добавить реальную статистику из БД
            $stats = [
                'total_products' => Product::count(),
                'synced_products' => rand(10, 50), // Заглушка
                'last_sync' => $this->getLastSyncTime($marketplace),
                'sync_errors' => rand(0, 5), // Заглушка
                'pending_updates' => rand(0, 10) // Заглушка
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
     * Получить лог синхронизации
     */
    public function getSyncLog(Request $request)
    {
        try {
            // Здесь должна быть реальная логика получения логов
            // Пока возвращаем заглушку
            $logs = [
                [
                    'id' => 1,
                    'marketplace' => 'wildberries',
                    'action' => 'sync',
                    'status' => 'success',
                    'message' => 'Синхронизировано 25 товаров',
                    'created_at' => now()->subHours(2)
                ],
                [
                    'id' => 2,
                    'marketplace' => 'ozon',
                    'action' => 'update_price',
                    'status' => 'error',
                    'message' => 'Ошибка обновления цены для товара ID:15',
                    'created_at' => now()->subHours(5)
                ]
            ];
            
            return response()->json([
                'logs' => $logs
            ]);
            
        } catch (\Exception $e) {
            Log::error('Ошибка получения логов: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка получения логов'
            ], 500);
        }
    }

    // Вспомогательные методы
    
    private function isMarketplaceEnabled($marketplace)
    {
        // Проверяем включен ли маркетплейс в настройках
        return config("marketplaces.{$marketplace}.enabled", false);
    }

    private function isMarketplaceConfigured($marketplace)
    {
        $required = $this->marketplaces[$marketplace]['requires'] ?? [];
        
        foreach ($required as $field) {
            $envKey = strtoupper($marketplace) . '_' . strtoupper($field);
            if (empty(env($envKey))) {
                return false;
            }
        }
        
        return true;
    }

    private function getLastSyncTime($marketplace)
    {
        // Здесь должна быть логика получения из БД
        return now()->subHours(rand(1, 24))->format('Y-m-d H:i:s');
    }

    private function getSyncedProductsCount($marketplace)
    {
        // Здесь должна быть реальная логика подсчета
        return rand(10, 100);
    }

    private function updateLastSyncTime($marketplace)
    {
        // Сохраняем время последней синхронизации
        // Можно использовать отдельную таблицу или cache
    }

    private function getMarketplaceAttributes($marketplace)
    {
        // Заглушка для атрибутов маркетплейсов
        $attributes = [
            'wildberries' => [
                ['id' => 'brand', 'name' => 'Бренд'],
                ['id' => 'material', 'name' => 'Материал'],
                ['id' => 'color', 'name' => 'Цвет'],
                ['id' => 'size', 'name' => 'Размер'],
                ['id' => 'weight', 'name' => 'Вес']
            ],
            'ozon' => [
                ['id' => 'brand', 'name' => 'Бренд'],
                ['id' => 'material', 'name' => 'Состав'],
                ['id' => 'color', 'name' => 'Цвет товара'],
                ['id' => 'product_weight', 'name' => 'Вес товара']
            ],
            'yandex_market' => [
                ['id' => 'vendor', 'name' => 'Производитель'],
                ['id' => 'material', 'name' => 'Материал'],
                ['id' => 'color', 'name' => 'Цвет']
            ],
            'flowwow' => [
                ['id' => 'flower_type', 'name' => 'Тип цветов'],
                ['id' => 'bouquet_size', 'name' => 'Размер букета'],
                ['id' => 'occasion', 'name' => 'Повод']
            ]
        ];

        return $attributes[$marketplace] ?? [];
    }

    private function syncProduct($marketplace, Product $product)
    {
        // Здесь должна быть реальная логика синхронизации
        // с API маркетплейса
        
        // Пример для Wildberries
        if ($marketplace === 'wildberries') {
            $this->syncToWildberries($product);
        }
        // И так далее для других маркетплейсов
    }

    private function syncToWildberries(Product $product)
    {
        // Реализация синхронизации с Wildberries API
        $apiKey = env('WILDBERRIES_API_KEY');
        
        // Подготовка данных товара
        $data = [
            'nmId' => $product->id,
            'vendorCode' => $product->slug,
            'brand' => 'Стужа',
            'object' => $product->name,
            'description' => $product->description,
            'price' => $product->price,
            // Маппинг атрибутов
            'characteristics' => $this->mapAttributesForMarketplace('wildberries', $product)
        ];
        
        // Отправка запроса
        // Http::withToken($apiKey)->post(...);
    }

    private function mapAttributesForMarketplace($marketplace, Product $product)
    {
        $mappings = MarketplaceMap::where('marketplace', $marketplace)->get();
        $result = [];
        
        foreach ($mappings as $mapping) {
            $productAttribute = $product->attributes()
                ->where('attribute_id', $mapping->our_attr_id)
                ->first();
                
            if ($productAttribute) {
                $result[$mapping->marketplace_attr_name] = $productAttribute->pivot->value;
            }
        }
        
        return $result;
    }
}