<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Models/MarketplaceMap.php
| Описание: Модель маппинга атрибутов для маркетплейсов
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
     */
    protected $fillable = [
        'marketplace',
        'our_attr_id',
        'marketplace_attr_name'
    ];

    /**
     * Связь с атрибутом
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
        $names = [
            'wildberries' => 'Wildberries',
            'ozon' => 'Ozon',
            'yandex_market' => 'Яндекс.Маркет',
            'flowwow' => 'Flowwow'
        ];

        return $names[$this->marketplace] ?? $this->marketplace;
    }
}