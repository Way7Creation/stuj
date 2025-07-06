<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceMap;
use App\Models\Product;
use App\Models\Attribute;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Http;

class MarketplaceController extends Controller
{
    /**
     * Список маппингов
     * GET /api/admin/marketplace-maps
     */
    public function indexMaps(Request $request)
    {
        try {
            $query = MarketplaceMap::with('attribute');
            
            // Фильтр по маркетплейсу
            if ($request->filled('marketplace')) {
                $query->where('marketplace', $request->marketplace);
            }
            
            $maps = $query->get()->groupBy('marketplace');
            
            return response()->json([
                'data' => $maps
            ]);
            
        } catch (\Exception $e) {
            Log::error('Ошибка получения маппингов: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка получения данных'
            ], 500);
        }
    }

    /**
     * Создание маппинга
     * POST /api/admin/marketplace-maps
     */
    public function storeMap(Request $request)
    {
        $request->validate([
            'marketplace' => 'required|in:wildberries,ozon,yandex_market,flowwow',
            'mapping_type' => 'required|in:category,attribute',
            'our_id' => 'required|integer',
            'marketplace_id' => 'required|string',
            'marketplace_name' => 'required|string',
            'additional_data' => 'nullable|array'
        ]);

        try {
            $map = MarketplaceMap::create([
                'marketplace' => $request->marketplace,
                'mapping_type' => $request->mapping_type,
                'our_id' => $request->our_id,
                'marketplace_id' => $request->marketplace_id,
                'marketplace_name' => $request->marketplace_name,
                'additional_data' => $request->additional_data
            ]);
            
            return response()->json([
                'message' => 'Маппинг создан',
                'data' => $map->load('attribute')
            ], 201);
            
        } catch (\Exception $e) {
            Log::error('Ошибка создания маппинга: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка создания'
            ], 500);
        }
    }

    /**
     * Показать маппинг
     * GET /api/admin/marketplace-maps/{id}
     */
    public function showMap(MarketplaceMap $marketplaceMap)
    {
        try {
            return response()->json([
                'data' => $marketplaceMap->load('attribute')
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Маппинг не найден'
            ], 404);
        }
    }

    /**
     * Обновление маппинга
     * PUT /api/admin/marketplace-maps/{id}
     */
    public function updateMap(Request $request, MarketplaceMap $marketplaceMap)
    {
        $request->validate([
            'marketplace_id' => 'sometimes|string',
            'marketplace_name' => 'sometimes|string',
            'additional_data' => 'nullable|array'
        ]);

        try {
            $marketplaceMap->update($request->only([
                'marketplace_id',
                'marketplace_name',
                'additional_data'
            ]));
            
            return response()->json([
                'message' => 'Маппинг обновлен',
                'data' => $marketplaceMap->load('attribute')
            ]);
            
        } catch (\Exception $e) {
            Log::error('Ошибка обновления маппинга: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка обновления'
            ], 500);
        }
    }

    /**
     * Удаление маппинга
     * DELETE /api/admin/marketplace-maps/{id}
     */
    public function destroyMap(MarketplaceMap $marketplaceMap)
    {
        try {
            $marketplaceMap->delete();
            
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
     * Загрузить категории с маркетплейса
     * GET /api/admin/marketplace-maps/load-categories/{marketplace}
     */
    public function loadCategories($marketplace)
    {
        try {
            $categories = [];
            
            switch ($marketplace) {
                case 'wildberries':
                    $categories = $this->loadWildberriesCategories();
                    break;
                case 'ozon':
                    $categories = $this->loadOzonCategories();
                    break;
                case 'yandex_market':
                    $categories = $this->loadYandexCategories();
                    break;
                case 'flowwow':
                    $categories = $this->loadFlowwowCategories();
                    break;
                default:
                    return response()->json([
                        'error' => 'Неизвестный маркетплейс'
                    ], 404);
            }
            
            return response()->json([
                'data' => $categories
            ]);
            
        } catch (\Exception $e) {
            Log::error('Ошибка загрузки категорий: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка загрузки категорий'
            ], 500);
        }
    }

    /**
     * Загрузить атрибуты с маркетплейса
     * GET /api/admin/marketplace-maps/load-attributes/{marketplace}/{categoryId?}
     */
    public function loadAttributes($marketplace, $categoryId = null)
    {
        try {
            $attributes = [];
            
            switch ($marketplace) {
                case 'wildberries':
                    $attributes = $this->loadWildberriesAttributes($categoryId);
                    break;
                case 'ozon':
                    $attributes = $this->loadOzonAttributes($categoryId);
                    break;
                case 'yandex_market':
                    $attributes = $this->loadYandexAttributes($categoryId);
                    break;
                case 'flowwow':
                    $attributes = $this->loadFlowwowAttributes($categoryId);
                    break;
                default:
                    return response()->json([
                        'error' => 'Неизвестный маркетплейс'
                    ], 404);
            }
            
            return response()->json([
                'data' => $attributes
            ]);
            
        } catch (\Exception $e) {
            Log::error('Ошибка загрузки атрибутов: ' . $e->getMessage());
            return response()->json([
                'error' => 'Ошибка загрузки атрибутов'
            ], 500);
        }
    }

    /**
     * Тестирование подключения к маркетплейсу
     * POST /api/admin/marketplace/test-connection/{marketplace}
     */
    public function testConnection($marketplace)
    {
        try {
            $result = false;
            $message = '';
            
            switch ($marketplace) {
                case 'wildberries':
                    $result = $this->testWildberriesConnection();
                    break;
                case 'ozon':
                    $result = $this->testOzonConnection();
                    break;
                case 'yandex_market':
                    $result = $this->testYandexConnection();
                    break;
                case 'flowwow':
                    $result = $this->testFlowwowConnection();
                    break;
                default:
                    return response()->json([
                        'error' => 'Неизвестный маркетплейс'
                    ], 404);
            }
            
            if ($result) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Подключение успешно'
                ]);
            } else {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Не удалось подключиться к маркетплейсу'
                ], 400);
            }
            
        } catch (\Exception $e) {
            Log::error('Ошибка тестирования подключения: ' . $e->getMessage());
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    // Приватные методы для работы с API маркетплейсов

    private function loadWildberriesCategories()
    {
        $apiKey = env('WILDBERRIES_API_KEY');
        
        try {
            $response = Http::withHeaders([
                'Authorization' => $apiKey
            ])->get('https://suppliers-api.wildberries.ru/content/v1/object/parent-all');
            
            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }
            
            return [];
        } catch (\Exception $e) {
            Log::error('Ошибка загрузки категорий Wildberries: ' . $e->getMessage());
            return [];
        }
    }

    private function loadWildberriesAttributes($categoryId)
    {
        $apiKey = env('WILDBERRIES_API_KEY');
        
        try {
            $url = 'https://suppliers-api.wildberries.ru/content/v1/object/characteristics/list';
            if ($categoryId) {
                $url .= '?subjectId=' . $categoryId;
            }
            
            $response = Http::withHeaders([
                'Authorization' => $apiKey
            ])->get($url);
            
            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }
            
            return [];
        } catch (\Exception $e) {
            Log::error('Ошибка загрузки атрибутов Wildberries: ' . $e->getMessage());
            return [];
        }
    }

    private function loadOzonCategories()
    {
        $clientId = env('OZON_CLIENT_ID');
        $apiKey = env('OZON_API_KEY');
        
        try {
            $response = Http::withHeaders([
                'Client-Id' => $clientId,
                'Api-Key' => $apiKey
            ])->post('https://api-seller.ozon.ru/v2/category/tree');
            
            if ($response->successful()) {
                return $response->json()['result'] ?? [];
            }
            
            return [];
        } catch (\Exception $e) {
            Log::error('Ошибка загрузки категорий Ozon: ' . $e->getMessage());
            return [];
        }
    }

    private function loadOzonAttributes($categoryId)
    {
        $clientId = env('OZON_CLIENT_ID');
        $apiKey = env('OZON_API_KEY');
        
        try {
            $response = Http::withHeaders([
                'Client-Id' => $clientId,
                'Api-Key' => $apiKey
            ])->post('https://api-seller.ozon.ru/v3/category/attribute', [
                'category_id' => [$categoryId]
            ]);
            
            if ($response->successful()) {
                return $response->json()['result'][0]['attributes'] ?? [];
            }
            
            return [];
        } catch (\Exception $e) {
            Log::error('Ошибка загрузки атрибутов Ozon: ' . $e->getMessage());
            return [];
        }
    }

    private function loadYandexCategories()
    {
        $token = env('YANDEX_MARKET_OAUTH_TOKEN');
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'OAuth ' . $token
            ])->get('https://api.partner.market.yandex.ru/v2/categories');
            
            if ($response->successful()) {
                return $response->json()['categories'] ?? [];
            }
            
            return [];
        } catch (\Exception $e) {
            Log::error('Ошибка загрузки категорий Яндекс.Маркет: ' . $e->getMessage());
            return [];
        }
    }

    private function loadYandexAttributes($categoryId)
    {
        // Яндекс.Маркет не предоставляет API для получения атрибутов категории
        // Возвращаем стандартный набор атрибутов
        return [
            ['id' => 'vendor', 'name' => 'Производитель', 'required' => true],
            ['id' => 'model', 'name' => 'Модель', 'required' => true],
            ['id' => 'description', 'name' => 'Описание', 'required' => false],
            ['id' => 'barcode', 'name' => 'Штрихкод', 'required' => false],
            ['id' => 'weight', 'name' => 'Вес', 'required' => false],
            ['id' => 'dimensions', 'name' => 'Размеры', 'required' => false]
        ];
    }

    private function loadFlowwowCategories()
    {
        $apiKey = env('FLOWWOW_API_KEY');
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey
            ])->get('https://api.flowwow.com/v1/categories');
            
            if ($response->successful()) {
                return $response->json()['data'] ?? [];
            }
            
            return [];
        } catch (\Exception $e) {
            Log::error('Ошибка загрузки категорий Flowwow: ' . $e->getMessage());
            return [];
        }
    }

    private function loadFlowwowAttributes($categoryId)
    {
        // Flowwow имеет фиксированный набор атрибутов для цветов
        return [
            ['id' => 'flower_type', 'name' => 'Тип цветов', 'required' => true],
            ['id' => 'color', 'name' => 'Цвет', 'required' => true],
            ['id' => 'size', 'name' => 'Размер букета', 'required' => true],
            ['id' => 'occasion', 'name' => 'Повод', 'required' => false],
            ['id' => 'packaging', 'name' => 'Упаковка', 'required' => false]
        ];
    }

    private function testWildberriesConnection()
    {
        $apiKey = env('WILDBERRIES_API_KEY');
        
        if (!$apiKey) {
            throw new \Exception('API ключ Wildberries не настроен');
        }
        
        try {
            $response = Http::withHeaders([
                'Authorization' => $apiKey
            ])->get('https://suppliers-api.wildberries.ru/public/api/v1/info');
            
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function testOzonConnection()
    {
        $clientId = env('OZON_CLIENT_ID');
        $apiKey = env('OZON_API_KEY');
        
        if (!$clientId || !$apiKey) {
            throw new \Exception('Учетные данные Ozon не настроены');
        }
        
        try {
            $response = Http::withHeaders([
                'Client-Id' => $clientId,
                'Api-Key' => $apiKey
            ])->post('https://api-seller.ozon.ru/v1/category/tree');
            
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function testYandexConnection()
    {
        $token = env('YANDEX_MARKET_OAUTH_TOKEN');
        $campaignId = env('YANDEX_MARKET_CAMPAIGN_ID');
        
        if (!$token || !$campaignId) {
            throw new \Exception('Учетные данные Яндекс.Маркет не настроены');
        }
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'OAuth ' . $token
            ])->get('https://api.partner.market.yandex.ru/v2/campaigns/' . $campaignId);
            
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }

    private function testFlowwowConnection()
    {
        $apiKey = env('FLOWWOW_API_KEY');
        
        if (!$apiKey) {
            throw new \Exception('API ключ Flowwow не настроен');
        }
        
        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . $apiKey
            ])->get('https://api.flowwow.com/v1/user');
            
            return $response->successful();
        } catch (\Exception $e) {
            return false;
        }
    }
}