<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Models/Theme.php
| Описание: Модель темы (Минимализм, Готика, Винтаж и т.д.)
|--------------------------------------------------------------------------
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Theme extends Model
{
    use HasFactory;

    /**
     * Заполняемые поля
     */
    protected $fillable = [
        'name'
    ];

    /**
     * Связь с товарами (один ко многим)
     */
    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    /**
     * Получить количество товаров в теме
     */
    public function getProductsCountAttribute(): int
    {
        return $this->products()->count();
    }
}