<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Models/MarketplaceMap.php
| Описание: Маппинг атрибутов между нашей системой и маркетплейсами
|--------------------------------------------------------------------------
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceMap extends Model
{
    use HasFactory;

    /**
     * Заполняемые поля
     * ВАЖНО: marketplace строго из enum списка!
     */
    protected $fillable = [
        'marketplace',
        'our_attr_id',
        'marketplace_attr_name'
    ];

    /**
     * Константы маркетплейсов (СТРОГО ПО ТЗ!)
     */
    const MARKETPLACE_WILDBERRIES = 'wildberries';
    const MARKETPLACE_OZON = 'ozon';
    const MARKETPLACE_YANDEX = 'yandex_market';
    const MARKETPLACE_FLOWWOW = 'flowwow';

    /**
     * Список всех поддерживаемых маркетплейсов
     */
    const MARKETPLACES = [
        self::MARKETPLACE_WILDBERRIES => 'Wildberries',
        self::MARKETPLACE_OZON => 'Ozon',
        self::MARKETPLACE_YANDEX => 'Яндекс.Маркет',
        self::MARKETPLACE_FLOWWOW => 'flowwow'
    ];

    /**
     * Связь с нашим атрибутом
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'our_attr_id');
    }

    /**
     * Получить читаемое название маркетплейса
     */
    public function getMarketplaceNameAttribute(): string
    {
        return self::MARKETPLACES[$this->marketplace] ?? $this->marketplace;
    }
}