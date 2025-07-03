<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Models/Category.php
| Описание: Модель категории (Кольца, Браслеты, Серьги и т.д.)
|--------------------------------------------------------------------------
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Category extends Model
{
    use HasFactory;

    /**
     * Заполняемые поля
     */
    protected $fillable = [
        'name',
        'slug'
    ];

    /**
     * Связь с товарами (многие ко многим)
     */
    public function products(): BelongsToMany
    {
        return $this->belongsToMany(Product::class, 'product_categories');
    }

    /**
     * Получить количество товаров в категории
     */
    public function getProductsCountAttribute(): int
    {
        return $this->products()->count();
    }
}