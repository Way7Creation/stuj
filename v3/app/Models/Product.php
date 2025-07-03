<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Models/Product.php
| Описание: Модель товара с поддержкой эффекта "матрёшки" и связями
|--------------------------------------------------------------------------
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Product extends Model
{
    use HasFactory;

    /**
     * Заполняемые поля
     * ВАЖНО: image_layers и gallery_images - JSON поля!
     */
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'use_matryoshka',
        'image_layers',
        'gallery_images',
        'theme_id'
    ];

    /**
     * Автоматическое преобразование JSON полей
     */
    protected $casts = [
        'price' => 'decimal:2',
        'use_matryoshka' => 'boolean',
        'image_layers' => 'array',    // {"outer": "...", "inner": "..."}
        'gallery_images' => 'array',   // ["img1.jpg", "img2.jpg", ...]
    ];

    /**
     * Связь с темой (один товар - одна тема)
     */
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    /**
     * Связь с категориями (многие ко многим)
     */
    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    /**
     * Связь с атрибутами/камнями (многие ко многим)
     */
    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'product_attributes');
    }

    /**
     * Scope для фильтрации по категории
     */
    public function scopeInCategory($query, $categorySlug)
    {
        return $query->whereHas('categories', function ($q) use ($categorySlug) {
            $q->where('slug', $categorySlug);
        });
    }

    /**
     * Scope для фильтрации по теме
     */
    public function scopeWithTheme($query, $themeName)
    {
        return $query->whereHas('theme', function ($q) use ($themeName) {
            $q->where('name', 'like', '%' . $themeName . '%');
        });
    }

    /**
     * Scope для фильтрации по атрибуту
     */
    public function scopeWithAttribute($query, $attributeName)
    {
        return $query->whereHas('attributes', function ($q) use ($attributeName) {
            $q->where('name', 'like', '%' . $attributeName . '%');
        });
    }

    /**
     * Scope для товаров с эффектом матрёшки
     */
    public function scopeMatryoshka($query)
    {
        return $query->where('use_matryoshka', true);
    }

    /**
     * Получить главное изображение для превью
     */
    public function getMainImageAttribute()
    {
        if ($this->use_matryoshka && $this->image_layers) {
            return $this->image_layers['outer'] ?? null;
        }
        
        return $this->gallery_images[0] ?? null;
    }
}