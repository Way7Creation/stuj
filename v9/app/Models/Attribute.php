<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Models/Attribute.php
| Описание: Модель атрибутов товаров (агат, турмалин, серебро, золото и т.д.)
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
     * Связь с товарами (многие ко многим)
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_attributes');
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
}