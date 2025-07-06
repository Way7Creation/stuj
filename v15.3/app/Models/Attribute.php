<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Models/Attribute.php
| Описание: БЫСТРОЕ ИСПРАВЛЕНИЕ - обновленная модель атрибутов со значениями
|--------------------------------------------------------------------------
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Attribute extends Model
{
    use HasFactory;

    /**
     * Заполняемые поля
     */
    protected $fillable = [
        'name'
    ];

    /**
     * Связь с товарами (многие ко многим) - ОБРАТНАЯ СОВМЕСТИМОСТЬ
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_attributes');
    }

    /**
     * НОВОЕ: Связь со значениями атрибута
     */
    public function values(): HasMany
    {
        return $this->hasMany(AttributeValue::class)->orderBy('sort_order');
    }

    /**
     * НОВОЕ: Связь с активными значениями
     */
    public function activeValues(): HasMany
    {
        return $this->values()->where('is_active', true);
    }

    /**
     * Связь с маппингом маркетплейсов
     */
    public function marketplaceMaps(): HasMany
    {
        return $this->hasMany(MarketplaceMap::class, 'our_attr_id');
    }

    /**
     * Проверка, является ли атрибут камнем
     * (для квиза важно отличать камни от металлов)
     */
    public function isStone(): bool
    {
        $stones = [
            'агат', 'турмалин', 'аметист', 'хризолит', 'гранат',
            'лазурит', 'малахит', 'янтарь', 'жемчуг', 'опал',
            'топаз', 'изумруд', 'рубин', 'сапфир', 'алмаз'
        ];
        
        return in_array(mb_strtolower($this->name), $stones);
    }

    /**
     * Получить количество товаров с этим атрибутом (ОБНОВЛЕНО)
     */
    public function getProductsCountAttribute(): int
    {
        // Старая схема - прямая связь через pivot
        $oldCount = $this->products()->count();
        
        // Новая схема - через значения атрибутов (если таблица существует)
        $newCount = 0;
        try {
            if (class_exists('App\Models\AttributeValue')) {
                $newCount = $this->values()
                    ->join('product_attributes', 'attribute_values.id', '=', 'product_attributes.attribute_value_id')
                    ->count();
            }
        } catch (\Exception $e) {
            // Игнорируем ошибки, если новая структура еще не готова
        }
        
        return $oldCount + $newCount;
    }

    /**
     * НОВОЕ: Проверить, имеет ли атрибут значения
     */
    public function hasValues(): bool
    {
        try {
            return $this->values()->exists();
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * НОВОЕ: Получить первое значение атрибута
     */
    public function getDefaultValueAttribute()
    {
        try {
            return $this->activeValues()->first();
        } catch (\Exception $e) {
            return null;
        }
    }
}