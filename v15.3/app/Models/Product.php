<?php

/*
|--------------------------------------------------------------------------
| app/Models/Product.php (ИСПРАВЛЕННАЯ ВЕРСИЯ)
|--------------------------------------------------------------------------
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    use HasFactory;

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

    protected $casts = [
        'price' => 'decimal:2',
        'use_matryoshka' => 'boolean',
        'image_layers' => 'array',
        'gallery_images' => 'array'
    ];

    // Связи
    public function theme(): BelongsTo
    {
        return $this->belongsTo(Theme::class);
    }

    public function categories(): BelongsToMany
    {
        return $this->belongsToMany(Category::class, 'product_categories');
    }

    public function attributes(): BelongsToMany
    {
        return $this->belongsToMany(Attribute::class, 'product_attributes');
    }

    // Scope методы для фильтрации
    public function scopeInCategory(Builder $query, string $categorySlug): Builder
    {
        return $query->whereHas('categories', function ($q) use ($categorySlug) {
            $q->where('slug', $categorySlug);
        });
    }

    public function scopeWithTheme(Builder $query, string $themeName): Builder
    {
        return $query->whereHas('theme', function ($q) use ($themeName) {
            $q->where('name', 'like', "%{$themeName}%");
        });
    }

    public function scopeWithAttribute(Builder $query, string $attributeName): Builder
    {
        return $query->whereHas('attributes', function ($q) use ($attributeName) {
            $q->where('name', 'like', "%{$attributeName}%");
        });
    }

    public function scopeMatryoshka(Builder $query): Builder
    {
        return $query->where('use_matryoshka', true);
    }

    // Аксессоры
    public function getMainImageAttribute(): ?string
    {
        if ($this->use_matryoshka && isset($this->image_layers['outer'])) {
            return $this->image_layers['outer'];
        }
        
        return $this->gallery_images[0] ?? null;
    }

    // Мутаторы для автоматической генерации slug
    public function setNameAttribute($value)
    {
        $this->attributes['name'] = $value;
        if (!$this->exists) {
            $this->attributes['slug'] = \Str::slug($value);
        }
    }
    public function images()
    {
        return $this->hasMany(Image::class)->orderBy('sort_order', 'asc');
    }
    
    public function mainImage()
    {
        return $this->hasOne(Image::class)->where('is_main', true);
    }
    
    public function getMainImageUrlAttribute()
    {
        $mainImage = $this->mainImage;
        return $mainImage ? $mainImage->url : null;
    }
}