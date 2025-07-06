<?php

return [
    'wildberries' => [
        'enabled' => env('WILDBERRIES_ENABLED', false),
        'api_url' => 'https://suppliers-api.wildberries.ru',
        'api_key' => env('WILDBERRIES_API_KEY'),
    ],
    
    'ozon' => [
        'enabled' => env('OZON_ENABLED', false),
        'api_url' => 'https://api-seller.ozon.ru',
        'client_id' => env('OZON_CLIENT_ID'),
        'api_key' => env('OZON_API_KEY'),
    ],
    
    'yandex_market' => [
        'enabled' => env('YANDEX_MARKET_ENABLED', false),
        'api_url' => 'https://api.partner.market.yandex.ru',
        'oauth_token' => env('YANDEX_MARKET_OAUTH_TOKEN'),
        'campaign_id' => env('YANDEX_MARKET_CAMPAIGN_ID'),
    ],
    
    'flowwow' => [
        'enabled' => env('FLOWWOW_ENABLED', false),
        'api_url' => 'https://api.flowwow.com',
        'api_key' => env('FLOWWOW_API_KEY'),
    ],
];