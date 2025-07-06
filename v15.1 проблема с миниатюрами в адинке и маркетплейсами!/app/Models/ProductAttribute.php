<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductAttribute extends Model
{
    /**
     * Название таблицы
     */
    protected $table = 'product_attributes';

    /**
     * Отключаем timestamps для промежуточной таблицы
     */
    public $timestamps = false;

    /**
     * Заполняемые поля
     */
    protected $fillable = [
        'product_id',
        'attribute_id', 
        'attribute_value_id',
        'custom_value'
    ];

    /**
     * Типы полей
     */
    protected $casts = [
        'product_id' => 'integer',
        'attribute_id' => 'integer',
        'attribute_value_id' => 'integer'
    ];

    /**
     * Связь с товаром
     */
    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    /**
     * Связь с атрибутом
     */
    public function attribute(): BelongsTo
    {
        return $this->belongsTo(Attribute::class);
    }

    /**
     * Связь со значением атрибута
     */
    public function attributeValue(): BelongsTo
    {
        return $this->belongsTo(AttributeValue::class);
    }

    /**
     * Scope: только записи с кастомными значениями
     */
    public function scopeCustomValues($query)
    {
        return $query->whereNotNull('custom_value');
    }

    /**
     * Scope: только записи со стандартными значениями
     */
    public function scopeStandardValues($query)
    {
        return $query->whereNotNull('attribute_value_id');
    }

    /**
     * Аксессор: получить итоговое значение (кастомное или стандартное)
     */
    public function getFinalValueAttribute(): ?string
    {
        if ($this->custom_value) {
            return $this->custom_value;
        }

        return $this->attributeValue ? $this->attributeValue->value : null;
    }

    /**
     * Проверить, является ли значение кастомным
     */
    public function isCustomValue(): bool
    {
        return !is_null($this->custom_value);
    }

    /**
     * Проверить, является ли значение стандартным
     */
    public function isStandardValue(): bool
    {
        return !is_null($this->attribute_value_id);
    }
}