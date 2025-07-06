<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketplaceMap extends Model
{
    protected $fillable = [
        'marketplace',
        'mapping_type',
        'our_id',
        'marketplace_id',
        'marketplace_name',
        'additional_data'
    ];

    protected $casts = [
        'additional_data' => 'array'
    ];

    /**
     * Получить связанный атрибут (если это маппинг атрибута)
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class, 'our_id')
            ->where('mapping_type', 'attribute');
    }

    /**
     * Получить связанную категорию (если это маппинг категории)
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'our_id')
            ->where('mapping_type', 'category');
    }

    /**
     * Получить связанную сущность в зависимости от типа
     */
    public function getMappedEntityAttribute()
    {
        if ($this->mapping_type === 'attribute') {
            return $this->attribute;
        } elseif ($this->mapping_type === 'category') {
            return $this->category;
        }
        
        return null;
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

    /**
     * Scope для фильтрации по маркетплейсу
     */
    public function scopeForMarketplace($query, $marketplace)
    {
        return $query->where('marketplace', $marketplace);
    }

    /**
     * Scope для фильтрации по типу маппинга
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('mapping_type', $type);
    }
}