<?php

/*
|--------------------------------------------------------------------------
| Путь: /var/www/www-root/data/www/stuj.ru/app/Models/AttributeValue.php
| Описание: НОВАЯ модель значений атрибутов
|--------------------------------------------------------------------------
*/

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class AttributeValue extends Model
{
    use HasFactory;

    /**
     * Заполняемые поля
     */
    protected $fillable = [
        'attribute_id',
        'value',
        'slug',
        'sort_order',
        'is_active'
    ];

    protected $casts = [
        'sort_order' => 'integer',
        'is_active' => 'boolean'
    ];

    /**
     * Связь с атрибутом
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * Scope: только активные значения
     */
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    /**
     * Автоматическая генерация slug при создании
     */
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            if (empty($model->slug)) {
                $model->slug = Str::slug($model->value);
            }
        });

        static::updating(function ($model) {
            if ($model->isDirty('value') && empty($model->slug)) {
                $model->slug = Str::slug($model->value);
            }
        });
    }

    /**
     * Получить количество товаров с этим значением
     */
    public function getProductsCountAttribute(): int
    {
        try {
            return \DB::table('product_attributes')
                ->where('attribute_value_id', $this->id)
                ->count();
        } catch (\Exception $e) {
            return 0;
        }
    }
}